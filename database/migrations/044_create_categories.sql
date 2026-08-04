-- Migration: 044_create_categories
-- Description: Real category tree to replace the flat products.category string
--              (which is "Uncategorized" on 770 of 891 rows and carries no meaning).
--              Seeded from products.brand_category, which already holds a usable
--              taxonomy. Step 1 of the Website & Publishing module, and Chapter 3 of
--              the EPIM outline.
--
-- products.category and products.brand_category are LEFT IN PLACE and untouched —
-- nothing that reads them breaks. They can be retired once the UI reads categories.

CREATE TABLE IF NOT EXISTS categories (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id        INT UNSIGNED    NULL,
    name             VARCHAR(150)    NOT NULL,
    slug             VARCHAR(150)    NOT NULL UNIQUE,      -- url-safe, e.g. field-marking-paint
    description      TEXT            NULL,
    image_path       VARCHAR(500)    NULL,                 -- category landing hero

    sort_order       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active        TINYINT(1)      NOT NULL DEFAULT 1,
    show_on_website  TINYINT(1)      NOT NULL DEFAULT 1,   -- 0 = internal only (toll, resale)

    -- Public site metadata
    seo_title        VARCHAR(255)    NULL,
    seo_description  VARCHAR(500)    NULL,

    created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id)
        REFERENCES categories (id) ON DELETE SET NULL,

    INDEX idx_categories_parent  (parent_id),
    INDEX idx_categories_active  (is_active),
    INDEX idx_categories_website (show_on_website),
    INDEX idx_categories_sort    (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- A product may sit in more than one category; exactly one is its primary.
CREATE TABLE IF NOT EXISTS product_categories (
    product_id   INT UNSIGNED NOT NULL,
    category_id  INT UNSIGNED NOT NULL,
    is_primary   TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    PRIMARY KEY (product_id, category_id),
    CONSTRAINT fk_prodcat_product  FOREIGN KEY (product_id)  REFERENCES products (id)   ON DELETE CASCADE,
    CONSTRAINT fk_prodcat_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE,

    INDEX idx_prodcat_category (category_id),
    INDEX idx_prodcat_primary  (product_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- Seed the tree from the existing brand_category values
-- ---------------------------------------------------------------------------

-- Top level
INSERT INTO categories (name, slug, sort_order, show_on_website) VALUES
    ('Field Marking Paint',      'field-marking-paint',      10, 1),
    ('Specialty Coatings',       'specialty-coatings',       20, 1),
    ('Concrete & Floor Coatings','concrete-floor-coatings',  30, 1),
    ('Direct-to-Metal Coatings', 'direct-to-metal-coatings', 40, 1),
    ('Turf Colorants',           'turf-colorants',           50, 1),
    ('Mulch Colorants',          'mulch-colorants',          60, 1),
    ('Sealers & Coatings',       'sealers-coatings',         70, 1),
    ('Safety Products',          'safety-products',          80, 1),
    -- Business-model classifications, not public categories
    ('Toll Manufacturing',       'toll-manufacturing',       90, 0),
    ('Resale',                   'resale',                  100, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Children of Field Marking Paint
INSERT IGNORE INTO categories (parent_id, name, slug, sort_order, show_on_website)
SELECT p.id, x.name, x.slug, x.sort_order, 1
FROM (SELECT id FROM categories WHERE slug = 'field-marking-paint') p
CROSS JOIN (
    SELECT 'Aerosol' AS name, 'field-marking-aerosol' AS slug, 10 AS sort_order
    UNION ALL SELECT 'Liquid',  'field-marking-liquid',  20
    UNION ALL SELECT 'Robotic', 'field-marking-robotic', 30
) x;


-- ---------------------------------------------------------------------------
-- Map products into the tree from brand_category
-- LIKE patterns are used rather than exact matches so the em dash in values such
-- as "Field Marking Paint — Aerosol" can't cause an encoding mismatch.
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'field-marking-aerosol'
WHERE p.brand_category LIKE 'Field Marking Paint%Aerosol%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'field-marking-liquid'
WHERE p.brand_category LIKE 'Field Marking Paint%Liquid%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'field-marking-robotic'
WHERE p.brand_category LIKE 'Robotic%Field Marking%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'specialty-coatings'
WHERE p.brand_category LIKE 'Specialty Coatings%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'concrete-floor-coatings'
WHERE p.brand_category LIKE 'Concrete%Floor%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'direct-to-metal-coatings'
WHERE p.brand_category LIKE 'Direct-to-Metal%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'turf-colorants'
WHERE p.brand_category LIKE 'Turf Colorant%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'mulch-colorants'
WHERE p.brand_category LIKE 'Mulch Colorant%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'sealers-coatings'
WHERE p.brand_category LIKE 'Sealers%Coatings%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'safety-products'
WHERE p.brand_category LIKE '%Safety Products%';

-- Business-model buckets, hidden from the website until reorganised by hand
INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'toll-manufacturing'
WHERE p.brand_category LIKE 'Toll%';

INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
SELECT p.id, c.id, 1
FROM products p
JOIN categories c ON c.slug = 'resale'
WHERE p.brand_category LIKE 'Resale%';
