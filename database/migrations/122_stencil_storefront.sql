-- What the storefront needs to show stencils the way the stencil shop did.
--
-- categories.website_copy  The long copy under a category's products: headings,
--                          paragraphs and links. `description` stays the one-line
--                          blurb under the title.
--
-- products.website_slug    The listing's address, /stencils/{website_slug}. Shared by
--                          every SKU in the listing. The shop's URLs were built this
--                          way, so copy that links /stencils/no-parking keeps working
--                          and the old addresses redirect one-for-one at cut-over.
-- products.website_size_slug
--                          One size within it, /stencils/no-parking/4-inch-letters.
--
-- site_pages               Pages whose words are content, not code: Shipping &
--                          Returns, stencil materials and sizing. Editing them should
--                          not need a deploy.

ALTER TABLE categories
    ADD COLUMN website_copy MEDIUMTEXT NULL AFTER description;

ALTER TABLE products
    ADD COLUMN website_slug      VARCHAR(150) NULL AFTER website_name,
    ADD COLUMN website_size_slug VARCHAR(150) NULL AFTER website_slug;

CREATE INDEX idx_products_website_slug ON products (website_slug);

CREATE TABLE IF NOT EXISTS site_pages (
    slug        VARCHAR(100)  NOT NULL PRIMARY KEY,
    title       VARCHAR(200)  NOT NULL,
    description VARCHAR(500)  NULL,
    html        MEDIUMTEXT    NOT NULL,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
