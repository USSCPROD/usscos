-- Customers
-- Source: QuickBooks Customer List (31,584 records)
-- Fields: Customer name, Bill-to address, Primary Contact, Main Phone, Fax, Balance Total
-- Note: QuickBooks stores parent:child customer names (e.g. "SPORTS INC:HAUFF SPORTS")
CREATE TABLE customers (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id           INT UNSIGNED NULL,                    -- for QB sub-customers (PARENT:CHILD)
    quickbooks_name     VARCHAR(255) NOT NULL,                -- full QB name including parent prefix
    company_name        VARCHAR(255) NOT NULL,
    first_name          VARCHAR(100) NULL,
    last_name           VARCHAR(100) NULL,
    email               VARCHAR(255) NULL,
    phone               VARCHAR(30)  NULL,
    fax                 VARCHAR(30)  NULL,
    account_number      VARCHAR(50)  NULL,

    -- Billing address (stored flat from QB export)
    bill_address_1      VARCHAR(255) NULL,
    bill_address_2      VARCHAR(255) NULL,
    bill_city           VARCHAR(100) NULL,
    bill_state          VARCHAR(50)  NULL,
    bill_zip            VARCHAR(20)  NULL,
    bill_country        VARCHAR(100) NULL DEFAULT 'US',

    -- Shipping address
    ship_address_1      VARCHAR(255) NULL,
    ship_address_2      VARCHAR(255) NULL,
    ship_city           VARCHAR(100) NULL,
    ship_state          VARCHAR(50)  NULL,
    ship_zip            VARCHAR(20)  NULL,
    ship_country        VARCHAR(100) NULL DEFAULT 'US',

    payment_term_id     INT UNSIGNED NULL,
    credit_limit        DECIMAL(12,2) NULL,
    tax_exempt          TINYINT(1) NOT NULL DEFAULT 0,
    sales_tax_code      VARCHAR(20)  NULL,                    -- QB tax code (Tax/Non)

    -- QuickBooks sync
    quickbooks_id       VARCHAR(50)  NULL UNIQUE,
    qb_balance          DECIMAL(12,2) NULL,                   -- last known QB balance
    last_synced_at      TIMESTAMP NULL,

    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    notes               TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_customers_parent   FOREIGN KEY (parent_id)        REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_customers_terms    FOREIGN KEY (payment_term_id)  REFERENCES payment_terms (id) ON DELETE SET NULL,

    INDEX idx_customers_company    (company_name),
    INDEX idx_customers_qb_name    (quickbooks_name),
    INDEX idx_customers_email      (email),
    INDEX idx_customers_phone      (phone),
    INDEX idx_customers_active     (is_active),
    INDEX idx_customers_parent     (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
