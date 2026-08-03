-- Add default system settings to companies table
ALTER TABLE companies
    ADD COLUMN default_tax_rate_id      INT UNSIGNED NULL AFTER postal_code,
    ADD COLUMN default_payment_term_id  INT UNSIGNED NULL AFTER default_tax_rate_id,
    ADD COLUMN default_ship_via_id      INT UNSIGNED NULL AFTER default_payment_term_id;
