-- Expand products table to match USSC EDI-ERP master field list
ALTER TABLE products
    -- Identity
    ADD COLUMN product_line       VARCHAR(100)   NULL AFTER brand_id,
    ADD COLUMN color              VARCHAR(100)   NULL AFTER product_line,
    ADD COLUMN short_description  TEXT           NULL AFTER description,
    ADD COLUMN purchase_description TEXT         NULL AFTER short_description,

    -- Item Setup
    ADD COLUMN category           VARCHAR(100)   NULL AFTER item_type,
    ADD COLUMN brand_category     VARCHAR(100)   NULL AFTER category,
    ADD COLUMN uom_code           VARCHAR(50)    NULL AFTER uom_id,
    ADD COLUMN pack_level         VARCHAR(50)    NULL AFTER uom_code,

    -- Pricing
    ADD COLUMN retail_price       DECIMAL(12,4)  NULL AFTER price,
    ADD COLUMN dist_price         DECIMAL(12,4)  NULL AFTER retail_price,
    ADD COLUMN dist_price_2026    DECIMAL(12,4)  NULL AFTER dist_price,
    ADD COLUMN stocking_dist      DECIMAL(12,4)  NULL AFTER dist_price_2026,

    -- Accounting
    ADD COLUMN income_account     VARCHAR(100)   NULL AFTER sales_tax_code,
    ADD COLUMN cogs_account       VARCHAR(100)   NULL AFTER income_account,
    ADD COLUMN asset_account      VARCHAR(100)   NULL AFTER cogs_account,
    ADD COLUMN tax_agency         VARCHAR(100)   NULL AFTER asset_account,

    -- Inventory
    ADD COLUMN min_order_qty      DECIMAL(12,4)  NULL AFTER reorder_point,
    ADD COLUMN lead_time_days     SMALLINT UNSIGNED NULL AFTER min_order_qty,

    -- Vendor
    ADD COLUMN vendor_part_number VARCHAR(100)   NULL AFTER preferred_vendor_id,
    ADD COLUMN preferred_vendor_name VARCHAR(150) NULL AFTER vendor_part_number,

    -- Barcodes
    ADD COLUMN mpn                VARCHAR(100)   NULL AFTER quickbooks_id,
    ADD COLUMN gtin12             VARCHAR(14)    NULL AFTER mpn,
    ADD COLUMN gtin14             VARCHAR(14)    NULL AFTER gtin12,

    -- GS1 / Dimensions
    ADD COLUMN height             DECIMAL(10,4)  NULL AFTER gtin14,
    ADD COLUMN width              DECIMAL(10,4)  NULL AFTER height,
    ADD COLUMN depth              DECIMAL(10,4)  NULL AFTER width,
    ADD COLUMN dim_unit           VARCHAR(20)    NULL AFTER depth,
    ADD COLUMN gross_weight       DECIMAL(10,4)  NULL AFTER dim_unit,
    ADD COLUMN net_weight         DECIMAL(10,4)  NULL AFTER gross_weight,
    ADD COLUMN weight_unit        VARCHAR(20)    NULL AFTER net_weight,
    ADD COLUMN gs1_status         VARCHAR(50)    NULL AFTER weight_unit,

    -- Digital / Amazon
    ADD COLUMN asin               VARCHAR(20)    NULL AFTER website_image_url,
    ADD COLUMN amazon_title       VARCHAR(500)   NULL AFTER asin;
