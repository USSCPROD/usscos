-- 5 gallon pails: 24 to a pallet.
--
-- A pail has no intermediate pack - it is not cased, it goes straight onto the pallet.
-- So units_per_case is 1, meaning the pail is its own pack, and pallet_qty is 24.
-- Base units on a pallet stays units_per_case * pallet_qty = 24 pails, so the same
-- arithmetic works for pails and for aerosol without a special case.
--
-- 1 is deliberate rather than NULL. NULL means "we do not know", 1 means "there is no
-- case", and those are different facts. Leaving it NULL would make receiving refuse to
-- suggest a pallet quantity, which is exactly wrong here - we know it is 24.
--
-- Matched where the unit of measure AND the name agree, which is 223 of the 227
-- products carrying a 5 gallon unit. The four that disagree are left alone and raised
-- with the user rather than guessed at - see the note at the end of this file.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products
SET units_per_case = 1,
    pallet_qty     = 24
WHERE uom_code IN ('5 Gal Pail', '5 Gal')
  AND (name LIKE '%5 gal%' OR name LIKE '%5-gal%' OR name LIKE '%pail%');

-- Left untouched, because the unit of measure says pail and the name does not:
--   DTM-SAND        Sand Aggregate, Each
--   DuraPaveLIQ     Self Curing, One Component Liquid Polyurethane Liquid
--   STP-WHT-NO AGG  Save the Planet White Sealer, No Aggregate
--   ASW             #1 Standard - White
