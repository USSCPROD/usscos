-- The last five 12-can aerosol cases: 18 lb, like every other one.
--
-- Pro Cup and the retail 12-can packs were missed by 086 only because their names say
-- "12 Can/case" and "12 cans/case" rather than "(12 x 18 oz)". Same pack, same weight.
--
-- Matched on the pack rather than by listing the five SKUs, so anything else already in
-- the catalog with that pack and no weight is caught too.
--
-- This finishes aerosol: every 12-to-a-case, 108-to-a-pallet product now has a weight.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products
SET case_weight_gross = 18.0,
    weight_unit       = COALESCE(NULLIF(weight_unit, ''), 'LB')
WHERE units_per_case = 12
  AND pallet_qty = 108
  AND (case_weight_gross IS NULL OR case_weight_gross = 0);
