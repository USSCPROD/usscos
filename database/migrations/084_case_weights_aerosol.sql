-- Case weights: DuraStripe and EcoStripe, 18 lb per case.
--
-- The first real weights in the system. Not one product had a case weight before this,
-- and FedEx will not produce a label without one - so this is what turns the label work
-- from proven-in-sandbox into something that could actually print.
--
-- 18 lb is a sane figure for the pack: 12 cans at 18 oz is 13.5 lb of product, and the
-- cans and the box make up the rest.
--
-- SCOPED BY SKU PREFIX, NOT BY PRODUCT NAME. The names describe the applicator - T-Tip,
-- UMA-Tip, Fat Can - while the brand lives in the SKU. DS is DuraStripe and ES or ECO is
-- EcoStripe.
--
-- ONLY THE 18 OZ CASES. The Fat Cans are DS SKUs too but they are 12 x 26 oz, which is
-- half as much paint again, so they are left alone rather than given a weight that is
-- knowably wrong.
--
-- The UMA (UDS, UES), StripEx (SX) and Tournament (SOT, SOA) cases are also 12 x 18 oz and
-- are very likely the same 18 lb - but they were not named, and a weight is a number
-- somebody pays freight on, so they are left for an answer rather than inferred.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products
SET case_weight_gross = 18.0,
    weight_unit       = COALESCE(NULLIF(weight_unit, ''), 'LB')
WHERE units_per_case = 12
  AND pallet_qty = 108
  AND name LIKE '%18 oz)%'
  AND (sku LIKE 'DS%' OR sku LIKE 'ES%' OR sku LIKE 'ECO%');
