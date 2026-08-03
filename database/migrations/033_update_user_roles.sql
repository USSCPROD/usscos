-- Fix user role ENUM to match roles used throughout the app
ALTER TABLE users
    MODIFY COLUMN role ENUM('owner','admin','bookkeeper','manager','employee','rep','distributor','readonly')
    NOT NULL DEFAULT 'employee';
