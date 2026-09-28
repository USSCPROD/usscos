-- iGo jug pack sizes, and the pallet quantity for the 2-pack cases.
--
-- Answered on 28 September 2026. The iGo answer is by size rather than by color: the
-- larger 2.6 gallon jug goes one to a box, the smaller 1.3 gallon jug goes two.
--
-- Applied to the whole iGo line rather than only the barcoded SKUs, as with
-- Direct-to-Metal in 081 - the answer was about how the product is packed, which does not
-- change depending on whether a barcode happens to be on file.
--
-- NOTE no semicolons in these comments. See the note in 054.

-- The larger jug: one per box.
UPDATE products
SET units_per_case = 1
WHERE sku LIKE 'AS-IGO%-2.6';

-- The smaller jug: two per box. Both spellings of the size are in use.
UPDATE products
SET units_per_case = 2
WHERE sku LIKE 'AS-IGO%-1.3' OR sku LIKE 'AS-IGO%-1.32';


-- 2-pack cases: 54 boxes to a pallet.
--
-- Which is the same 1,296 cans as 108 single cases, so a pallet of T-Tip holds the same
-- paint whichever way it is boxed. That arithmetic was suggestive but not evidence - it is
-- recorded here because somebody who stacks them confirmed it.
UPDATE products
SET pallet_qty = 54
WHERE sku IN ('DSW24', 'DSR24', 'DSRB24', 'ESW24');


-- Colorants are deliberately still open: the user set them aside for now. That is
--   BWBLACK, BWBROWN, BWRED, and the 1 Gal turf colorants TWACC-1G, TWCG-1G, TWOG-1G,
--   TWPIG-1G, TWSWG-1G, TWSHG-1G - nine products.
