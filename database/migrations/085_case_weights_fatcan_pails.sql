-- Case weights: Fat Cans at 26 lb, 5 gallon pails at 70 lb.
--
-- Fat Cans hold 12 x 26 oz, half as much paint again as an 18 oz case, and weigh 26 lb
-- against the 18 oz case's 18. A pail is its own pack, so 70 lb is one pail on the scale.
--
-- THESE NUMBERS CHECK OUT AGAINST THE PALLETS, which is worth noting because it is the
-- only corroboration available until something is actually weighed:
--
--   18 oz aerosol   108 cases x 18 lb = 1,944 lb
--   Fat Can          75 cases x 26 lb = 1,950 lb
--   5 gal pails      24 pails x 70 lb = 1,680 lb
--
-- Three different products landing within a few hundred pounds of each other is what a
-- pallet should look like. Had one come out at 400 lb or 4,000 lb, the figure or the
-- pallet quantity would have been wrong.
--
-- A 70 lb pail also explains max_parcel_qty = 1 from 077 without anyone having to justify
-- it: two pails is 140 lb, which is at the edge of what FedEx Ground takes in one package.
--
-- NOTE no semicolons in these comments. See the note in 054.

-- Fat Cans: 12 x 26 oz.
UPDATE products
SET case_weight_gross = 26.0,
    weight_unit       = COALESCE(NULLIF(weight_unit, ''), 'LB')
WHERE units_per_case = 12 AND pallet_qty = 75;

-- 5 gallon pails, which are their own pack.
UPDATE products
SET case_weight_gross = 70.0,
    weight_unit       = COALESCE(NULLIF(weight_unit, ''), 'LB')
WHERE units_per_case = 1 AND pallet_qty = 24;
