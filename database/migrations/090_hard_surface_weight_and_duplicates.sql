-- Hard Surface is RoboTraffic: 29 lb per jug. And it exists four times over.
--
-- RoboTraffic had no matching SKU because it is called Hard Surface in the catalog.
-- Confirmed by the user on 28 September 2026.
--
-- Stored as 58 lb, being two jugs to a pack like the rest of the Robo line.
--
-- FOUR DUPLICATE PAIRS. Every Hard Surface product is in twice, once from the original
-- import with inconsistent spacing and once from the later series with clean hyphens:
--
--   ROBOHS P-2.5      / ROBOHS-P-2.5
--   ROBOHS W-2.5      / ROBOHS-W-2.5
--   ROBOHS Y 2.5      / ROBOHS-Y-2.5
--   ROBOHS-SC W-1.25  / ROBOHS-SCW-1.25
--
-- The later series is kept, matching the choice already made for Direct-to-Metal, where
-- DTMGRN-1G was kept over DTMG-1G. The last pair settles it on its own: the older SKU says
-- 1.25 in its code and "2.5 Gal Jug" in its name, which is wrong either way, while the
-- newer one is consistent.
--
-- None of the eight has a sale, a barcode or any stock, so retiring is reversible and
-- costs nothing. Deactivated rather than deleted, as always.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products
SET case_weight_gross = 58.0,
    weight_unit       = COALESCE(NULLIF(weight_unit, ''), 'LB')
WHERE sku LIKE 'ROBOHS%' AND units_per_case = 2 AND pallet_qty = 24;

UPDATE products
SET is_active = 0
WHERE sku IN ('ROBOHS P-2.5', 'ROBOHS W-2.5', 'ROBOHS Y 2.5', 'ROBOHS-SC W-1.25');
