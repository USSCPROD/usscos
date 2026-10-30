<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class FulfillmentRepository
{
    public function __construct(public ?\PDO $pdo = null)
    {
        $this->pdo ??= Database::connection();
    }

    private function rows(string $sql, array $params = []): array
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($params);
        return $s->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function run(string $sql, array $params = []): int
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($params);
        return $s->rowCount();
    }

    public static function installed(): bool
    {
        $pdo = Database::connection();
        $sql = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='fulfillment_orders'"
            : "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='fulfillment_orders'";
        return (int)$pdo->query($sql)->fetchColumn() > 0;
    }

    public static function orderIsManaged(int $id): bool
    {
        return self::installed() && (new self())->managed($id);
    }

    public static function invoiceIsManaged(int $id): bool
    {
        return self::installed() && (new self())->invoiceManaged($id);
    }

    public function managed(int $id): bool
    {
        return $this->rows('SELECT sales_order_id FROM fulfillment_orders WHERE sales_order_id = ?', [$id]) !== [];
    }

    public function invoiceManaged(int $id): bool
    {
        return $this->rows('SELECT id FROM fulfillments WHERE invoice_id = ?', [$id]) !== [];
    }

    public function order(int $id, bool $lock = false): array
    {
        $suffix = $lock && $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        return $this->rows('SELECT so.*, c.company_name, c.payment_term_id AS customer_term_id,
            fo.billing_policy, fo.payment_term_id AS fulfillment_term_id
            FROM sales_orders so JOIN customers c ON c.id = so.customer_id
            LEFT JOIN fulfillment_orders fo ON fo.sales_order_id = so.id WHERE so.id = ?' . $suffix, [$id])[0] ?? [];
    }

    public function lines(int $id): array
    {
        return $this->rows("SELECT li.*, COALESCE((SELECT SUM(fl.qty) FROM fulfillment_lines fl
            JOIN fulfillments f ON f.id = fl.fulfillment_id WHERE fl.sales_order_line_id = li.id
            AND f.status <> 'cancelled'),0) AS allocated
            FROM sales_order_line_items li WHERE li.sales_order_id = ? ORDER BY li.id", [$id]);
    }

    public function term(int $id): array
    {
        return $this->rows('SELECT * FROM payment_terms WHERE id = ? AND is_active = 1', [$id])[0] ?? [];
    }

    public function adopt(int $id, string $policy, int $termId, int $userId): void
    {
        if ($this->rows('SELECT id FROM invoices WHERE sales_order_id = ?', [$id])
            || $this->rows('SELECT id FROM sales_order_line_items WHERE sales_order_id = ? AND qty_shipped <> 0', [$id])
            || $this->rows('SELECT id FROM payments WHERE sales_order_id = ?', [$id])) {
            throw new \DomainException('This order has billing, payments, or shipment history. Keep its existing workflow until history is reconciled.');
        }
        $this->run('INSERT INTO fulfillment_orders (sales_order_id,billing_policy,payment_term_id,created_by) VALUES (?,?,?,?)', [$id,$policy,$termId,$userId]);
    }

    public function all(int $id): array
    {
        return $this->rows('SELECT * FROM fulfillments WHERE sales_order_id = ? ORDER BY id', [$id]);
    }

    public function find(int $id): array
    {
        return $this->rows('SELECT * FROM fulfillments WHERE id = ?', [$id])[0] ?? [];
    }

    public function byKey(string $key): array
    {
        return $this->rows('SELECT * FROM fulfillments WHERE request_key = ?', [$key])[0] ?? [];
    }

    public function create(int $orderId, array $data, int $userId): int
    {
        $this->run(
            'INSERT INTO fulfillments (sales_order_id,method,request_key,scheduled_date,driver,notes,created_by) VALUES (?,?,?,?,?,?,?)',
            [$orderId,$data['method'],$data['request_key'],$data['scheduled_date'] ?: null,$data['driver'] ?: null,$data['notes'] ?: null,$userId]
        );
        return (int)$this->pdo->lastInsertId();
    }

    public function addLine(int $id, int $lineId, float $qty): void
    {
        $this->run('INSERT INTO fulfillment_lines (fulfillment_id,sales_order_line_id,qty) VALUES (?,?,?)', [$id,$lineId,$qty]);
    }

    public function items(int $id): array
    {
        return $this->rows('SELECT li.*, fl.qty FROM fulfillment_lines fl JOIN sales_order_line_items li ON li.id = fl.sales_order_line_id WHERE fl.fulfillment_id = ?', [$id]);
    }

    public function event(int $id, string $event, string $details, int $userId): void
    {
        $this->run('INSERT INTO fulfillment_events (fulfillment_id,event,details,user_id) VALUES (?,?,?,?)', [$id,$event,$details,$userId]);
    }

    public function transition(int $id, string $status, array $post): void
    {
        $this->run(
            'UPDATE fulfillments SET status=?, recipient=?, tracking=?, completed_at=? WHERE id=?',
            [$status,$post['recipient'] ?: null,$post['tracking'] ?: null,$status === 'completed' ? date('Y-m-d H:i:s') : null,$id]
        );
    }

    public function incrementCompleted(int $lineId, float $qty): void
    {
        $this->run('UPDATE sales_order_line_items SET qty_shipped = qty_shipped + ? WHERE id = ?', [$qty,$lineId]);
    }

    public function remaining(int $orderId): float
    {
        $r = $this->rows('SELECT SUM(qty_ordered - qty_shipped) AS qty FROM sales_order_line_items WHERE sales_order_id = ?', [$orderId]);
        return (float)($r[0]['qty'] ?? 0);
    }

    public function setOrderStatus(int $id, string $status): void
    {
        $this->run('UPDATE sales_orders SET status=? WHERE id=?', [$status,$id]);
    }

    public function stampInvoiceCompletion(int $id): void
    {
        $rows = $this->rows('SELECT status,completed_at FROM fulfillments WHERE invoice_id=?', [$id]);
        if (!$rows || array_filter($rows, fn ($row) => $row['status'] !== 'completed')) {
            return;
        }
        $this->run('UPDATE invoices SET ship_date=? WHERE id=?', [substr(max(array_column($rows, 'completed_at')), 0, 10),$id]);
    }

    public function resetInvoiceReview(int $id): void
    {
        $this->run("UPDATE invoices SET review_status='pending',reviewed_by=NULL,reviewed_at=NULL WHERE id=?", [$id]);
    }

    public function invoice(int $id): array
    {
        $suffix = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        return $this->rows('SELECT * FROM invoices WHERE id=?' . $suffix, [$id])[0] ?? [];
    }

    public function linkInvoice(int $id, int $invoiceId): void
    {
        $this->run('UPDATE fulfillments SET invoice_id=? WHERE id=? AND invoice_id IS NULL', [$invoiceId,$id]);
    }

    public function checkReceipt(int $id): array
    {
        return $this->rows('SELECT * FROM delivery_check_receipts WHERE fulfillment_id=?', [$id])[0] ?? [];
    }

    public function receiveCheck(int $id, int $invoiceId, string $number, float $amount, int $userId): void
    {
        $this->run(
            'INSERT INTO delivery_check_receipts (fulfillment_id,invoice_id,check_number,amount,collected_by,collected_at) VALUES (?,?,?,?,?,?)',
            [$id,$invoiceId,$number,$amount,$userId,date('Y-m-d H:i:s')]
        );
    }

    public function checks(): array
    {
        return $this->rows('SELECT r.*, i.invoice_number, c.company_name FROM delivery_check_receipts r JOIN invoices i ON i.id=r.invoice_id JOIN customers c ON c.id=i.customer_id WHERE r.posted_payment_id IS NULL ORDER BY r.collected_at');
    }

    public function markCheckPosted(int $id, int $paymentId): void
    {
        $this->run('UPDATE delivery_check_receipts SET posted_payment_id=?,handed_to_accounting_at=? WHERE id=? AND posted_payment_id IS NULL', [$paymentId,date('Y-m-d H:i:s'),$id]);
    }

    public function queue(string $method): array
    {
        $where = $method === '' ? '' : ' AND f.method = ?';
        return $this->rows("SELECT f.*,so.so_number,c.company_name,fo.billing_policy,(SELECT SUM(li.qty_ordered-li.qty_shipped) FROM sales_order_line_items li WHERE li.sales_order_id=so.id) AS remaining FROM fulfillments f
            JOIN sales_orders so ON so.id=f.sales_order_id JOIN customers c ON c.id=so.customer_id
            JOIN fulfillment_orders fo ON fo.sales_order_id=so.id WHERE f.status <> 'cancelled'" . $where . ' ORDER BY f.id DESC', $method === '' ? [] : [$method]);
    }
}
