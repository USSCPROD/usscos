-- How product goes into shipping boxes, and when it stops being a parcel at all.
--
-- A case is not a package. Aerosol is cased 12 cans, but a case is not what FedEx
-- collects - cases go into shipping boxes, and the rule is that they pair up:
--
--   1 case   one single box            1 package
--   2 cases  one double box            1 package
--   3 cases  one double + one single   2 packages
--   4 cases  two doubles               2 packages
--
-- Which is a greedy fill: take the biggest box that still fits, repeat. Expressed as rows
-- rather than as code so that a triple box, or a different rule for a different product,
-- is data rather than a change to the packer.
--
-- WHY THIS MATTERS FOR LABELS. FedEx prices and tracks per package, and returns a tracking
-- number per piece. Asking it for three packages when the order actually leaves as two is
-- wrong twice over - the customer gets a tracking number for a box that does not exist,
-- and the freight is overpriced.
--
-- NOT EVERYTHING IS A PARCEL. One 5 gallon pail goes FedEx. Two go on a truck. That is a
-- quantity threshold per product rather than a weight one, because the decision is made on
-- the floor by what fits, not by arithmetic - so max_parcel_qty records the number above
-- which it becomes freight, and freight is hand-coded until the LTL side is automated.
--
-- The dimensions and weights are deliberately left NULL. They arrive with the product file
-- along with the pack quantities, and a guessed box size produces a confidently wrong
-- shipping charge.
--
-- NOTE no semicolons in these comments. See the note in 054.

CREATE TABLE IF NOT EXISTS shipping_boxes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30)  NOT NULL,
    name        VARCHAR(120) NOT NULL,

    length_in   DECIMAL(8,2) NULL,
    width_in    DECIMAL(8,2) NULL,
    height_in   DECIMAL(8,2) NULL,
    tare_lb     DECIMAL(8,2) NULL COMMENT 'The empty box - it is on the scale too',
    max_lb      DECIMAL(8,2) NULL COMMENT 'Above this it is not going by parcel',

    notes       VARCHAR(255) NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_box_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO shipping_boxes (code, name, notes) VALUES
    ('AERO-1', 'Aerosol single box', 'Holds one case of 12 cans - dimensions and weight to come with the product file'),
    ('AERO-2', 'Aerosol double box', 'Holds two cases - dimensions and weight to come with the product file')
ON DUPLICATE KEY UPDATE name = VALUES(name);


-- Which boxes a product may go in, and how much of it fits in each.
CREATE TABLE IF NOT EXISTS product_packing_rules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    shipping_box_id INT UNSIGNED NOT NULL,

    cases_per_box   SMALLINT UNSIGNED NOT NULL DEFAULT 1
                    COMMENT 'How many SELLING UNITS of this product fit in this box',

    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_packing_product FOREIGN KEY (product_id)      REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_packing_box     FOREIGN KEY (shipping_box_id) REFERENCES shipping_boxes (id) ON DELETE CASCADE,

    UNIQUE KEY uq_packing (product_id, shipping_box_id),
    INDEX idx_packing_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Above this many, it is freight rather than a parcel. NULL means no limit.
ALTER TABLE products
    ADD COLUMN max_parcel_qty DECIMAL(12,4) NULL
        COMMENT 'More than this on one order goes LTL - one 5 gal pail ships FedEx, two do not'
        AFTER pallet_qty_varies;


-- Aerosol: every 18 oz and 26 oz case can go one to a single box or two to a double.
INSERT INTO product_packing_rules (product_id, shipping_box_id, cases_per_box)
SELECT p.id, b.id, 1
FROM products p
JOIN shipping_boxes b ON b.code = 'AERO-1'
WHERE p.units_per_case = 12 AND p.pallet_qty IN (108, 75)
ON DUPLICATE KEY UPDATE cases_per_box = VALUES(cases_per_box);

INSERT INTO product_packing_rules (product_id, shipping_box_id, cases_per_box)
SELECT p.id, b.id, 2
FROM products p
JOIN shipping_boxes b ON b.code = 'AERO-2'
WHERE p.units_per_case = 12 AND p.pallet_qty IN (108, 75)
ON DUPLICATE KEY UPDATE cases_per_box = VALUES(cases_per_box);


-- 5 gallon pails: one goes FedEx, more than one goes on a truck.
UPDATE products
SET max_parcel_qty = 1
WHERE units_per_case = 1 AND pallet_qty = 24;
