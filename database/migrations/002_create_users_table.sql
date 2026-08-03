-- Migration: 002_create_users_table
-- Description: Users table with roles and permissions

CREATE TABLE IF NOT EXISTS users (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id                  INT UNSIGNED        NOT NULL,
    first_name                  VARCHAR(100)        NOT NULL,
    last_name                   VARCHAR(100)        NOT NULL,
    email                       VARCHAR(255)        NOT NULL,
    password                    VARCHAR(255)        NOT NULL,
    role                        ENUM('owner','admin','manager','staff','readonly') NOT NULL DEFAULT 'staff',
    permissions                 JSON                NULL,
    is_active                   TINYINT(1)          NOT NULL DEFAULT 1,
    avatar                      VARCHAR(500)        NULL,
    phone                       VARCHAR(30)         NULL,
    title                       VARCHAR(100)        NULL,
    remember_token              VARCHAR(100)        NULL,
    remember_token_expires_at   DATETIME            NULL,
    password_reset_token        VARCHAR(100)        NULL,
    password_reset_expires_at   DATETIME            NULL,
    email_verified_at           DATETIME            NULL,
    last_login_at               DATETIME            NULL,
    last_login_ip               VARCHAR(45)         NULL,
    preferences                 JSON                NULL,
    created_at                  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at                  DATETIME            NULL,
    UNIQUE KEY uq_email (email),
    INDEX idx_company_id (company_id),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    INDEX idx_remember_token (remember_token),
    CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
