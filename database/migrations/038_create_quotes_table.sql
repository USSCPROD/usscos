CREATE TABLE quotes (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    quote_number        VARCHAR(20)     NOT NULL UNIQUE,
    customer_id         INT UNSIGNED    NOT NULL,
    lead_id             INT UNSIGNED    NULL,
    opportunity_id      INT UNSIGNED    NULL,
    created_by          INT UNSIGNED    NOT NULL,
    rep_id              INT UNSIGNED    NULL,

    status              ENUM('draft','sent','accepted','declined','expired')
                        NOT NULL DEFAULT 'draft',

    quote_date          DATE            NOT NULL,
    expiry_date         DATE            NULL,
    accepted_date       DATE            NULL,

    po_number           VARCHAR(100)    NULL,
    payment_term_id     INT UNSIGNED    NULL,
    ship_via_id         INT UNSIGNED    NULL,
    tax_rate_id         INT UNSIGNED    NULL,

    ship_name           VARCHAR(150)    NULL,
    ship_address_1      VARCHAR(200)    NULL,
    ship_address_2      VARCHAR(200)    NULL,
    ship_city           VARCHAR(100)    NULL,
    ship_state          CHAR(2)         NULL,
    ship_zip            VARCHAR(20)     NULL,

    subtotal            DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    discount_amount     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    tax_amount          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    total_amount        DECIMAL(12,2)   NOT NULL DEFAULT 0.00,

    memo                TEXT            NULL,
    internal_notes      TEXT            NULL,

    -- Converted to SO
    sales_order_id      INT UNSIGNED    NULL,

    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_quote_customer  FOREIGN KEY (customer_id)    REFERENCES customers(id),
    CONSTRAINT fk_quote_created   FOREIGN KEY (created_by)     REFERENCES users(id),
    CONSTRAINT fk_quote_rep       FOREIGN KEY (rep_id)         REFERENCES users(id),
    CONSTRAINT fk_quote_term      FOREIGN KEY (payment_term_id) REFERENCES payment_terms(id),
    CONSTRAINT fk_quote_ship_via  FOREIGN KEY (ship_via_id)    REFERENCES ship_via(id),
    CONSTRAINT fk_quote_tax       FOREIGN KEY (tax_rate_id)    REFERENCES tax_rates(id),

    INDEX idx_quote_customer (customer_id),
    INDEX idx_quote_status   (status),
    INDEX idx_quote_date     (quote_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quote_line_items (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    quote_id        INT UNSIGNED    NOT NULL,
    product_id      INT UNSIGNED    NULL,
    quickbooks_item VARCHAR(100)    NULL,
    description     TEXT            NULL,
    qty             DECIMAL(10,3)   NOT NULL DEFAULT 1.000,
    uom_id          INT UNSIGNED    NULL,
    unit_price      DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    discount_pct    DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    is_taxable      TINYINT(1)      NOT NULL DEFAULT 0,
    line_total      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    sort_order      SMALLINT        NOT NULL DEFAULT 0,

    CONSTRAINT fk_qli_quote   FOREIGN KEY (quote_id)   REFERENCES quotes(id) ON DELETE CASCADE,
    CONSTRAINT fk_qli_product FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_qli_quote (quote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
