-- A login name for people who have no email address.
--
-- Warehouse staff sign in on a shared tablet at the shipping bench. Requiring an
-- email address meant inventing one per person and asking them to type it, which is
-- slower, easy to mistype, and tells somebody who has no company email that the
-- system thinks they do.
--
-- Nullable and unique: office staff keep signing in with their email and have no
-- username at all. Two users may not share one, but any number may have none, which
-- is what a nullable unique index gives in MySQL.
--
-- email stays NOT NULL. Too much of the application reads it, and a warehouse
-- account is given a placeholder address that is never written to. Nothing is sent
-- to it and nobody types it.

ALTER TABLE users
    ADD COLUMN username VARCHAR(50) NULL AFTER email;

CREATE UNIQUE INDEX uq_users_username ON users (username);
