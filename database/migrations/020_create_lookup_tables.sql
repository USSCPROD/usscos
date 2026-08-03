-- Ship Via options
CREATE TABLE ship_via (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ship_via (name, sort_order) VALUES
    ('UPS',              1),
    ('FedEx',            2),
    ('FedEx Freight',    3),
    ('Freight',          4),
    ('Customer Pick-Up', 5),
    ('Will Call',        6),
    ('USPS',             7),
    ('Other',            8);

-- Customer messages (invoice/SO footer messages)
CREATE TABLE customer_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message    TEXT NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO customer_messages (message, sort_order) VALUES
    ('Thank you for your business!', 1),
    ('Payment due upon receipt.',    2),
    ('Please remit payment by due date to avoid late fees.', 3);

-- Tax rates
CREATE TABLE tax_rates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,  -- e.g. "GA - Forsyth County"
    state_code  CHAR(2)      NOT NULL,
    rate        DECIMAL(5,4) NOT NULL,         -- 0.0700 = 7%
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tax_rates (name, state_code, rate) VALUES
    ('GA - Forsyth County', 'GA', 0.0700),
    ('NC',                  'NC', 0.0825),
    ('Out of State',        '',   0.0000);
