-- Pack sizes for products that carry a case barcode but had none recorded.
--
-- 52 products scanned as a case without the system knowing what a case held, so a scan at
-- the bench could identify the product but not offer a quantity. These are the 30 answered
-- on 28 September 2026. The remaining 22 are listed at the end, still open.
--
-- units_per_case is BASE UNITS in one pack, as defined in 054 - cans in a box, not boxes.
--
-- NOTE no semicolons in these comments. See the note in 054.

-- T-Tip 2-pack cases: one box holds 2 cases of 12 cans, so 24 cans to the box.
UPDATE products
SET units_per_case = 24
WHERE sku IN ('DSW24', 'DSR24', 'DSRB24', 'ESW24');

-- Striping machines, wands and CurbHuggers: a single box each, no paint included.
-- Several of these carry a uom_code of "18 oz Can", which is what they SPRAY rather than
-- how they are packed - a good example of why uom_code is not trusted for packing.
UPDATE products
SET units_per_case = 1
WHERE sku IN ('SS10', 'SS8', 'SSAT', 'SSATWHDL', 'SS2CAT',
              'WANDHG', 'WANDLG UMA',
              'CURB10B', 'CURB10R', 'CURB10Y');

-- 55 gallon drums ship singly.
UPDATE products
SET units_per_case = 1
WHERE sku IN ('TWACC-55G', 'TWOG-55G', 'TWPIG-55G', 'TWSWG-55G', 'TWSHG-55G');

-- Direct-to-Metal: one gallon per box.
UPDATE products
SET units_per_case = 1
WHERE sku LIKE 'DTM%-1G';

-- The #10B Elite concentrate goes two gallons to a box.
UPDATE products
SET units_per_case = 2
WHERE sku = 'ASW10BEZ 1G PAIL';


-- STILL OPEN, and deliberately left alone rather than guessed at:
--
--   Colorants, 1 Gal            BWBLACK, BWBROWN, BWRED                     3 products
--   Turf Colorant, 1 Gal        TWACC-1G, TWCG-1G, TWOG-1G,
--                               TWPIG-1G, TWSWG-1G, TWSHG-1G                6 products
--   iGo machine paint jugs      every AS-IGO SKU at 1.32 and 2.6 gal       13 products
--
-- Also open: the pallet quantity for the 2-pack cases. The arithmetic suggests 54 boxes -
-- the same 1,296 cans as 108 single cases - but whether 54 of the larger boxes physically
-- fit a pallet is a question for somebody who stacks them, not for arithmetic.
