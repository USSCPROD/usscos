-- Distributors table
CREATE TABLE distributors (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    account_number VARCHAR(50)  NULL,
    contact_name   VARCHAR(150) NULL,
    phone          VARCHAR(30)  NULL,
    email          VARCHAR(255) NULL,
    address_1      VARCHAR(255) NULL,
    address_2      VARCHAR(255) NULL,
    city           VARCHAR(100) NULL,
    state          CHAR(2)      NULL,
    zip            VARCHAR(20)  NULL,
    notes          TEXT         NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link rep and distributor to customers
ALTER TABLE customers
    ADD COLUMN rep_id          INT UNSIGNED NULL AFTER account_number,
    ADD COLUMN distributor_id  INT UNSIGNED NULL AFTER rep_id,
    ADD CONSTRAINT fk_customers_rep         FOREIGN KEY (rep_id)         REFERENCES users(id)         ON DELETE SET NULL,
    ADD CONSTRAINT fk_customers_distributor FOREIGN KEY (distributor_id) REFERENCES distributors(id)  ON DELETE SET NULL,
    ADD INDEX idx_customers_rep         (rep_id),
    ADD INDEX idx_customers_distributor (distributor_id);
