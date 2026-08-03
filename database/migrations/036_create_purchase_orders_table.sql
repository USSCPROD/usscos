CREATE TABLE purchase_orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number           VARCHAR(50)  NOT NULL UNIQUE,
    vendor_id           INT UNSIGNED NOT NULL,
    status              ENUM('draft','sent','partial','received','closed','cancelled') NOT NULL DEFAULT 'draft',

    order_date          DATE NOT NULL,
    expected_date       DATE NULL,
    received_date       DATE NULL,

    ship_to_name        VARCHAR(255) NULL,
    ship_to_address_1   VARCHAR(255) NULL,
    ship_to_address_2   VARCHAR(255) NULL,
    ship_to_city        VARCHAR(100) NULL,
    ship_to_state       VARCHAR(50)  NULL,
    ship_to_zip         VARCHAR(20)  NULL,

    subtotal            DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_amount          DECIMAL(14,2) NOT NULL DEFAULT 0,
    shipping_cost       DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_amount        DECIMAL(14,2) NOT NULL DEFAULT 0,

    vendor_ref          VARCHAR(100) NULL,   -- vendor's confirmation/order number
    memo                TEXT NULL,
    internal_notes      TEXT NULL,

    bill_id             INT UNSIGNED NULL,   -- linked bill once received

    created_by          INT UNSIGNED NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_po_vendor  FOREIGN KEY (vendor_id)   REFERENCES vendors (id) ON DELETE RESTRICT,
    CONSTRAINT fk_po_bill    FOREIGN KEY (bill_id)     REFERENCES bills (id) ON DELETE SET NULL,
    CONSTRAINT fk_po_user    FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_po_vendor  (vendor_id),
    INDEX idx_po_status  (status),
    INDEX idx_po_date    (order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_order_lines (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id           INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    description     TEXT NULL,
    qty_ordered     DECIMAL(12,4) NOT NULL DEFAULT 1,
    qty_received    DECIMAL(12,4) NOT NULL DEFAULT 0,
    unit_cost       DECIMAL(12,4) NOT NULL DEFAULT 0,
    line_total      DECIMAL(14,2) NOT NULL DEFAULT 0,

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pol_po      FOREIGN KEY (po_id)       REFERENCES purchase_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_pol_product FOREIGN KEY (product_id)  REFERENCES products (id) ON DELETE SET NULL,

    INDEX idx_pol_po      (po_id),
    INDEX idx_pol_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
