-- Payments (customer payments applied to invoices)
CREATE TABLE payments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id         INT UNSIGNED NOT NULL,
    payment_date        DATE NOT NULL,
    payment_method      ENUM('check','credit_card','ach','cash','wire','other') NOT NULL DEFAULT 'check',
    reference_number    VARCHAR(100) NULL,   -- check #, transaction ID, etc.
    amount              DECIMAL(14,2) NOT NULL,
    memo                TEXT NULL,

    -- QuickBooks sync
    quickbooks_id       VARCHAR(50) NULL UNIQUE,
    last_synced_at      TIMESTAMP NULL,

    created_by          INT UNSIGNED NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_payments_user     FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_payments_customer (customer_id),
    INDEX idx_payments_date     (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment applications (one payment can cover multiple invoices)
CREATE TABLE payment_applications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id      INT UNSIGNED NOT NULL,
    invoice_id      INT UNSIGNED NOT NULL,
    amount_applied  DECIMAL(14,2) NOT NULL,
    applied_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payapp_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE CASCADE,
    CONSTRAINT fk_payapp_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE RESTRICT,
    UNIQUE KEY uq_payment_invoice (payment_id, invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
