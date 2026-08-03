-- Payment Terms
-- Derived from QuickBooks: "Net 30", "CREDIT CARD", etc.
CREATE TABLE payment_terms (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50)  NOT NULL UNIQUE,          -- e.g. "Net 30", "CREDIT CARD"
    days_due    TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- 30 for Net 30, 0 for due on receipt
    is_credit_card TINYINT(1) NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payment_terms (name, days_due, is_credit_card) VALUES
    ('Net 30',      30, 0),
    ('Net 15',      15, 0),
    ('Net 60',      60, 0),
    ('Due on Receipt', 0, 0),
    ('CREDIT CARD',  0, 1);
