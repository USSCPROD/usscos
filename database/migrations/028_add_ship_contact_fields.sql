-- Add company name, contact name, and phone to shipping address on customers
ALTER TABLE customers
    ADD COLUMN ship_company  VARCHAR(255) NULL AFTER ship_country,
    ADD COLUMN ship_contact  VARCHAR(200) NULL AFTER ship_company,
    ADD COLUMN ship_phone    VARCHAR(30)  NULL AFTER ship_contact;
