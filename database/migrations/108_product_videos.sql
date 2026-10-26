-- Videos for a product page.
--
-- One URL per line rather than a table: a product has a handful of videos, they are
-- ordered by being typed in order, and nothing needs to query them. A join table
-- would be more structure than the thing deserves.
--
-- product_documents already covers the other tabs - its doc_type enum carries tds,
-- sds, color_chart, sales_flyer and catalog - so only video had nowhere to live.

ALTER TABLE products
    ADD COLUMN video_urls TEXT NULL AFTER website_image_url;
