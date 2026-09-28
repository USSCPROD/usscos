-- Every 18 oz aerosol case weighs 18 lb, whatever is printed on the can.
--
-- 084 set the weight for DuraStripe and EcoStripe only, because those were the two brands
-- named. Confirmed since: the weight is a property of twelve 18 oz cans in a box rather
-- than of the brand, so it applies to the UMA-Tip, StripEx and Tournament cases too.
--
-- Matched on the PACK rather than on a list of SKU prefixes - 12 to a case, 108 cases to a
-- pallet, an 18 oz case in the name. Anything that is that pack is 18 lb, including
-- whatever gets added next year by somebody who never reads this file.
--
-- The Fat Cans are excluded by pallet_qty: they are 75 to a pallet, not 108, and were
-- weighed at 26 lb in 085.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products
SET case_weight_gross = 18.0,
    weight_unit       = COALESCE(NULLIF(weight_unit, ''), 'LB')
WHERE units_per_case = 12
  AND pallet_qty = 108
  AND name LIKE '%18 oz)%'
  AND (case_weight_gross IS NULL OR case_weight_gross = 0);
