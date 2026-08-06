<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\AdminRepository;
use App\Repositories\CustomerRepository;

class CustomerService extends Service
{
    private CustomerRepository $customers;
    private AdminRepository $admin;

    public function __construct()
    {
        $this->customers = new CustomerRepository();
        $this->admin     = new AdminRepository();
    }

    public function list(int $page, int $perPage, string $search, string $filter, string $rep = ''): array
    {
        $paginated = $this->customers->paginateWithBalance($page, $perPage, $search, $filter, $rep);
        $totalAR   = $this->customers->getTotalAR();
        $repOptions = (new \App\Repositories\AccountingRepository())->repOptions();

        return [
            'paginated'   => $paginated,
            'total_ar'    => $totalAR,
            'search'      => $search,
            'filter'      => $filter,
            'rep'         => $rep,
            'rep_options' => $repOptions,
        ];
    }

    public function edit(int|string $id): array
    {
        $customer = $this->customers->findWithDetails((int)$id);
        if (!$customer) {
            throw new \RuntimeException('Customer not found.');
        }
        return [
            'customer'       => $customer,
            'payment_terms'  => $this->customers->getAllPaymentTerms(),
            'customer_types' => $this->admin->allCustomerTypes(),
            'tax_rates'      => $this->admin->allTaxRates(),
            'users'          => $this->admin->allUsers(),
        ];
    }

    public function update(int|string $id, array $post): void
    {
        $customer = $this->customers->findWithDetails((int)$id);
        if (!$customer) {
            throw new \RuntimeException('Customer not found.');
        }

        $this->customers->update((int)$id, [
            'company_name'              => trim($post['company_name']    ?? $customer['company_name']),
            'first_name'                => trim($post['first_name']      ?? ''),
            'last_name'                 => trim($post['last_name']       ?? ''),
            'email'                     => trim($post['email']           ?? ''),
            'cc_email'                  => trim($post['cc_email']        ?? ''),
            'phone'                     => trim($post['phone']           ?? ''),
            'work_phone'                => trim($post['work_phone']      ?? ''),
            'mobile'                    => trim($post['mobile']          ?? ''),
            'fax'                       => trim($post['fax']             ?? ''),
            'account_number'            => trim($post['account_number']  ?? ''),
            'payment_term_id'           => (int)($post['payment_term_id'] ?? 0) ?: null,
            'credit_limit'              => ($post['credit_limit'] ?? '') !== '' ? (float)$post['credit_limit'] : null,
            'preferred_delivery_method' => ($post['preferred_delivery_method'] ?? '') ?: null,
            'preferred_payment_method'  => ($post['preferred_payment_method']  ?? '') ?: null,
            'cc_number'                 => trim($post['cc_number']         ?? ''),
            'cc_exp_date'               => trim($post['cc_exp_date']       ?? ''),
            'cc_name'                   => trim($post['cc_name']           ?? ''),
            'cc_billing_address'        => trim($post['cc_billing_address'] ?? ''),
            'cc_billing_zip'            => trim($post['cc_billing_zip']    ?? ''),
            'tax_exempt'                => isset($post['tax_exempt']) ? 1 : 0,
            'sales_tax_code'            => trim($post['sales_tax_code']  ?? ''),
            'tax_rate_id'               => (int)($post['tax_rate_id'] ?? 0) ?: null,
            'resale_number'             => trim($post['resale_number']   ?? ''),
            'bill_address_1'            => trim($post['bill_address_1']  ?? ''),
            'bill_address_2'            => trim($post['bill_address_2']  ?? ''),
            'bill_city'                 => trim($post['bill_city']       ?? ''),
            'bill_state'                => strtoupper(trim($post['bill_state'] ?? '')),
            'bill_zip'                  => trim($post['bill_zip']        ?? ''),
            'ship_company'              => trim($post['ship_company']    ?? ''),
            'ship_contact'              => trim($post['ship_contact']    ?? ''),
            'ship_phone'                => trim($post['ship_phone']      ?? ''),
            'ship_address_1'            => trim($post['ship_address_1']  ?? ''),
            'ship_address_2'            => trim($post['ship_address_2']  ?? ''),
            'ship_city'                 => trim($post['ship_city']       ?? ''),
            'ship_state'                => strtoupper(trim($post['ship_state'] ?? '')),
            'ship_zip'                  => trim($post['ship_zip']        ?? ''),
            'notes'                     => trim($post['notes']           ?? ''),
            'is_active'                 => isset($post['is_active']) ? 1 : 0,
            'customer_type'             => ($post['customer_type'] ?? '') ?: null,
            'rep_id'                    => (int)($post['rep_id'] ?? 0) ?: null,
        ]);
    }

    public function show(int|string $id): array
    {
        $customer = $this->customers->findWithDetails($id);
        if (!$customer) {
            throw new \RuntimeException('Customer not found.');
        }

        $invoices     = $this->customers->getInvoices($id);
        $salesOrders  = $this->customers->getSalesOrders($id);
        $subCustomers = $this->customers->getSubCustomers($id);
        $payments     = $this->customers->getPayments($id);

        // Customer Intelligence — aggregates over data already in the system.
        // Named `profile` because a `stats` key already exists below.
        $profile        = $this->customers->getProfileStats((int)$id);
        $topProducts    = $this->customers->getTopProducts((int)$id);
        $revenueByMonth = $this->customers->getRevenueByMonth((int)$id);
        $alerts         = $this->customers->getAlerts((int)$id, $profile);
        $quotes       = \App\Core\Database::select(
            'SELECT q.id, q.quote_number, q.quote_date, q.expiry_date, q.status,
                    q.po_number, q.total_amount,
                    u.first_name AS rep_first, u.last_name AS rep_last
             FROM quotes q
             LEFT JOIN users u ON u.id = q.rep_id
             WHERE q.customer_id = ?
             ORDER BY q.quote_date DESC, q.id DESC',
            [(int)$id]
        );

        $tasks = (new \App\Repositories\TaskRepository())->getForRecord('customer', (int)$id);

        return [
            'customer'     => $customer,
            'invoices'     => $invoices,
            'sales_orders' => $salesOrders,
            'payments'     => $payments,
            'quotes'       => $quotes,
            'tasks'        => $tasks,
            'sub_customers'=> $subCustomers,
            'profile'         => $profile,
            'top_products'    => $topProducts,
            'revenue_by_month'=> $revenueByMonth,
            'alerts'          => $alerts,
            'stats'        => $this->customers->getStats((int)$id),
            'notes'        => $this->customers->getNotes((int)$id),
            'open_balance' => array_sum(array_column(
                array_filter($invoices, fn($i) => $i['status'] !== 'paid'),
                'balance_due'
            )),
        ];
    }

    public function autocomplete(string $term): array
    {
        return $this->customers->search($term, [], 20);
    }
}
