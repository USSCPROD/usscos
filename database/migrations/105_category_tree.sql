-- The website's category tree, kept re-importable and able to say what kind of node
-- each row is.
--
-- external_ref holds the tree's own id (CAT-FM-AEROSOL). Matching on name alone would
-- rename-and-duplicate the moment somebody edits a label on the website, and names in
-- the tree already differ from ours - "Field Marking Paints" against our "Field
-- Marking Paint".
--
-- nav_role distinguishes the two kinds of node in that tree. 'top_level' and
-- 'category' are the product taxonomy. 'discovery' and 'facet' - Athletic Fields,
-- Concrete, Football - are not a hierarchy at all: they are cross-cutting ways to
-- browse, and a product belongs to several at once. Storing them in one table is fine
-- as long as the storefront can tell which is which, which is what this column is for.

ALTER TABLE categories
    ADD COLUMN external_ref VARCHAR(80)  NULL AFTER slug,
    ADD COLUMN nav_role     VARCHAR(30)  NULL AFTER external_ref,
    ADD COLUMN canonical_url VARCHAR(500) NULL AFTER nav_role;

CREATE UNIQUE INDEX uq_categories_external_ref ON categories (external_ref);
