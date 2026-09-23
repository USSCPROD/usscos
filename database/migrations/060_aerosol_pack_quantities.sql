-- Aerosol pack quantities: 12 cans per case, 108 cases per pallet of one colour.
--
-- Migration 054 defined these columns but deliberately left them empty, waiting for
-- real numbers. These are the real numbers for aerosol, and they are corroborated by
-- QuickBooks itself, which carries pallet SKUs named "108 CASE PALLET WHITE" and so on.
--
-- So a full pallet of one colour is 12 x 108 = 1,296 cans, and receiving one pallet
-- posts 1,296 cans rather than 1.
--
-- Matching is on the product NAME, not the unit of measure. uom_code is unreliable
-- here - the striping machines carry "18 oz Can" because that is what they spray, and
-- the tire cleaners carry "Case (12)" while actually being four gallons per case.
-- The name states the pack outright, which is the stronger signal.
--
-- NOTE no semicolons in these comments. See the note in 054.

-- 1) The 18 oz aerosol cases - the main catalogue, about 147 products.
UPDATE products
SET units_per_case = 12,
    pallet_qty     = 108
WHERE name LIKE '%Case (12 %18 oz)%'
   OR name LIKE '%12 Can/case%'
   OR name LIKE '%12 cans/case%';

-- 2) The 26 oz Fat Cans are also 12 to a case, but a taller can does not fit a pallet
--    the same way and nobody has given us that figure. Set what we know and leave the
--    pallet quantity NULL, so receiving offers no pallet suggestion rather than
--    offering a wrong one with the same confidence as a right one.
UPDATE products
SET units_per_case = 12
WHERE name LIKE '%Case (12 %26 oz)%';
