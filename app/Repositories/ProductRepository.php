<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Repository;

class ProductRepository extends Repository
{
    /**
     * Every products column the edit form is allowed to write.
     * Only keys actually present in the POST are updated.
     */
    private const EDITABLE = [
        // Identity
        'sku', 'name', 'brand_id', 'product_line', 'color', 'category',
        'brand_category', 'sports', 'uom_code', 'pack_level', 'item_type',
        // Descriptions
        'short_description', 'long_description', 'purchase_description',
        'edi_description', 'gs1_description', 'amazon_title', 'amazon_bullets',
        // Pricing
        'cost', 'price', 'retail_price', 'dist_price', 'dist_price_2026',
        'stocking_dist', 'website_price', 'amazon_price',
        // Accounting
        'income_account', 'cogs_account', 'asset_account', 'tax_agency',
        'sales_tax_code',
        // Inventory
        'reorder_point', 'min_order_qty', 'lead_time_days',
        // Vendor / barcodes
        'preferred_vendor_name', 'vendor_part_number', 'mpn', 'gtin12', 'gtin14', 'asin',
        // Shipping unit dimensions
        'height', 'width', 'depth', 'dim_unit',
        'gross_weight', 'net_weight', 'weight_unit', 'gs1_status',
        // Individual unit dimensions
        'unit_height', 'unit_width', 'unit_depth',
        'unit_gross_weight', 'unit_net_weight',
        // Case / pallet
        'units_per_case', 'case_length', 'case_width', 'case_height',
        'case_weight_gross', 'case_weight_net', 'pallet_ti', 'pallet_hi', 'pallet_qty',
        // Compliance
        'country_of_origin', 'hts_code', 'hazmat_class', 'un_number',
        // Specs
        'paint_type', 'voc', 'flash_point', 'propellant', 'odor', 'dry_time',
        'field_ready', 'coverage', 'dilution', 'surface_use', 'application',
        'recommended_use', 'clean_up', 'shelf_life',
        'storage_temp_min', 'storage_temp_max', 'warranty', 'spec_notes', 'review_note',
        // Web
        'website_image_url', 'publish_to_website',
        // Flags
        'is_active', 'is_taxable', 'is_hazmat',
    ];

    private const DECIMAL_FIELDS = [
        'cost', 'price', 'retail_price', 'dist_price', 'dist_price_2026',
        'stocking_dist', 'website_price', 'amazon_price',
        'reorder_point', 'min_order_qty',
        'height', 'width', 'depth', 'gross_weight', 'net_weight',
        'unit_height', 'unit_width', 'unit_depth',
        'unit_gross_weight', 'unit_net_weight',
        'case_length', 'case_width', 'case_height',
        'case_weight_gross', 'case_weight_net',
    ];

    private const INTEGER_FIELDS = [
        'brand_id', 'lead_time_days', 'units_per_case',
        'pallet_ti', 'pallet_hi', 'pallet_qty',
        'storage_temp_min', 'storage_temp_max',
    ];

    private const BOOLEAN_FIELDS = ['is_active', 'is_taxable', 'is_hazmat', 'publish_to_website'];

    /** NOT NULL columns that must never be written as null. */
    private const REQUIRED_FIELDS = ['sku', 'name'];

    protected function getTable(): string
    {
        return 'products';
    }

    public function paginateWithBrand(int $page, int $perPage, string $search = '', string $brand = '', string $filter = 'active'): array
    {
        $params     = [];
        $conditions = ["p.item_type != 'raw_material'"];

        if ($search !== '') {
            $conditions[] = '(p.sku LIKE ? OR p.name LIKE ? OR p.short_description LIKE ?)';
            $s = '%' . $search . '%';
            array_push($params, $s, $s, $s);
        }

        if ($brand !== '') {
            $conditions[] = 'b.name = ?';
            $params[]     = $brand;
        }

        if ($filter === 'inactive') {
            $conditions[] = 'p.is_active = 0';
        } else {
            $conditions[] = 'p.is_active = 1';
        }

        $where  = 'WHERE ' . implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::selectOne(
            "SELECT COUNT(*) AS total
             FROM products p
             LEFT JOIN product_brands b ON b.id = p.brand_id
             $where",
            $params
        )['total'] ?? 0);

        $rows = Database::select(
            "SELECT p.id, p.sku, p.name, p.short_description, p.color,
                    p.price, p.cost, p.uom_code, p.is_active, p.is_taxable,
                    p.qty_on_hand, p.category, p.product_line,
                    b.name AS brand_name
             FROM products p
             LEFT JOIN product_brands b ON b.id = p.brand_id
             $where
             ORDER BY p.sku ASC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
            'from'         => $offset + 1,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function paginateRawMaterials(int $page, int $perPage, string $search = ''): array
    {
        $conditions = ["p.item_type = 'raw_material'"];
        $params     = [];

        if ($search !== '') {
            $conditions[] = '(p.sku LIKE ? OR p.name LIKE ?)';
            $s = '%' . $search . '%';
            array_push($params, $s, $s);
        }

        $where  = 'WHERE ' . implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::selectOne(
            "SELECT COUNT(*) AS total FROM products p $where", $params
        )['total'] ?? 0);

        $rows = Database::select(
            "SELECT p.id, p.sku, p.name, p.uom_code, p.cost, p.qty_on_hand,
                    p.reorder_point, p.preferred_vendor_name, p.vendor_part_number,
                    p.is_active
             FROM products p
             $where
             ORDER BY p.name ASC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
            'from'         => $offset + 1,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function findWithBrand(int $id): array|false
    {
        return Database::selectOne("
            SELECT p.*, b.name AS brand_name
            FROM products p
            LEFT JOIN product_brands b ON b.id = p.brand_id
            WHERE p.id = ?
            LIMIT 1
        ", [$id]);
    }

    public function insertRawMaterial(array $data): int
    {
        $pdo = Database::connection();
        $pdo->prepare("
            INSERT INTO products
                (sku, name, item_type, cost, uom_code, qty_on_hand, reorder_point,
                 vendor_part_number, preferred_vendor_name, purchase_description, is_active)
            VALUES (?,?,'raw_material',?,?,?,?,?,?,?,1)
        ")->execute([
            trim($data['sku'] ?? ''),
            trim($data['name'] ?? ''),
            ($data['cost'] ?? '') !== '' ? (float)$data['cost'] : null,
            trim($data['uom_code'] ?? '') ?: null,
            ($data['qty_on_hand'] ?? '') !== '' ? (float)$data['qty_on_hand'] : 0,
            ($data['reorder_point'] ?? '') !== '' ? (float)$data['reorder_point'] : null,
            trim($data['vendor_part_number'] ?? '') ?: null,
            trim($data['preferred_vendor_name'] ?? '') ?: null,
            trim($data['purchase_description'] ?? '') ?: null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public function getAllBrands(): array
    {
        return Database::select("SELECT id, name FROM product_brands ORDER BY name ASC");
    }

    public function update(int|string $id, array $data): int
    {
        // Keep the legacy retail_price column in step with price
        if (array_key_exists('price', $data) && !array_key_exists('retail_price', $data)) {
            $data['retail_price'] = $data['price'];
        }

        $set    = [];
        $params = [':id' => $id];

        foreach (self::EDITABLE as $col) {
            if (!array_key_exists($col, $data)) {
                continue;   // field wasn't on the submitted form — leave it alone
            }
            $raw = $data[$col];

            if (in_array($col, self::BOOLEAN_FIELDS, true)) {
                $val = (int)(bool)$raw;
            } elseif (in_array($col, self::DECIMAL_FIELDS, true)) {
                $val = ($raw === '' || $raw === null) ? null : (float)$raw;
            } elseif (in_array($col, self::INTEGER_FIELDS, true)) {
                $val = ($raw === '' || $raw === null) ? null : (int)$raw;
            } else {
                $val = is_string($raw) ? trim($raw) : $raw;
                if ($val === '' && !in_array($col, self::REQUIRED_FIELDS, true)) {
                    $val = null;
                }
            }

            $set[]           = "$col = :$col";
            $params[":$col"] = $val;
        }

        if (empty($set)) {
            return 0;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE products SET ' . implode(', ', $set) . ' WHERE id = :id'
        );
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // ---------------------------------------------------------------- Images

    public function getImages(int $productId): array
    {
        return Database::select("
            SELECT * FROM product_images
            WHERE product_id = ?
            ORDER BY is_primary DESC, sort_order ASC, id ASC
        ", [$productId]);
    }

    public function findImage(int $imageId, int $productId): array|false
    {
        return Database::selectOne(
            "SELECT * FROM product_images WHERE id = ? AND product_id = ? LIMIT 1",
            [$imageId, $productId]
        );
    }

    public function insertImage(array $data): int
    {
        $pdo = Database::connection();
        $pdo->prepare("
            INSERT INTO product_images
                (product_id, file_path, file_name, caption, is_primary, sort_order, uploaded_by)
            VALUES (:product_id, :file_path, :file_name, :caption, :is_primary, :sort_order, :uploaded_by)
        ")->execute([
            ':product_id'  => (int)$data['product_id'],
            ':file_path'   => $data['file_path'],
            ':file_name'   => $data['file_name']  ?? null,
            ':caption'     => ($data['caption']   ?? '') ?: null,
            ':is_primary'  => (int)($data['is_primary'] ?? 0),
            ':sort_order'  => (int)($data['sort_order'] ?? 0),
            ':uploaded_by' => $data['uploaded_by'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public function deleteImage(int $imageId, int $productId): void
    {
        Database::statement(
            "DELETE FROM product_images WHERE id = ? AND product_id = ?",
            [$imageId, $productId]
        );
    }

    public function setPrimaryImage(int $imageId, int $productId): void
    {
        Database::statement("UPDATE product_images SET is_primary = 0 WHERE product_id = ?", [$productId]);
        Database::statement(
            "UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?",
            [$imageId, $productId]
        );
    }

    public function countImages(int $productId): int
    {
        return (int)(Database::selectOne(
            "SELECT COUNT(*) AS c FROM product_images WHERE product_id = ?", [$productId]
        )['c'] ?? 0);
    }

    public function nextImageSort(int $productId): int
    {
        return (int)(Database::selectOne(
            "SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM product_images WHERE product_id = ?",
            [$productId]
        )['n'] ?? 1);
    }

    // ------------------------------------------------------------- Documents

    public function getDocuments(int $productId): array
    {
        return Database::select("
            SELECT * FROM product_documents
            WHERE product_id = ?
            ORDER BY sort_order ASC, doc_type ASC, id ASC
        ", [$productId]);
    }

    public function findDocument(int $docId, int $productId): array|false
    {
        return Database::selectOne(
            "SELECT * FROM product_documents WHERE id = ? AND product_id = ? LIMIT 1",
            [$docId, $productId]
        );
    }

    public function insertDocument(array $data): int
    {
        $pdo = Database::connection();
        $pdo->prepare("
            INSERT INTO product_documents
                (product_id, doc_type, title, file_path, file_name,
                 file_size, mime_type, is_public, uploaded_by)
            VALUES (:product_id, :doc_type, :title, :file_path, :file_name,
                    :file_size, :mime_type, :is_public, :uploaded_by)
        ")->execute([
            ':product_id'  => (int)$data['product_id'],
            ':doc_type'    => $data['doc_type'] ?? 'other',
            ':title'       => $data['title'],
            ':file_path'   => $data['file_path'],
            ':file_name'   => $data['file_name'] ?? null,
            ':file_size'   => $data['file_size'] ?? null,
            ':mime_type'   => $data['mime_type'] ?? null,
            ':is_public'   => (int)($data['is_public'] ?? 1),
            ':uploaded_by' => $data['uploaded_by'] ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public function deleteDocument(int $docId, int $productId): void
    {
        Database::statement(
            "DELETE FROM product_documents WHERE id = ? AND product_id = ?",
            [$docId, $productId]
        );
    }
}
