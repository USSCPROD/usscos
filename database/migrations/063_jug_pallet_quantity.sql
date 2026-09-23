-- 2.5 gallon jugs (Robo): 48 jugs per pallet, currently boxed 2 to a box.
--
-- The invariant is 48 JUGS on a pallet. How they are boxed is a packing decision that
-- is under review - the double boxes are heavy, and shipping three singles costs less
-- than shipping one double - so the boxing may change to 1 per box while the pallet
-- still holds 48 jugs.
--
--   today            units_per_case 2, pallet_qty 24  ->  48 jugs
--   if singles       units_per_case 1, pallet_qty 48  ->  48 jugs
--
-- Whoever makes that change must change BOTH columns together. Setting units_per_case
-- to 1 and leaving pallet_qty at 24 would quietly turn a pallet into 24 jugs, and a
-- receipt of one pallet would post half the paint that actually arrived. The arithmetic
-- that must hold either way is units_per_case * pallet_qty = 48.
--
-- Applies to every 2.5 gallon jug, because the pallet count is a property of the jug
-- rather than of what is in it. That is 80 Robo products plus PSSX-REM (Remover), which
-- ships in the same jug.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products
SET units_per_case = 2,
    pallet_qty     = 24
WHERE uom_code IN ('2.5 Gal Jug', '2.5 Gal');
