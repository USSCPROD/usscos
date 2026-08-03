-- Invoices
-- Source: QuickBooks AR Aging + Open Invoices (invoice #s in the 280k-292k range observed)
-- Types seen: Invoice, Credit Memo, Payment
CREATE TABLE invoices (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id         INT UNSIGNED NOT NULL,
    invoice_number      VARCHAR(30)  NOT NULL UNIQUE,   -- QB Num field (e.g. 291480)
    po_number           VARCHAR(100) NULL,               -- customer PO # (P.O. # field)
    invoice_type        ENUM('invoice','credit_memo','estimate') NOT NULL DEFAULT 'invoice',
    status              ENUM('draft','sent','partial','paid','void','overdue') NOT NULL DEFAULT 'draft',

    invoice_date        DATE NOT NULL,
    due_date            DATE NOT NULL,
    payment_term_id     INT UNSIGNED NULL,

    -- Amounts (computed from line items but cached for fast reporting)
    subtotal            DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_amount          DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount_amount     DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_amount        DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_paid         DECIMAL(14,2) NOT NULL DEFAULT 0,
    balance_due         DECIMAL(14,2) NOT NULL DEFAULT 0,

    -- Shipping
    ship_date           DATE NULL,
    ship_via            VARCHAR(100) NULL,
    tracking_number     VARCHAR(100) NULL,
    ship_address_1      VARCHAR(255) NULL,
    ship_address_2      VARCHAR(255) NULL,
    ship_city           VARCHAR(100) NULL,
    ship_state          VARCHAR(50)  NULL,
    ship_zip            VARCHAR(20)  NULL,

    memo                TEXT NULL,
    internal_notes      TEXT NULL,

    -- AR aging fields (populated from QB aging report on import)
    aging_days          SMALLINT UNSIGNED NULL,

    -- QuickBooks sync
    quickbooks_id       VARCHAR(50) NULL UNIQUE,
    last_synced_at      TIMESTAMP NULL,

    created_by          INT UNSIGNED NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_invoices_customer FOREIGN KEY (customer_id)     REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_invoices_terms    FOREIGN KEY (payment_term_id) REFERENCES payment_terms (id) ON DELETE SET NULL,
    CONSTRAINT fk_invoices_user     FOREIGN KEY (created_by)      REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_invoices_customer     (customer_id),
    INDEX idx_invoices_date         (invoice_date),
    INDEX idx_invoices_due_date     (due_date),
    INDEX idx_invoices_status       (status),
    INDEX idx_invoices_balance      (balance_due),
    INDEX idx_invoices_aging        (aging_days)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
