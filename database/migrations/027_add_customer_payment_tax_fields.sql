-- Add payment preferences, card-on-file, tax item, resale number, and assigned rep to customers
ALTER TABLE customers
    ADD COLUMN preferred_delivery_method  ENUM('email','mail','none') NULL          AFTER cc_email,
    ADD COLUMN preferred_payment_method   ENUM('cash','check','credit_card') NULL   AFTER preferred_delivery_method,
    ADD COLUMN cc_number                  VARCHAR(20)  NULL                          AFTER preferred_payment_method,
    ADD COLUMN cc_exp_date                VARCHAR(7)   NULL                          AFTER cc_number,
    ADD COLUMN cc_name                    VARCHAR(100) NULL                          AFTER cc_exp_date,
    ADD COLUMN cc_billing_address         VARCHAR(255) NULL                          AFTER cc_name,
    ADD COLUMN cc_billing_zip             VARCHAR(20)  NULL                          AFTER cc_billing_address,
    ADD COLUMN tax_rate_id                INT UNSIGNED NULL                          AFTER sales_tax_code,
    ADD COLUMN resale_number              VARCHAR(50)  NULL                          AFTER tax_rate_id,
    ADD COLUMN rep_id                     INT UNSIGNED NULL                          AFTER resale_number,
    ADD COLUMN ship_company               VARCHAR(255) NULL                          AFTER ship_country,
    ADD COLUMN ship_contact               VARCHAR(200) NULL                          AFTER ship_company,
    ADD COLUMN ship_phone                 VARCHAR(30)  NULL                          AFTER ship_contact,
    ADD CONSTRAINT fk_customers_tax_rate  FOREIGN KEY (tax_rate_id) REFERENCES tax_rates (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_customers_rep       FOREIGN KEY (rep_id)      REFERENCES users (id)      ON DELETE SET NULL;
