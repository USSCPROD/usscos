<?php

declare(strict_types=1);

namespace App\Services;

final class FulfillmentPolicy
{
    public const METHODS = ['carrier' => 'Carrier Shipping', 'pickup' => 'Customer Pickup', 'delivery' => 'USSC Delivery'];

    public static function enabled(): bool
    {
        return filter_var($_ENV['FULFILLMENT_V2_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function quantity(mixed $value): float
    {
        if (!is_numeric($value) || !is_finite((float)$value) || (float)$value <= 0
            || abs((float)$value - round((float)$value, 4)) > 0.0000001) {
            throw new \DomainException('Enter a positive quantity with at most four decimal places.');
        }
        return round((float)$value, 4);
    }

    public static function assertTransition(array $fulfillment, string $action, string $policy, array $invoice, bool $checkReceived): void
    {
        $status = $fulfillment['status'];
        $method = $fulfillment['method'];
        $allowed = match ($action) {
            'ready' => $status === 'planned',
            'dispatch' => $method === 'delivery' && $status === 'ready',
            'failed' => $method === 'delivery' && $status === 'out_for_delivery',
            'complete' => $method === 'delivery' ? $status === 'out_for_delivery' : $status === 'ready',
            'cancel' => in_array($status, ['planned', 'ready'], true) && empty($fulfillment['invoice_id']),
            default => false,
        };
        if (!$allowed) {
            throw new \DomainException('This action is not available in the current fulfillment status.');
        }
        if ($policy === 'cod' && in_array($action, ['dispatch', 'complete'], true)) {
            if (!$invoice || $invoice['status'] === 'void' || $invoice['review_status'] !== 'approved') {
                throw new \DomainException('Prepare and approve the COD invoice before release.');
            }
            if ($action === 'complete' && (float)$invoice['balance_due'] > 0.009
                && !($method === 'delivery' && $checkReceived)) {
                throw new \DomainException('Payment must be recorded before releasing these goods.');
            }
        }
    }
}
