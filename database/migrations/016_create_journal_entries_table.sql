-- Journal Entries
-- Source: QuickBooks General Ledger
-- The GL shows: Type, Date, Num, Name, Memo, Clr (cleared), Split, Debit, Credit, Balance
CREATE TABLE journal_entries (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_number    VARCHAR(30)  NULL,           -- QB Num field
    entry_date      DATE NOT NULL,
    entry_type      ENUM(
                        'invoice',
                        'payment',
                        'bill',
                        'bill_payment',
                        'general',
                        'deposit',
                        'check',
                        'credit_memo',
                        'transfer'
                    ) NOT NULL DEFAULT 'general',

    -- Source document link (polymorphic)
    source_type     ENUM('invoice','bill','payment','adjustment') NULL,
    source_id       INT UNSIGNED NULL,

    description     TEXT NULL,
    memo            TEXT NULL,
    is_reconciled   TINYINT(1) NOT NULL DEFAULT 0,   -- QB Clr field

    -- QuickBooks sync
    quickbooks_id   VARCHAR(50) NULL UNIQUE,
    last_synced_at  TIMESTAMP NULL,

    created_by      INT UNSIGNED NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_je_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_je_date   (entry_date),
    INDEX idx_je_type   (entry_type),
    INDEX idx_je_source (source_type, source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
