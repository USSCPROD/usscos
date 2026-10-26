-- A role for warehouse floor staff, distinct from 'shipping'.
--
-- 'shipping' already means the shipping department: it carries inventory-adjustment
-- rights and belongs to the shipping manager, who also enters orders and needs most
-- of the application. Restricting that role to three screens would have locked her
-- out of her own job.
--
-- 'warehouse' is the narrow one - the pull list, shipping and receiving, nothing
-- else - for the people working from a shared tablet at the bench.
--
-- Placed after 'shipping' in the enum so the list reads from widest to narrowest.

ALTER TABLE users
    MODIFY COLUMN role
    ENUM('owner','admin','bookkeeper','manager','shipping','warehouse','employee','rep','distributor','readonly')
    NOT NULL DEFAULT 'employee';
