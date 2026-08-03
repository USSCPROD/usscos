-- Update users table: expand role enum and add rep fields
ALTER TABLE users
    MODIFY COLUMN role ENUM('owner','admin','bookkeeper','employee','rep','distributor')
        NOT NULL DEFAULT 'employee',
    ADD COLUMN commission_rate  DECIMAL(5,2) NULL AFTER role,
    ADD COLUMN rep_code         VARCHAR(20)  NULL AFTER commission_rate;
