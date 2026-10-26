-- Copy a customer reads, kept apart from the names the business runs on.
--
-- A product's `name` is what QuickBooks, the invoice and the packing list say:
-- 'T-Tip - White, Case (12 x 18 oz)'. That is precise and unreadable, and it is not
-- what belongs at the top of a product page, where the live site says 'DURASTRIPE
-- Field Marking Paint'. Editing `name` to suit the website would change what prints
-- on an invoice, so the website gets its own.
--
-- Both description fields hold HTML from the rich text editor, sanitised by
-- App\Services\RichText on the way in.

ALTER TABLE products
    ADD COLUMN website_name              VARCHAR(255) NULL AFTER name,
    ADD COLUMN website_short_description TEXT         NULL AFTER website_name;
