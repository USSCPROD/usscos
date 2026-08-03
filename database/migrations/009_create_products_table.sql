-- Products / Items
-- Source: QuickBooks Item List (4,738 records)
-- Item format: BRAND:SKU (e.g. DURASTRIPE:DSBLK)
-- Types: Service, Inventory Part, Inventory Assembly, Non-inventory Part,
--        Other Charge, Group, Discount, Payment, Sales Tax Item
CREATE TABLE products (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_id            INT UNSIGNED NULL,
    sku                 VARCHAR(100) NOT NULL,           -- part after colon (DSBLK)
    quickbooks_item     VARCHAR(255) NOT NULL UNIQUE,   -- full QB item name (DURASTRIPE:DSBLK)
    name                VARCHAR(255) NOT NULL,           -- display name
    description         TEXT NULL,

    -- Item classification
    item_type           ENUM(
                            'inventory_part',
                            'inventory_assembly',
                            'non_inventory_part',
                            'service',
                            'other_charge',
                            'group',
                            'discount',
                            'payment',
                            'sales_tax_item'
                        ) NOT NULL DEFAULT 'inventory_part',

    -- Pricing
    cost                DECIMAL(12,4) NULL,             -- purchase cost
    price               DECIMAL(12,4) NULL,             -- default sell price
    is_percent_discount TINYINT(1) NOT NULL DEFAULT 0, -- for Discount type

    -- Units
    uom_id              INT UNSIGNED NULL,

    -- Inventory tracking (only for inventory_part / inventory_assembly)
    track_inventory     TINYINT(1) NOT NULL DEFAULT 0,
    qty_on_hand         DECIMAL(12,4) NOT NULL DEFAULT 0,
    qty_on_sales_order  DECIMAL(12,4) NOT NULL DEFAULT 0,
    qty_on_po           DECIMAL(12,4) NOT NULL DEFAULT 0,
    reorder_point       DECIMAL(12,4) NULL,
    avg_cost            DECIMAL(12,4) NULL,
    asset_value         DECIMAL(14,2) NULL,

    -- Tax
    sales_tax_code      VARCHAR(20) NULL,               -- Tax / Non
    is_taxable          TINYINT(1) NOT NULL DEFAULT 1,

    -- Vendor
    preferred_vendor_id INT UNSIGNED NULL,

    -- Assembly / Group components stored separately in product_components

    -- QuickBooks sync
    quickbooks_id       VARCHAR(50) NULL UNIQUE,
    last_synced_at      TIMESTAMP NULL,

    -- Website
    publish_to_website  TINYINT(1) NOT NULL DEFAULT 0,
    website_description TEXT NULL,
    website_image_url   VARCHAR(500) NULL,
    website_sort_order  INT UNSIGNED NULL,

    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_brand    FOREIGN KEY (brand_id)            REFERENCES product_brands (id) ON DELETE SET NULL,
    CONSTRAINT fk_products_uom      FOREIGN KEY (uom_id)              REFERENCES units_of_measure (id) ON DELETE SET NULL,
    CONSTRAINT fk_products_vendor   FOREIGN KEY (preferred_vendor_id) REFERENCES vendors (id) ON DELETE SET NULL,

    INDEX idx_products_sku          (sku),
    INDEX idx_products_brand        (brand_id),
    INDEX idx_products_type         (item_type),
    INDEX idx_products_active       (is_active),
    INDEX idx_products_website      (publish_to_website),
    INDEX idx_products_qty          (qty_on_hand),
    INDEX idx_products_reorder      (reorder_point),
    FULLTEXT idx_products_search    (sku, name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assembly / Group components (bill of materials)
CREATE TABLE product_components (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id       INT UNSIGNED NOT NULL,  -- the Assembly or Group product
    component_id    INT UNSIGNED NOT NULL,  -- the child product/item
    qty             DECIMAL(12,4) NOT NULL DEFAULT 1,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    CONSTRAINT fk_components_parent    FOREIGN KEY (parent_id)    REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_components_component FOREIGN KEY (component_id) REFERENCES products (id) ON DELETE RESTRICT,
    UNIQUE KEY uq_component (parent_id, component_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
