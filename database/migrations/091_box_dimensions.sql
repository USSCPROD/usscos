-- Shipping box dimensions, and Fat Cans get their own box.
--
--   Aerosol, 18 oz case   11 x 10 x 9
--   Fat Can case          12 x 9 x 11
--   RoboPaint jug          6 x 9 x 14
--
-- FAT CANS WERE SHARING THE AEROSOL BOX. 077 gave every 12-to-a-case product the AERO-1
-- and AERO-2 rules, which put the 26 oz Fat Cans in the 18 oz box. That was harmless while
-- no box had dimensions and becomes wrong the moment one does - a Fat Can case is a
-- different size and would have been rated as a smaller parcel than it is. They move to a
-- box of their own.
--
-- AERO-2, the double box, is deliberately left without dimensions. Only one aerosol box
-- size was given and a double is not simply twice a single in every direction - guessing
-- would produce a confident, wrong dimensional weight, which is a thing carriers charge
-- for.
--
-- The RoboPaint box is recorded but NOT given a packing rule yet. How many jugs go in it
-- is the open question, and a rule of the wrong capacity would quietly mis-count pallets.
--
-- Tare weights are still unknown for every box. Until they are, a package weight is the
-- contents only, which understates it slightly.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE shipping_boxes
SET length_in = 11, width_in = 10, height_in = 9,
    notes = 'Holds one 18 oz case of 12. Dimensions confirmed 28 Sep 2026 - tare weight still needed'
WHERE code = 'AERO-1';

UPDATE shipping_boxes
SET notes = 'Holds two 18 oz cases. DIMENSIONS STILL NEEDED - not assumed from the single box'
WHERE code = 'AERO-2';

INSERT INTO shipping_boxes (code, name, length_in, width_in, height_in, notes)
VALUES
    ('FATCAN-1', 'Fat Can box', 12, 9, 11,
     'Holds one 26 oz Fat Can case of 12. Tare weight still needed'),
    ('ROBO-1', 'RoboPaint jug box', 6, 9, 14,
     'The same box for singles and doubles. Jugs per box not yet confirmed, so no packing rule yet')
ON DUPLICATE KEY UPDATE
    length_in = VALUES(length_in), width_in = VALUES(width_in),
    height_in = VALUES(height_in), notes = VALUES(notes);


-- Move the Fat Cans off the aerosol boxes and onto their own.
DELETE r FROM product_packing_rules r
JOIN products p ON p.id = r.product_id
JOIN shipping_boxes b ON b.id = r.shipping_box_id
WHERE p.pallet_qty = 75 AND b.code IN ('AERO-1', 'AERO-2');

INSERT INTO product_packing_rules (product_id, shipping_box_id, cases_per_box)
SELECT p.id, b.id, 1
FROM products p
JOIN shipping_boxes b ON b.code = 'FATCAN-1'
WHERE p.units_per_case = 12 AND p.pallet_qty = 75
ON DUPLICATE KEY UPDATE cases_per_box = VALUES(cases_per_box);
