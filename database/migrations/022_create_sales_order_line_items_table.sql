CREATE TABLE sales_order_line_items (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    sales_order_id  INT UNSIGNED    NOT NULL,
    product_id      INT UNSIGNED    NULL,
    quickbooks_item VARCHAR(100)    NULL,
    description     VARCHAR(500)    NULL,
    qty_ordered     DECIMAL(12,4)   NOT NULL DEFAULT 1.0000,
    qty_shipped     DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    uom_id          INT UNSIGNED    NULL,
    unit_price      DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    discount_pct    DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    taxable         TINYINT(1)      NOT NULL DEFAULT 0,
    line_total      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    CONSTRAINT fk_soli_so      FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_soli_product FOREIGN KEY (product_id)     REFERENCES products(id),
    CONSTRAINT fk_soli_uom     FOREIGN KEY (uom_id)         REFERENCES units_of_measure(id),

    INDEX idx_soli_so (sales_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
