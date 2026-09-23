-- Correct the warehouses to what they are actually called.
--
-- Migration 057 seeded "Main Warehouse" and "730 Warehouse", which was a guess and wrong
-- on both counts. USSC has FOUR buildings and every one is known by its street address:
--
--   1000   the main warehouse
--   900
--   3085
--   730    the new one
--
-- Nobody says "Main Warehouse" or "730 Warehouse" — they say the number. Names here match
-- how people speak, because a location label that has to be translated in someone's head
-- is a label that gets picked wrong on a scanner.
--
-- The "main" distinction is kept as a note rather than a type. It matters for choosing a
-- sensible default later, but it is not a different kind of place.
--
-- NOTE no semicolons in these comments — database/migrate.php splits on them.

UPDATE stock_locations
SET code = '1000',
    name = '1000',
    notes = 'Main warehouse'
WHERE code = 'MAIN' AND location_type = 'warehouse';

UPDATE stock_locations
SET name = '730'
WHERE code = '730' AND location_type = 'warehouse';


-- The two that were never created.
INSERT INTO stock_locations (parent_id, code, name, location_type, sort_order)
SELECT NULL, '900', '900', 'warehouse', 2
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM stock_locations) x WHERE x.code = '900' AND x.location_type = 'warehouse');

INSERT INTO stock_locations (parent_id, code, name, location_type, sort_order)
SELECT NULL, '3085', '3085', 'warehouse', 3
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM stock_locations) x WHERE x.code = '3085' AND x.location_type = 'warehouse');

UPDATE stock_locations SET sort_order = 1 WHERE code = '1000' AND location_type = 'warehouse';
UPDATE stock_locations SET sort_order = 4 WHERE code = '730'  AND location_type = 'warehouse';


-- Each warehouse needs its own transit location, including the two just added, and the
-- renamed ones need their transit label corrected.
INSERT INTO stock_locations (parent_id, code, name, location_type, sort_order)
SELECT w.id, 'TRANSIT', CONCAT('In transit from ', w.name), 'transit', 99
FROM (SELECT * FROM stock_locations) w
WHERE w.location_type = 'warehouse'
  AND NOT EXISTS (
      SELECT 1 FROM (SELECT * FROM stock_locations) t
      WHERE t.parent_id = w.id AND t.location_type = 'transit'
  );

UPDATE stock_locations t
JOIN stock_locations w ON w.id = t.parent_id
SET t.name = CONCAT('In transit from ', w.name)
WHERE t.location_type = 'transit';
