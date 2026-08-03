CREATE TABLE sales_orders (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    so_number           VARCHAR(20)     NOT NULL UNIQUE,
    customer_id         INT UNSIGNED    NOT NULL,
    created_by          INT UNSIGNED    NOT NULL,
    rep_id              INT UNSIGNED    NULL,

    -- Status
    status              ENUM('draft','confirmed','processing','partially_shipped','shipped','invoiced','cancelled')
                        NOT NULL DEFAULT 'draft',

    -- Dates
    order_date          DATE            NOT NULL,
    requested_ship_date DATE            NULL,
    shipped_date        DATE            NULL,
    cancelled_date      DATE            NULL,

    -- References
    po_number           VARCHAR(100)    NULL,
    customer_message_id INT UNSIGNED    NULL,
    ship_via_id         INT UNSIGNED    NULL,
    tax_rate_id         INT UNSIGNED    NULL,

    -- Shipping address (override customer default)
    ship_name           VARCHAR(150)    NULL,
    ship_address_1      VARCHAR(200)    NULL,
    ship_address_2      VARCHAR(200)    NULL,
    ship_city           VARCHAR(100)    NULL,
    ship_state          CHAR(2)         NULL,
    ship_zip            VARCHAR(20)     NULL,

    -- Totals (computed by app, stored for speed)
    subtotal            DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    discount_amount     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    tax_amount          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    total_amount        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,

    memo                TEXT            NULL,
    internal_notes      TEXT            NULL,

    -- Converted to invoice
    invoice_id          INT UNSIGNED    NULL,

    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_so_customer   FOREIGN KEY (customer_id)         REFERENCES customers(id),
    CONSTRAINT fk_so_created_by FOREIGN KEY (created_by)          REFERENCES users(id),
    CONSTRAINT fk_so_rep        FOREIGN KEY (rep_id)              REFERENCES users(id),
    CONSTRAINT fk_so_ship_via   FOREIGN KEY (ship_via_id)         REFERENCES ship_via(id),
    CONSTRAINT fk_so_tax_rate   FOREIGN KEY (tax_rate_id)         REFERENCES tax_rates(id),
    CONSTRAINT fk_so_cust_msg   FOREIGN KEY (customer_message_id) REFERENCES customer_messages(id),
    CONSTRAINT fk_so_invoice    FOREIGN KEY (invoice_id)          REFERENCES invoices(id),

    INDEX idx_so_customer (customer_id),
    INDEX idx_so_status   (status),
    INDEX idx_so_order_date (order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-increment SO numbers: SO-000001, SO-000002 ...
-- The app will pad with leading zeros using LPAD.
