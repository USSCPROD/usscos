<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class CategoryRepository
{
    /** All categories with their parent name and product count, ordered as a tree. */
    public function allWithCounts(): array
    {
        return Database::select("
            SELECT c.*,
                   pa.name AS parent_name,
                   (SELECT COUNT(*) FROM product_categories pc WHERE pc.category_id = c.id) AS product_count,
                   (SELECT COUNT(*) FROM categories ch WHERE ch.parent_id = c.id)           AS child_count
            FROM categories c
            LEFT JOIN categories pa ON pa.id = c.parent_id
            ORDER BY COALESCE(pa.sort_order, c.sort_order),
                     (pa.id IS NOT NULL),
                     c.sort_order,
                     c.name
        ");
    }

    /**
     * Same data arranged as parents each holding a `children` array —
     * what the tree view and the site navigation both need.
     */
    public function tree(bool $websiteOnly = false): array
    {
        $rows = $this->allWithCounts();
        if ($websiteOnly) {
            $rows = array_filter($rows, fn($r) => (int)$r['show_on_website'] === 1 && (int)$r['is_active'] === 1);
        }

        $byId = [];
        foreach ($rows as $r) {
            $r['children'] = [];
            $byId[(int)$r['id']] = $r;
        }

        $tree = [];
        foreach ($byId as $id => $row) {
            $parentId = $row['parent_id'] !== null ? (int)$row['parent_id'] : null;
            if ($parentId !== null && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$byId[$id];
            } else {
                $tree[] = &$byId[$id];
            }
        }
        unset($row);

        return $tree;
    }

    /** Flat list suitable for a parent <select>, indented by depth. */
    public function selectOptions(?int $excludeId = null): array
    {
        $out = [];
        foreach ($this->tree() as $parent) {
            if ($excludeId !== null && (int)$parent['id'] === $excludeId) {
                continue;   // a category can't be its own parent
            }
            $out[] = ['id' => (int)$parent['id'], 'label' => $parent['name']];
            foreach ($parent['children'] as $child) {
                if ($excludeId !== null && (int)$child['id'] === $excludeId) {
                    continue;
                }
                $out[] = ['id' => (int)$child['id'], 'label' => '— ' . $child['name']];
            }
        }
        return $out;
    }

    public function find(int $id): array|false
    {
        return Database::selectOne("SELECT * FROM categories WHERE id = ? LIMIT 1", [$id]);
    }

    public function findBySlug(string $slug): array|false
    {
        return Database::selectOne("SELECT * FROM categories WHERE slug = ? LIMIT 1", [$slug]);
    }

    public function insert(array $d): int
    {
        $pdo = Database::connection();
        $pdo->prepare("
            INSERT INTO categories
                (parent_id, name, slug, description, sort_order,
                 is_active, show_on_website, seo_title, seo_description)
            VALUES
                (:parent_id, :name, :slug, :description, :sort_order,
                 :is_active, :show_on_website, :seo_title, :seo_description)
        ")->execute($this->params($d));

        return (int)$pdo->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $params       = $this->params($d);
        $params[':id'] = $id;

        Database::connection()->prepare("
            UPDATE categories SET
                parent_id       = :parent_id,
                name            = :name,
                slug            = :slug,
                description     = :description,
                sort_order      = :sort_order,
                is_active       = :is_active,
                show_on_website = :show_on_website,
                seo_title       = :seo_title,
                seo_description = :seo_description
            WHERE id = :id
        ")->execute($params);
    }

    /**
     * Categories are only deletable when nothing depends on them — children are
     * re-parented by the FK, but products would silently lose their classification.
     */
    public function delete(int $id): void
    {
        Database::statement("UPDATE categories SET parent_id = NULL WHERE parent_id = ?", [$id]);
        Database::statement("DELETE FROM categories WHERE id = ?", [$id]);
    }

    /** Build a slug that isn't already taken. */
    public function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name) ?? '', '-'));
        if ($base === '') $base = 'category';
        $base = substr($base, 0, 140);

        $slug = $base;
        $n    = 2;
        while (true) {
            $row = Database::selectOne(
                "SELECT id FROM categories WHERE slug = ? " . ($ignoreId ? "AND id != ?" : "") . " LIMIT 1",
                $ignoreId ? [$slug, $ignoreId] : [$slug]
            );
            if (!$row) return $slug;
            $slug = $base . '-' . $n++;
        }
    }

    // ------------------------------------------------------- product links

    public function categoryIdsForProduct(int $productId): array
    {
        return array_map(
            fn($r) => (int)$r['category_id'],
            Database::select("SELECT category_id FROM product_categories WHERE product_id = ?", [$productId])
        );
    }

    public function categoriesForProduct(int $productId): array
    {
        return Database::select("
            SELECT c.*, pc.is_primary,
                   pa.name AS parent_name
            FROM product_categories pc
            JOIN categories c        ON c.id  = pc.category_id
            LEFT JOIN categories pa  ON pa.id = c.parent_id
            WHERE pc.product_id = ?
            ORDER BY pc.is_primary DESC, c.sort_order
        ", [$productId]);
    }

    /** Replace a product's category assignments; the first becomes primary. */
    public function setProductCategories(int $productId, array $categoryIds): void
    {
        // The form submits a trailing empty value so an all-unticked selection still
        // arrives — strip it and anything else that isn't a real id.
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $categoryIds),
            fn($id) => $id > 0
        )));

        Database::statement("DELETE FROM product_categories WHERE product_id = ?", [$productId]);
        if (empty($ids)) return;

        $stmt = Database::connection()->prepare("
            INSERT INTO product_categories (product_id, category_id, is_primary)
            VALUES (?, ?, ?)
        ");
        foreach ($ids as $i => $catId) {
            $stmt->execute([$productId, $catId, $i === 0 ? 1 : 0]);
        }
    }

    /**
     * Assign many products to one category in a single action (bulk categorise).
     * Existing assignments are kept; a product with no primary yet gets one.
     */
    public function assignProducts(int $categoryId, array $productIds): int
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $productIds),
            fn($id) => $id > 0
        )));
        if (empty($ids)) return 0;

        $insert = Database::connection()->prepare("
            INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
            VALUES (?, ?, 0)
        ");
        $promote = Database::connection()->prepare("
            UPDATE product_categories SET is_primary = 1
            WHERE product_id = ? AND category_id = ?
              AND NOT EXISTS (
                  SELECT 1 FROM (SELECT * FROM product_categories) x
                  WHERE x.product_id = ? AND x.is_primary = 1
              )
        ");

        $n = 0;
        foreach ($ids as $pid) {
            $insert->execute([$pid, $categoryId]);
            $n += $insert->rowCount();
            $promote->execute([$pid, $categoryId, $pid]);
        }
        return $n;
    }

    public function uncategorisedCount(): int
    {
        return (int)(Database::selectOne("
            SELECT COUNT(*) AS c FROM products p
            WHERE p.item_type != 'raw_material'
              AND NOT EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id)
        ")['c'] ?? 0);
    }

    private function params(array $d): array
    {
        return [
            ':parent_id'       => ($d['parent_id'] ?? '') !== '' ? (int)$d['parent_id'] : null,
            ':name'            => trim((string)($d['name'] ?? '')),
            ':slug'            => trim((string)($d['slug'] ?? '')),
            ':description'     => trim((string)($d['description'] ?? '')) ?: null,
            ':sort_order'      => (int)($d['sort_order'] ?? 0),
            ':is_active'       => (int)(bool)($d['is_active'] ?? 1),
            ':show_on_website' => (int)(bool)($d['show_on_website'] ?? 1),
            ':seo_title'       => trim((string)($d['seo_title'] ?? '')) ?: null,
            ':seo_description' => trim((string)($d['seo_description'] ?? '')) ?: null,
        ];
    }
}
