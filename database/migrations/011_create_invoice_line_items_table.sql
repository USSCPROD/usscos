-- Invoice Line Items
-- Source: QuickBooks Sales Detail report
-- Columns: Type, Date, Num, Memo, Name, Item (BRAND:SKU), Qty, U/M, Sales Price, Amount
CREATE TABLE invoice_line_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id      INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NULL,              -- NULL for non-inventory / free-text lines
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    -- Item details (denormalized for historical accuracy — prices change)
    quickbooks_item VARCHAR(255) NULL,              -- full QB item name at time of sale
    description     TEXT NULL,
    qty             DECIMAL(12,4) NOT NULL DEFAULT 1,
    uom_id          INT UNSIGNED NULL,
    unit_price      DECIMAL(12,4) NOT NULL DEFAULT 0,
    discount_pct    DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax_code        VARCHAR(20)  NULL,
    is_taxable      TINYINT(1) NOT NULL DEFAULT 0,
    line_total      DECIMAL(14,2) NOT NULL DEFAULT 0,  -- qty * unit_price * (1 - discount)

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_inv_lines_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE,
    CONSTRAINT fk_inv_lines_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL,
    CONSTRAINT fk_inv_lines_uom     FOREIGN KEY (uom_id)     REFERENCES units_of_measure (id) ON DELETE SET NULL,

    INDEX idx_inv_lines_invoice  (invoice_id),
    INDEX idx_inv_lines_product  (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
