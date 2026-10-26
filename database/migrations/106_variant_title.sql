-- A display title for a group of variants.
--
-- The stencil grouping derives its listing title by stripping the thickness off the
-- end of a product name, which works because the name ends in it. Aerosol names do
-- not: 'T-Tip - Aluminum, Case (12 x 18 oz)' carries the color in the middle, and
-- stripping it would leave a torn sentence. Worse, the name never says the brand, so
-- 'T-Tip - Augusta Green' gives a customer no way to tell DuraStripe from EcoStripe.
--
-- So the title is stored rather than derived: 'DuraStripe T-Tip Field Marking Paint,
-- Case (12 x 18 oz)'. Null means fall back to deriving it, which is what stencils do.

ALTER TABLE products
    ADD COLUMN variant_title VARCHAR(200) NULL AFTER variant_order;
