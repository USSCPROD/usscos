-- Put the warehouses in the order people say them: 1000, 900, 3085, 730.
--
-- They were left in the order they happened to be created, which put 3085 first. That
-- is not cosmetic. sort_order decides which warehouse the location dropdowns offer
-- first, and it decides the fallback when stock has to be deducted for a product that
-- is recorded at no location at all - so the wrong order quietly sends the guesses to
-- the wrong building. 1000 is the main warehouse, so 1000 goes first.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE stock_locations SET sort_order = 1 WHERE code = '1000' AND parent_id IS NULL;
UPDATE stock_locations SET sort_order = 2 WHERE code = '900'  AND parent_id IS NULL;
UPDATE stock_locations SET sort_order = 3 WHERE code = '3085' AND parent_id IS NULL;
UPDATE stock_locations SET sort_order = 4 WHERE code = '730'  AND parent_id IS NULL;
