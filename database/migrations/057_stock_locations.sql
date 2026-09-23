-- Multi-location inventory: warehouses, bays, racks, and stock held at each.
--
-- Today `products.qty_on_hand` is a single number with nowhere to say where the stock
-- actually is. With a second warehouse opening at 730, "how many do we have" becomes
-- "how many, and in which building".
--
-- WHY qty_on_hand STAYS. Plenty of existing code reads products.qty_on_hand, and
-- breaking all of it to introduce locations would be a large change for no operational
-- gain. So it keeps its meaning — the total across every location — and becomes a
-- maintained sum rather than the source of truth. `product_stock` holds the detail.
-- One number, two levels of resolution, and the two are kept in step in one place.
--
-- LOCATIONS ARE A TREE. Stock at USSC is stored by bay and rack, so a flat list of
-- warehouses would not describe it. A self-referencing parent_id keeps the model honest
-- without fixing the depth: warehouse > bay > rack today, and a bin level later if it is
-- ever wanted, without another migration.
--
-- IN TRANSIT IS A REAL PLACE. When a pallet leaves one building and has not arrived at
-- the other it is in neither, and if a transfer is one instant event anything lost
-- between them vanishes with nobody able to say where. So each warehouse gets a transit
-- location of its own, stock moves into it on scan-out and out of it on scan-in, and
-- anything sitting there too long is a question worth asking.
--
-- NOTE no semicolons in these comments — database/migrate.php splits on them.

CREATE TABLE IF NOT EXISTS stock_locations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED NULL,
    code        VARCHAR(40)  NOT NULL COMMENT 'Short label people say out loud - 730, A, A-12',
    name        VARCHAR(150) NOT NULL,
    location_type ENUM('warehouse','bay','rack','bin','transit','staging') NOT NULL DEFAULT 'warehouse',

    -- A transit location belongs to the warehouse goods are leaving, so "what is in
    -- transit out of 730" is answerable.
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    notes       VARCHAR(255) NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_stock_location_parent FOREIGN KEY (parent_id)
        REFERENCES stock_locations (id) ON DELETE CASCADE,
    UNIQUE KEY uq_location_code (parent_id, code),
    INDEX idx_location_type (location_type),
    INDEX idx_location_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- How much of a product sits at one location. The row only exists once stock has been
-- there, so an empty table means an empty warehouse rather than a broken one.
CREATE TABLE IF NOT EXISTS product_stock (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id   INT UNSIGNED NOT NULL,
    location_id  INT UNSIGNED NOT NULL,
    qty_on_hand  DECIMAL(12,4) NOT NULL DEFAULT 0,
    counted_at   DATETIME     NULL COMMENT 'Last physical count of this product here',
    counted_by   INT UNSIGNED NULL,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_stock_product  FOREIGN KEY (product_id)  REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_stock_location FOREIGN KEY (location_id) REFERENCES stock_locations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_product_stock_counter  FOREIGN KEY (counted_by)  REFERENCES users (id) ON DELETE SET NULL,

    UNIQUE KEY uq_product_location (product_id, location_id),
    INDEX idx_stock_location (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Every movement needs to say where from and where to. A receipt has only a destination,
-- a shipment only a source, a transfer has both.
ALTER TABLE inventory_transactions
    ADD COLUMN from_location_id INT UNSIGNED NULL AFTER product_id,
    ADD COLUMN to_location_id   INT UNSIGNED NULL AFTER from_location_id,
    ADD CONSTRAINT fk_invtxn_from FOREIGN KEY (from_location_id) REFERENCES stock_locations (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_invtxn_to   FOREIGN KEY (to_location_id)   REFERENCES stock_locations (id) ON DELETE SET NULL;


-- The two buildings, each with a transit location. Bays and racks get added through the
-- admin screen rather than guessed at here.
INSERT INTO stock_locations (parent_id, code, name, location_type, sort_order) VALUES
    (NULL, 'MAIN', 'Main Warehouse', 'warehouse', 1),
    (NULL, '730',  '730 Warehouse',  'warehouse', 2);

INSERT INTO stock_locations (parent_id, code, name, location_type, sort_order)
SELECT id, 'TRANSIT', CONCAT('In transit from ', name), 'transit', 99
FROM stock_locations WHERE location_type = 'warehouse';
