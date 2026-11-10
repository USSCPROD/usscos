-- A stocking distributor is its own kind of account.
--
-- One who holds stock buys differently from one who does not, and the price list has a
-- column for it (products.stocking_dist) that nothing could ever reach because there
-- was no customer type to attach it to.
--
-- 'School' is left alone. It is not in the four Chip named, but it is a real type in
-- this table and deleting a classification is not the same as not offering it.

INSERT INTO customer_types (name, sort_order, is_active)
SELECT 'Stocking Distributor', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM customer_types ct WHERE ct.name = 'Stocking Distributor');

UPDATE customer_types SET sort_order = 1 WHERE name = 'Retail';
UPDATE customer_types SET sort_order = 2 WHERE name = 'Distributor';
UPDATE customer_types SET sort_order = 4 WHERE name = 'Rep';
UPDATE customer_types SET sort_order = 5 WHERE name = 'School';
