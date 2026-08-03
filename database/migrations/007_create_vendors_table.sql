-- Vendors
-- Source: QuickBooks Vendor List (269 records)
-- Fields: Vendor, Account No., Bill from, Primary Contact, Main Phone, Fax, Balance Total
CREATE TABLE vendors (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quickbooks_name     VARCHAR(255) NOT NULL,
    company_name        VARCHAR(255) NOT NULL,
    first_name          VARCHAR(100) NULL,
    last_name           VARCHAR(100) NULL,
    email               VARCHAR(255) NULL,
    phone               VARCHAR(30)  NULL,
    fax                 VARCHAR(30)  NULL,
    account_number      VARCHAR(50)  NULL,                    -- our account # with vendor

    -- Remit-to address
    address_1           VARCHAR(255) NULL,
    address_2           VARCHAR(255) NULL,
    city                VARCHAR(100) NULL,
    state               VARCHAR(50)  NULL,
    zip                 VARCHAR(20)  NULL,
    country             VARCHAR(100) NULL DEFAULT 'US',

    payment_term_id     INT UNSIGNED NULL,
    tax_id              VARCHAR(30)  NULL,                    -- EIN / 1099 tracking
    is_1099             TINYINT(1) NOT NULL DEFAULT 0,

    -- QuickBooks sync
    quickbooks_id       VARCHAR(50)  NULL UNIQUE,
    qb_balance          DECIMAL(12,2) NULL,
    last_synced_at      TIMESTAMP NULL,

    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    notes               TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_vendors_terms FOREIGN KEY (payment_term_id) REFERENCES payment_terms (id) ON DELETE SET NULL,

    INDEX idx_vendors_company  (company_name),
    INDEX idx_vendors_qb_name  (quickbooks_name),
    INDEX idx_vendors_active   (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
