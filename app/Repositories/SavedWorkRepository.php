<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * What a website customer keeps in their account: designs from the designers, and
 * carts saved as quotes. Every query is scoped by login, so one person's id in a URL
 * can never open another person's work.
 */
class SavedWorkRepository
{
    public const MAX_DESIGNS = 200;
    public const MAX_QUOTES  = 200;

    // ── Designs ───────────────────────────────────────────────────────────────

    public function designs(int $loginId): array
    {
        return Database::select("SELECT id, kind, name, data, summary, price, updated_at FROM saved_designs
                                 WHERE login_id = ? ORDER BY updated_at DESC", [$loginId]);
    }

    public function design(int $loginId, int $id): array|false
    {
        return Database::selectOne("SELECT * FROM saved_designs WHERE id = ? AND login_id = ?", [$id, $loginId]);
    }

    public function countDesigns(int $loginId): int
    {
        return (int)Database::selectOne("SELECT COUNT(*) n FROM saved_designs WHERE login_id = ?", [$loginId])['n'];
    }

    public function createDesign(int $loginId, string $kind, string $name, array $data, string $summary, ?float $price): int
    {
        Database::query("INSERT INTO saved_designs (login_id, kind, name, data, summary, price) VALUES (?, ?, ?, ?, ?, ?)",
            [$loginId, $kind, $name, json_encode($data), $summary, $price]);

        return (int)Database::connection()->lastInsertId();
    }

    public function updateDesign(int $loginId, int $id, string $name, array $data, string $summary, ?float $price): bool
    {
        return Database::query("UPDATE saved_designs SET name = ?, data = ?, summary = ?, price = ? WHERE id = ? AND login_id = ?",
            [$name, json_encode($data), $summary, $price, $id, $loginId])->rowCount() > 0
            || (bool)$this->design($loginId, $id);
    }

    public function deleteDesign(int $loginId, int $id): void
    {
        Database::query("DELETE FROM saved_designs WHERE id = ? AND login_id = ?", [$id, $loginId]);
    }

    // ── Quotes ────────────────────────────────────────────────────────────────

    public function quotes(int $loginId): array
    {
        return Database::select("SELECT q.id, q.name, q.items, q.total_when_saved, q.status, q.updated_at, so.so_number
                                 FROM saved_quotes q LEFT JOIN sales_orders so ON so.id = q.sales_order_id
                                 WHERE q.login_id = ? ORDER BY q.updated_at DESC", [$loginId]);
    }

    public function quote(int $loginId, int $id): array|false
    {
        return Database::selectOne("SELECT q.*, so.so_number FROM saved_quotes q LEFT JOIN sales_orders so ON so.id = q.sales_order_id
                                    WHERE q.id = ? AND q.login_id = ?", [$id, $loginId]);
    }

    public function countQuotes(int $loginId): int
    {
        return (int)Database::selectOne("SELECT COUNT(*) n FROM saved_quotes WHERE login_id = ?", [$loginId])['n'];
    }

    public function createQuote(int $loginId, string $name, array $items, float $total): int
    {
        Database::query("INSERT INTO saved_quotes (login_id, name, items, total_when_saved) VALUES (?, ?, ?, ?)",
            [$loginId, $name, json_encode($items), $total]);

        return (int)Database::connection()->lastInsertId();
    }

    public function deleteQuote(int $loginId, int $id): void
    {
        Database::query("DELETE FROM saved_quotes WHERE id = ? AND login_id = ? AND status = 'saved'", [$id, $loginId]);
    }

    /** A saved quote that has just been placed on terms. */
    public function markQuoteOrdered(int $loginId, int $id, int $salesOrderId): void
    {
        Database::query("UPDATE saved_quotes SET status = 'ordered', sales_order_id = ? WHERE id = ? AND login_id = ? AND status = 'saved'",
            [$salesOrderId, $id, $loginId]);
    }
}
