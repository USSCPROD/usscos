-- Document categories and documents for the role-aware document library

CREATE TABLE IF NOT EXISTS document_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)                                                    NOT NULL,
    audience    ENUM('internal','reps','distributors','customers','all')        NOT NULL DEFAULT 'internal',
    sort_order  TINYINT UNSIGNED                                                NOT NULL DEFAULT 0,
    is_active   TINYINT(1)                                                      NOT NULL DEFAULT 1,
    created_at  DATETIME                                                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_category_id    INT UNSIGNED        NOT NULL,
    title                   VARCHAR(255)        NOT NULL,
    original_filename       VARCHAR(255)        NOT NULL,
    stored_filename         VARCHAR(255)        NOT NULL UNIQUE,
    file_size               INT UNSIGNED        NOT NULL DEFAULT 0,
    mime_type               VARCHAR(100)        NULL,
    description             TEXT                NULL,
    uploaded_by             INT UNSIGNED        NOT NULL,
    is_active               TINYINT(1)          NOT NULL DEFAULT 1,
    created_at              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_category FOREIGN KEY (document_category_id) REFERENCES document_categories(id),
    CONSTRAINT fk_doc_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_doc_category (document_category_id),
    INDEX idx_doc_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default categories
INSERT INTO document_categories (name, audience, sort_order) VALUES
    ('HR',                  'internal',     1),
    ('Operations',          'internal',     2),
    ('Training',            'internal',     3),
    ('Forms',               'internal',     4),
    ('Price Sheets',        'internal',     5),
    ('Sales Brochures',     'reps',         6),
    ('Marketing Materials', 'reps',         7),
    ('Rep Training',        'reps',         8),
    ('Dist Sales Brochures','distributors', 9),
    ('Dist Marketing',      'distributors', 10),
    ('Dist Training',       'distributors', 11),
    ('Dist Price Sheets',   'distributors', 12),
    ('TDS',                 'customers',    10),
    ('SDS',                 'customers',    11),
    ('Product Catalogs',    'customers',    12);
