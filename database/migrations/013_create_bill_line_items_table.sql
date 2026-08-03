-- Bill Line Items (AP)
CREATE TABLE bill_line_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bill_id         INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    description     TEXT NULL,
    qty             DECIMAL(12,4) NOT NULL DEFAULT 1,
    uom_id          INT UNSIGNED NULL,
    unit_cost       DECIMAL(12,4) NOT NULL DEFAULT 0,
    line_total      DECIMAL(14,2) NOT NULL DEFAULT 0,

    -- GL account for expense coding
    account_code    VARCHAR(20) NULL,

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_bill_lines_bill    FOREIGN KEY (bill_id)    REFERENCES bills (id) ON DELETE CASCADE,
    CONSTRAINT fk_bill_lines_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL,
    CONSTRAINT fk_bill_lines_uom     FOREIGN KEY (uom_id)     REFERENCES units_of_measure (id) ON DELETE SET NULL,

    INDEX idx_bill_lines_bill    (bill_id),
    INDEX idx_bill_lines_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
