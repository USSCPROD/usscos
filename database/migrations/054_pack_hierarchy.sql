-- Pack hierarchy for inventory scanning.
--
-- Stock is counted in BASE UNITS (a can, a jug, a pail). Everything else is a
-- multiple of that, and the columns already on `products` carry it once their
-- meaning is pinned down:
--
--   units_per_case   base units in one intermediate pack
--                      12  cans per case
--                      24  cans per 2-pack case (2 x 12)
--                       2  jugs per box (2.5 gal)
--   pallet_qty       intermediate packs on a full pallet
--                      108 cases of aerosol
--                       54 2-pack cases (same 1,296 cans)
--                       24 boxes of 2.5 gal jugs (48 jugs)
--   pallet_ti/hi     packs per layer, and layers, where the arrangement is known
--
-- So base units on a pallet = units_per_case * pallet_qty, and a receipt of one
-- pallet of aerosol posts 1,296 cans, not 1.
--
-- pallet_qty_varies exists because 1-gallon pallets are not consistent: roughly 120,
-- but it depends on the batch, so the figure is a starting suggestion and the person
-- receiving must be able to correct it. Without this flag the system would present a
-- wrong number with the same confidence as a right one, which is how stock figures
-- stop being trusted.
--
-- Values are deliberately NOT populated here. SKUs and product names are being
-- renamed in QuickBooks, and the catalog will be re-imported from the final
-- spreadsheet — populating now would be overwritten.
--
-- NOTE no semicolons in these comments. database/migrate.php splits on semicolons and,
-- while it now tracks quote state, comments are still simplest kept clean.

ALTER TABLE products
    ADD COLUMN pallet_qty_varies TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Pallet quantity is a suggestion, not fixed - receiver must confirm'
        AFTER pallet_qty;


-- Where a receipt actually differed from the expected pallet quantity, keep the fact.
-- Repeated overrides on the same product are the signal that pallet_qty is wrong, or
-- that the product genuinely varies and should be flagged as such.
ALTER TABLE inventory_transactions
    ADD COLUMN qty_expected DECIMAL(12,4) NULL
        COMMENT 'Pallet-derived quantity offered before the receiver adjusted it'
        AFTER qty;
