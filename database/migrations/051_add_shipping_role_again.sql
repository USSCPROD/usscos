-- Add the `shipping` role to users.role.
--
-- HISTORY: migration 034 was written to do exactly this and never took effect. The
-- migrations tracking table was later reconciled by checking that expected TABLES
-- existed, which cannot detect a missed ALTER, so 034 was recorded as run while the
-- enum still lacked `shipping`. The Add/Edit User form offers Shipping as a choice,
-- so under STRICT_TRANS_TABLES saving a user with that role failed outright.
--
-- Re-running is prevented by the migrations tracking table. Restating the whole enum
-- rather than a conditional ALTER keeps this to one statement, because
-- database/migrate.php splits files on semicolons without stripping comments.

ALTER TABLE users
    MODIFY COLUMN role ENUM('owner','admin','bookkeeper','manager','shipping','employee','rep','distributor','readonly')
    NOT NULL DEFAULT 'employee';
