-- A Robo jug is the unit that is sold, and FedEx takes one to a box.
--
-- The box holds two jugs and that is how it goes on a pallet or on a truck. For a parcel
-- only one goes in. So the box is not the product - the jug is - and two facts recorded in
-- 063 and 088 were describing the box instead.
--
-- WHAT WAS WRONG. units_per_case was 2 and case_weight_gross was the weight of a pair, so
-- an order for 3 jugs would have been priced, weighed and cartonized as 3 PAIRS. Six jugs
-- of freight on a three jug order, and a FedEx label reading 105 lb for something that
-- weighs 35.
--
-- WHY IT WAS NOT WRONG FOR AEROSOL. There the unit sold IS the case - DSW averages $58.94
-- a line across 15,086 invoice lines, which is a case of twelve at about $5 a can, not a
-- single can. So a case is both the pack and the thing ordered, and the two never had to
-- be told apart. For jugs they do.
--
-- THE PALLET IS UNCHANGED at 48 jugs. It was 2 x 24 and is now 1 x 48 - the same paint on
-- the same pallet, described in the unit that is actually ordered. This is the change 063
-- warned had to be made in one piece, and all three parts of it are here.
--
-- NOTE no semicolons in these comments. See the note in 054.

-- A jug is its own pack, and there are 48 to a pallet.
UPDATE products
SET units_per_case = 1,
    pallet_qty     = 48
WHERE units_per_case = 2 AND pallet_qty = 24;

-- Halve the weights, which were recorded per pair.
UPDATE products
SET case_weight_gross = case_weight_gross / 2
WHERE units_per_case = 1 AND pallet_qty = 48 AND case_weight_gross > 0;


-- For a parcel, one jug per box. The box physically holds two, but a two-jug box is 58 to
-- 70 lb and is why the double was dropped for shipping in the first place.
UPDATE shipping_boxes
SET notes = 'Holds two jugs on a pallet or a truck. For a parcel, ONE jug per box - two is 58 to 70 lb. Tare weight still needed'
WHERE code = 'ROBO-1';

INSERT INTO product_packing_rules (product_id, shipping_box_id, cases_per_box)
SELECT p.id, b.id, 1
FROM products p
JOIN shipping_boxes b ON b.code = 'ROBO-1'
WHERE p.units_per_case = 1 AND p.pallet_qty = 48
ON DUPLICATE KEY UPDATE cases_per_box = VALUES(cases_per_box);
