-- Migration: 041_create_departments_table
-- Description: Departments for user classification and lead routing

CREATE TABLE IF NOT EXISTS departments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)    NOT NULL,
    description VARCHAR(255)    NULL,
    is_active   TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order  INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO departments (name, description, sort_order) VALUES
    ('Management',          'Owners and managers',              1),
    ('Stencil Sales',       'Stencil sales and quoting',        2),
    ('Paint Sales',         'Paint and coatings sales',         3),
    ('Production',          'Manufacturing and production',     4),
    ('Shipping',            'Shipping and fulfillment',         5),
    ('Bookkeeping',         'Accounting and bookkeeping',       6),
    ('Customer Service',    'Customer support',                 7);

ALTER TABLE users ADD COLUMN department_id INT UNSIGNED NULL AFTER role,
    ADD CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;
