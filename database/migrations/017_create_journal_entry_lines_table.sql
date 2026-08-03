-- Journal Entry Lines (double-entry debits and credits)
-- Every journal entry has >= 2 lines; debits must equal credits
CREATE TABLE journal_entry_lines (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id    INT UNSIGNED NOT NULL,
    account_id          INT UNSIGNED NOT NULL,
    sort_order          SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    description         VARCHAR(500) NULL,   -- QB Memo / Split field
    debit               DECIMAL(14,2) NOT NULL DEFAULT 0,
    credit              DECIMAL(14,2) NOT NULL DEFAULT 0,

    -- Entity links (who this line relates to)
    customer_id         INT UNSIGNED NULL,
    vendor_id           INT UNSIGNED NULL,
    is_reconciled       TINYINT(1) NOT NULL DEFAULT 0,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_jel_entry    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id) ON DELETE CASCADE,
    CONSTRAINT fk_jel_account  FOREIGN KEY (account_id)       REFERENCES chart_of_accounts (id) ON DELETE RESTRICT,
    CONSTRAINT fk_jel_customer FOREIGN KEY (customer_id)      REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_jel_vendor   FOREIGN KEY (vendor_id)        REFERENCES vendors (id) ON DELETE SET NULL,

    INDEX idx_jel_entry    (journal_entry_id),
    INDEX idx_jel_account  (account_id),
    INDEX idx_jel_customer (customer_id),
    INDEX idx_jel_vendor   (vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
