-- Distributors, and the link from a customer to its distributor.
--
-- HISTORY: this migration never ran. It was written to add both `rep_id` and
-- `distributor_id` to customers, but migration 027 also adds `rep_id` — and 027 did run.
-- So 026 failed on "Duplicate column name 'rep_id'" and was skipped, silently leaving
-- the distributors table missing. Rewritten here to own only what 027 doesn't: the
-- distributors table and `customers.distributor_id`.
--
-- Idempotent, so it is safe to run against a database where parts already exist.

CREATE TABLE IF NOT EXISTS distributors (
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
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_distributors_active (is_active),
    INDEX idx_distributors_name   (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- A customer may buy through more than one distributor, so the link is many-to-many.
-- `customers.distributor_id` below records the primary one for quick filtering.
CREATE TABLE IF NOT EXISTS customer_distributor_links (
    customer_id    INT UNSIGNED NOT NULL,
    distributor_id INT UNSIGNED NOT NULL,
    is_primary     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (customer_id, distributor_id),
    CONSTRAINT fk_cdl_customer    FOREIGN KEY (customer_id)    REFERENCES customers (id)    ON DELETE CASCADE,
    CONSTRAINT fk_cdl_distributor FOREIGN KEY (distributor_id) REFERENCES distributors (id) ON DELETE CASCADE,
    INDEX idx_cdl_distributor (distributor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- customers.distributor_id for the primary distributor.
--
-- NOTE rep_id is deliberately not touched here. Migration 027 owns that column, which is
-- what made the original version of this file fail.
--
-- Not guarded with an existence check, because database/migrate.php splits files on
-- semicolons without stripping comments, so a PREPARE block is fragile. Re-running is
-- prevented by the migrations tracking table instead.
ALTER TABLE customers
    ADD COLUMN distributor_id INT UNSIGNED NULL AFTER rep_id,
    ADD CONSTRAINT fk_customers_distributor FOREIGN KEY (distributor_id)
        REFERENCES distributors (id) ON DELETE SET NULL,
    ADD INDEX idx_customers_distributor (distributor_id);
