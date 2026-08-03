-- Migration: 001_create_companies_table
-- Description: Core company/tenant table

CREATE TABLE IF NOT EXISTS companies (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255)        NOT NULL,
    legal_name      VARCHAR(255)        NULL,
    slug            VARCHAR(100)        NOT NULL UNIQUE,
    industry        VARCHAR(100)        NULL,
    website         VARCHAR(255)        NULL,
    phone           VARCHAR(30)         NULL,
    email           VARCHAR(255)        NULL,
    address_line1   VARCHAR(255)        NULL,
    address_line2   VARCHAR(255)        NULL,
    city            VARCHAR(100)        NULL,
    state           VARCHAR(100)        NULL,
    postal_code     VARCHAR(20)         NULL,
    country         VARCHAR(100)        DEFAULT 'US',
    timezone        VARCHAR(60)         DEFAULT 'America/New_York',
    currency        VARCHAR(10)         DEFAULT 'USD',
    fiscal_year_end TINYINT UNSIGNED    DEFAULT 12 COMMENT 'Month number: 1-12',
    logo            VARCHAR(500)        NULL,
    settings        JSON                NULL,
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    trial_ends_at   DATETIME            NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME            NULL,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
