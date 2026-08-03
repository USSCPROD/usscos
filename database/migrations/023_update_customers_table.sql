-- Add contact fields that QB customer records have but we didn't originally include
ALTER TABLE customers
    ADD COLUMN work_phone  VARCHAR(30)  NULL AFTER phone,
    ADD COLUMN mobile      VARCHAR(30)  NULL AFTER work_phone,
    ADD COLUMN cc_email    VARCHAR(254) NULL AFTER email;
