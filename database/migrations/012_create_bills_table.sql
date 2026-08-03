-- Bills (Accounts Payable)
-- Source: QuickBooks AP Aging (2,518 rows)
-- Columns: Type, Date, Num, Name (vendor), Due Date, Aging, Open Balance
CREATE TABLE bills (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id           INT UNSIGNED NOT NULL,
    bill_number         VARCHAR(50)  NULL,               -- vendor's invoice/ref #
    our_reference       VARCHAR(50)  NULL,               -- our PO or ref #
    bill_type           ENUM('bill','credit','expense') NOT NULL DEFAULT 'bill',
    status              ENUM('draft','approved','partial','paid','void','overdue') NOT NULL DEFAULT 'draft',

    bill_date           DATE NOT NULL,
    due_date            DATE NOT NULL,
    payment_term_id     INT UNSIGNED NULL,

    subtotal            DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_amount          DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_amount        DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_paid         DECIMAL(14,2) NOT NULL DEFAULT 0,
    balance_due         DECIMAL(14,2) NOT NULL DEFAULT 0,

    memo                TEXT NULL,
    internal_notes      TEXT NULL,

    aging_days          SMALLINT UNSIGNED NULL,

    -- QuickBooks sync
    quickbooks_id       VARCHAR(50) NULL UNIQUE,
    last_synced_at      TIMESTAMP NULL,

    created_by          INT UNSIGNED NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_bills_vendor  FOREIGN KEY (vendor_id)       REFERENCES vendors (id) ON DELETE RESTRICT,
    CONSTRAINT fk_bills_terms   FOREIGN KEY (payment_term_id) REFERENCES payment_terms (id) ON DELETE SET NULL,
    CONSTRAINT fk_bills_user    FOREIGN KEY (created_by)      REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_bills_vendor   (vendor_id),
    INDEX idx_bills_date     (bill_date),
    INDEX idx_bills_due_date (due_date),
    INDEX idx_bills_status   (status),
    INDEX idx_bills_balance  (balance_due)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
