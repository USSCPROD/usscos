-- Two Direct-to-Metal products exist twice. Keep the SKU the user named, retire the other.
--
--   Green            DTMG-1G   and DTMGRN-1G   -> keep DTMGRN-1G
--   Handicap Blue    DTMHB -1G and DTMHB-1G    -> keep DTMHB-1G (the other has a stray space)
--
-- THE BARCODE MOVES WITH THE SURVIVOR. DTMG-1G is the one carrying the GTINs, and it is
-- the one being retired - so the barcodes are moved to DTMGRN-1G first. Retiring the row
-- that holds them would quietly make a scannable product unscannable, which is the sort of
-- thing nobody notices until somebody is standing at a shelf with a scanner.
--
-- RETIRED, NOT DELETED. DTMG-1G carries an invoice line from March 2026. Products are
-- referenced from seven tables and a forced delete would erase the product link on that
-- historical invoice, so it is deactivated instead. The invoice still points at the
-- product that was actually sold, which is correct - history is not rewritten to match a
-- tidier present.
--
-- NOTE no semicolons in these comments. See the note in 054.

-- Move the barcodes to the SKU that is being kept.
UPDATE products dst
JOIN products src ON src.sku = 'DTMG-1G'
SET dst.gtin14 = src.gtin14,
    dst.gtin12 = src.gtin12
WHERE dst.sku = 'DTMGRN-1G'
  AND (dst.gtin14 IS NULL OR dst.gtin14 = '');

-- Clear them from the retired row so one barcode does not resolve to two products.
UPDATE products SET gtin14 = NULL, gtin12 = NULL WHERE sku = 'DTMG-1G';

-- Retire the duplicates.
--
-- Only is_active changes. There is no notes field on products meant for this - spec_notes
-- is for specifications and would be the wrong place - so the reason lives here and in the
-- commit rather than being written somewhere it does not belong.
--
--   DTMG-1G    superseded by DTMGRN-1G
--   DTMHB -1G  superseded by DTMHB-1G
UPDATE products SET is_active = 0 WHERE sku IN ('DTMG-1G', 'DTMHB -1G');


-- The duplicate iGo yellow jugs are left alone - the user does not want time spent on iGo.
