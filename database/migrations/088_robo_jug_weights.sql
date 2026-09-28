-- Robo 2.5 gallon jug weights, per jug.
--
--   Removable            ROBOREM   35 lb
--   RoboChalk            ROBOCH    32 lb
--   Concentrate          ROBOCON   31 lb
--   Ready-to-Spray       ROBORTS   29 lb
--
-- These are heavier than the aerosol by a long way, and they explain the decision to stop
-- double-boxing on their own: two jugs in a box is 58 to 70 lb, which is an awkward lift
-- and an expensive parcel. One jug is a comfortable package.
--
-- case_weight_gross is the weight of ONE PACK, and a Robo pack is currently 2 jugs - so
-- the per-jug figure is doubled here. If the boxing moves to singles this must be halved
-- in the same change that sets units_per_case to 1, or the pallet arithmetic breaks in
-- two places at once.
--
-- STILL UNWEIGHED in this family and deliberately untouched:
--   ROBOHS   Hard Surface, 6 products in two SKU spellings
--   PSSX-REM Remover, which is not a Robo paint at all
--
-- RoboTraffic was given as 29 lb per jug but no product in the catalog matches it - the
-- only 'traffic' products are thermoplastic primers in 5 gallon pails. Either it is not
-- in the catalog yet or it is called something else here.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products SET case_weight_gross = 70.0, weight_unit = COALESCE(NULLIF(weight_unit,''), 'LB')
WHERE units_per_case = 2 AND pallet_qty = 24 AND sku LIKE 'ROBOREM%';

UPDATE products SET case_weight_gross = 64.0, weight_unit = COALESCE(NULLIF(weight_unit,''), 'LB')
WHERE units_per_case = 2 AND pallet_qty = 24 AND sku LIKE 'ROBOCH%';

UPDATE products SET case_weight_gross = 62.0, weight_unit = COALESCE(NULLIF(weight_unit,''), 'LB')
WHERE units_per_case = 2 AND pallet_qty = 24 AND sku LIKE 'ROBOCON%';

UPDATE products SET case_weight_gross = 58.0, weight_unit = COALESCE(NULLIF(weight_unit,''), 'LB')
WHERE units_per_case = 2 AND pallet_qty = 24 AND sku LIKE 'ROBORTS%';
