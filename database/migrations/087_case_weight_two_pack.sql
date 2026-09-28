-- 2-pack cases weigh 36 lb.
--
-- Twice an 18 oz case, which is what it should be: the box holds 2 cases of 12 cans, so
-- 24 cans against the single case's 12.
--
-- It also checks out against the pallet - 54 boxes x 36 lb = 1,944 lb, exactly the same as
-- 108 single cases at 18 lb. The same paint on the same pallet, weighed two different ways
-- and agreeing, which is the strongest confirmation available short of a scale.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE products
SET case_weight_gross = 36.0,
    weight_unit       = COALESCE(NULLIF(weight_unit, ''), 'LB')
WHERE sku IN ('DSW24', 'DSR24', 'DSRB24', 'ESW24');
