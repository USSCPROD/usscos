-- Fat Can pallet quantity: 75 cases per pallet.
--
-- Migration 060 set 12 cans per case for the 26 oz Fat Cans but left the pallet
-- quantity empty, because a taller can does not stack like an 18 oz one and nobody
-- had given us the figure. The figure is 75.
--
-- So a Fat Can pallet is 12 x 75 = 900 cans, against 1,296 for the 18 oz cases -
-- which is the right shape for a bigger can.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products
SET units_per_case = 12,
    pallet_qty     = 75
WHERE name LIKE '%Case (12 %26 oz)%';
