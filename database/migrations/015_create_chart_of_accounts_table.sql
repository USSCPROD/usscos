-- Chart of Accounts
-- Source: QuickBooks General Ledger (account hierarchy observed: ASSETS > Current Assets > Checking/Savings etc.)
CREATE TABLE chart_of_accounts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id       INT UNSIGNED NULL,
    account_code    VARCHAR(20)  NULL UNIQUE,        -- numeric code (optional, QB doesn't always use)
    name            VARCHAR(255) NOT NULL,
    account_type    ENUM(
                        'bank',
                        'accounts_receivable',
                        'other_current_asset',
                        'fixed_asset',
                        'other_asset',
                        'accounts_payable',
                        'credit_card',
                        'other_current_liability',
                        'long_term_liability',
                        'equity',
                        'income',
                        'cost_of_goods_sold',
                        'expense',
                        'other_income',
                        'other_expense'
                    ) NOT NULL,
    account_subtype VARCHAR(100) NULL,               -- e.g. "Checking/Savings" from QB hierarchy
    normal_balance  ENUM('debit','credit') NOT NULL, -- debit-normal or credit-normal
    is_bank_account TINYINT(1) NOT NULL DEFAULT 0,
    bank_account_number VARCHAR(50) NULL,
    description     TEXT NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,

    -- QuickBooks sync
    quickbooks_id   VARCHAR(50) NULL UNIQUE,
    last_synced_at  TIMESTAMP NULL,

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_coa_parent FOREIGN KEY (parent_id) REFERENCES chart_of_accounts (id) ON DELETE SET NULL,

    INDEX idx_coa_type   (account_type),
    INDEX idx_coa_parent (parent_id),
    INDEX idx_coa_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
