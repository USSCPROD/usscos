-- Units of Measure
-- Derived from QuickBooks U/M column: CS (case), EA (each), 12PK, etc.
CREATE TABLE units_of_measure (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20)  NOT NULL UNIQUE,   -- CS, EA, 12PK
    label       VARCHAR(50)  NOT NULL,          -- Case, Each, 12-Pack
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO units_of_measure (code, label) VALUES
    ('CS',    'Case'),
    ('EA',    'Each'),
    ('12PK',  '12-Pack'),
    ('BX',    'Box'),
    ('PL',    'Pallet'),
    ('GAL',   'Gallon'),
    ('QT',    'Quart'),
    ('LB',    'Pound');
