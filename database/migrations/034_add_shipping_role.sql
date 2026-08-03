-- Add shipping role to users table
ALTER TABLE users
    MODIFY COLUMN role ENUM('owner','admin','bookkeeper','manager','shipping','employee','rep','distributor','readonly')
    NOT NULL DEFAULT 'employee';
