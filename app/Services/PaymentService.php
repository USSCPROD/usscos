<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\PaymentRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\CustomerRepository;

class PaymentService extends Service
{
    private PaymentRepository $payments;
    private InvoiceRepository $invoices;
    private CustomerRepository $customers;

    public function __construct()
    {
        $this->payments  = new PaymentRepository();
        $this->invoices  = new InvoiceRepository();
        $this->customers = new CustomerRepository();
    }

    public function editForm(int $paymentId): array
    {
        $payment = $this->payments->findById($paymentId);
        if (!$payment) {
            throw new \RuntimeException('Payment not found.');
        }

        $applications = $this->payments->getApplications($paymentId);
        $applied      = array_sum(array_column($applications, 'amount_applied'));
        $unapplied    = round((float)$payment['amount'] - $applied, 2);

        // Open invoices for this customer (excluding already-fully-paid ones)
        // but include invoices that already have applications for this payment
        $openInvoices = $this->payments->getOpenInvoicesForCustomer((int)$payment['customer_id']);

        // Also include invoices already applied to this payment (may now be paid/partial)
        $appliedInvoiceIds = array_column($applications, 'invoice_id');
        $openIds           = array_column($openInvoices, 'id');
        foreach ($appliedInvoiceIds as $invId) {
            if (!in_array($invId, $openIds)) {
                $inv = $this->invoices->findWithDetails($invId);
                if ($inv) $openInvoices[] = $inv;
            }
        }

        return [
            'payment'      => $payment,
            'applications' => $applications,
            'unapplied'    => $unapplied,
            'open_invoices'=> $openInvoices,
        ];
    }

    public function update(int $paymentId, array $post): void
    {
        $payment = $this->payments->findById($paymentId);
        if (!$payment) {
            throw new \RuntimeException('Payment not found.');
        }

        // Update payment header
        $this->payments->updatePayment($paymentId, [
            ':payment_date'     => $post['payment_date'],
            ':payment_method'   => $post['payment_method'] ?? 'check',
            ':reference_number' => ($post['reference_number'] ?? '') ?: null,
            ':amount'           => round((float)str_replace(',', '', $post['amount'] ?? '0'), 2),
            ':memo'             => ($post['memo'] ?? '') ?: null,
        ]);

        // Replace all applications
        $oldInvoiceIds = $this->payments->deleteApplications($paymentId);

        $invoiceIds = $post['invoice_id'] ?? [];
        $amounts    = $post['apply']      ?? [];
        $newIds     = [];

        foreach ($invoiceIds as $i => $invId) {
            $amt = round((float)str_replace(',', '', $amounts[$i] ?? '0'), 2);
            if ($amt <= 0 || !$invId) continue;
            $this->payments->insertApplication($paymentId, (int)$invId, $amt);
            $newIds[] = (int)$invId;
        }

        foreach (array_unique(array_merge($oldInvoiceIds, $newIds)) as $invId) {
            $this->payments->recalcInvoice((int)$invId);
        }
    }

    public function createCustomerForm(int $customerId, ?int $preselectedInvoiceId = null): array
    {
        $customer = $this->customers->findWithDetails($customerId);
        if (!$customer) {
            throw new \RuntimeException('Customer not found.');
        }

        $openInvoices = $this->payments->getOpenInvoicesForCustomer($customerId);

        return [
            'customer'             => $customer,
            'open_invoices'        => $openInvoices,
            'preselected_invoice'  => $preselectedInvoiceId,
        ];
    }

    public function storeCustomerPayment(int $userId, int $customerId, array $post): void
    {
        $customer = $this->customers->findWithDetails($customerId);
        if (!$customer) {
            throw new \RuntimeException('Customer not found.');
        }

        $totalAmount = round((float)str_replace(',', '', $post['amount'] ?? '0'), 2);
        if ($totalAmount <= 0) {
            throw new \RuntimeException('Payment amount must be greater than zero.');
        }

        $paymentId = $this->payments->insert([
            ':customer_id'      => $customerId,
            ':payment_date'     => $post['payment_date'],
            ':payment_method'   => $post['payment_method'] ?? 'check',
            ':reference_number' => ($post['reference_number'] ?? '') ?: null,
            ':amount'           => $totalAmount,
            ':memo'             => ($post['memo'] ?? '') ?: null,
            ':created_by'       => $userId,
        ]);

        $invoiceIds = $post['invoice_id']  ?? [];
        $amounts    = $post['apply']       ?? [];
        $applied    = [];

        foreach ($invoiceIds as $i => $invId) {
            $amt = round((float)str_replace(',', '', $amounts[$i] ?? '0'), 2);
            if ($amt <= 0) continue;
            $invId = (int)$invId;
            if (!$invId) continue;
            $this->payments->insertApplication($paymentId, $invId, $amt);
            $applied[] = $invId;
        }

        foreach (array_unique($applied) as $invId) {
            $this->payments->recalcInvoice($invId);
        }
    }
}
