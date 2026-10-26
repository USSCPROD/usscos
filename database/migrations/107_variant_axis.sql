-- What a group of variants varies BY.
--
-- The product page labelled its picker 'Thickness' because stencils were the first
-- thing grouped. Aerosol paints vary by color, and a row of color names under the
-- heading 'Thickness' is worse than no heading - it tells the customer the page is
-- about something it is not.
--
-- Stored per product rather than guessed from the category, because the axis belongs
-- to the grouping that produced it and a product can be filed in several categories.

ALTER TABLE products
    ADD COLUMN variant_axis VARCHAR(30) NULL AFTER variant_title;
