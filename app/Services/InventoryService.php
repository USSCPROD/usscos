<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InventoryRepository;

class InventoryService
{
    private InventoryRepository $repo;

    public const CAN_ADJUST = ['owner', 'admin', 'bookkeeper', 'manager', 'shipping'];

    public function __construct()
    {
        $this->repo = new InventoryRepository();
    }

    public function canAdjust(string $role): bool
    {
        return in_array($role, self::CAN_ADJUST, true);
    }

    public function list(int $page, string $search, string $brand, string $filter): array
    {
        return $this->repo->paginateProducts($page, 50, $search, $brand, $filter);
    }

    public function show(int $id): array|false
    {
        return $this->repo->getProductWithTransactions($id);
    }

    public function getAllBrands(): array
    {
        return $this->repo->getAllBrands();
    }

    public function getStats(): array
    {
        return [
            'low_stock'    => $this->repo->getLowStockCount(),
            'out_of_stock' => $this->repo->getOutOfStockCount(),
        ];
    }

    public function adjust(int $productId, array $post, int $userId): string|true
    {
        $qty   = trim($post['qty'] ?? '');
        $type  = $post['adjust_type'] ?? 'add';
        $notes = trim($post['notes'] ?? '');

        if ($qty === '' || !is_numeric($qty)) {
            return 'Please enter a valid quantity.';
        }

        if (!in_array($type, ['add', 'subtract', 'set'], true)) {
            return 'Invalid adjustment type.';
        }

        $amount = (float)$qty;
        if ($type === 'subtract') {
            $amount = -$amount;
        }

        $this->repo->adjust($productId, $amount, $type, $notes, $userId);

        return true;
    }
}
