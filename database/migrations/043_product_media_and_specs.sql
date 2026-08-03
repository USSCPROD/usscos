-- Migration: 043_product_media_and_specs
-- Description: Adds remaining EDI/ERP master columns, marketing descriptions,
--              unit + case + pallet packaging data, product specs / compliance,
--              and the product image gallery + document library tables.

ALTER TABLE products
    -- Pricing channels missing from the master sheet
    ADD COLUMN website_price      DECIMAL(12,4)  NULL AFTER stocking_dist,
    ADD COLUMN amazon_price       DECIMAL(12,4)  NULL AFTER website_price,

    -- Descriptions that syndicate out to website / EDI / Amazon
    ADD COLUMN long_description   TEXT           NULL AFTER short_description,
    ADD COLUMN edi_description    VARCHAR(500)   NULL AFTER long_description,
    ADD COLUMN gs1_description    TEXT           NULL AFTER edi_description,
    ADD COLUMN amazon_bullets     TEXT           NULL AFTER amazon_title,

    -- Merchandising tags (comma separated, e.g. "Football,Soccer,Lacrosse")
    ADD COLUMN sports             VARCHAR(255)   NULL AFTER brand_category,

    -- Unit-level dimensions (existing height/width/depth stay as the shipping unit)
    ADD COLUMN unit_height        DECIMAL(10,4)  NULL AFTER gs1_status,
    ADD COLUMN unit_width         DECIMAL(10,4)  NULL AFTER unit_height,
    ADD COLUMN unit_depth         DECIMAL(10,4)  NULL AFTER unit_width,
    ADD COLUMN unit_gross_weight  DECIMAL(10,4)  NULL AFTER unit_depth,
    ADD COLUMN unit_net_weight    DECIMAL(10,4)  NULL AFTER unit_gross_weight,

    -- Case / pallet packaging
    ADD COLUMN units_per_case     INT UNSIGNED   NULL AFTER unit_net_weight,
    ADD COLUMN case_length        DECIMAL(10,4)  NULL AFTER units_per_case,
    ADD COLUMN case_width         DECIMAL(10,4)  NULL AFTER case_length,
    ADD COLUMN case_height        DECIMAL(10,4)  NULL AFTER case_width,
    ADD COLUMN case_weight_gross  DECIMAL(10,4)  NULL AFTER case_height,
    ADD COLUMN case_weight_net    DECIMAL(10,4)  NULL AFTER case_weight_gross,
    ADD COLUMN pallet_ti          SMALLINT UNSIGNED NULL AFTER case_weight_net,
    ADD COLUMN pallet_hi          SMALLINT UNSIGNED NULL AFTER pallet_ti,
    ADD COLUMN pallet_qty         INT UNSIGNED   NULL AFTER pallet_hi,

    -- Compliance / logistics
    ADD COLUMN country_of_origin  VARCHAR(100)   NULL AFTER pallet_qty,
    ADD COLUMN hts_code           VARCHAR(50)    NULL AFTER country_of_origin,
    ADD COLUMN is_hazmat          TINYINT(1)     NOT NULL DEFAULT 0 AFTER hts_code,
    ADD COLUMN hazmat_class       VARCHAR(30)    NULL AFTER is_hazmat,
    ADD COLUMN un_number          VARCHAR(30)    NULL AFTER hazmat_class,

    -- Product specs (paint / coating attributes)
    ADD COLUMN paint_type         VARCHAR(100)   NULL AFTER un_number,
    ADD COLUMN voc                VARCHAR(50)    NULL AFTER paint_type,
    ADD COLUMN flash_point        VARCHAR(50)    NULL AFTER voc,
    ADD COLUMN propellant         VARCHAR(50)    NULL AFTER flash_point,
    ADD COLUMN odor               VARCHAR(50)    NULL AFTER propellant,
    ADD COLUMN dry_time           VARCHAR(50)    NULL AFTER odor,
    ADD COLUMN field_ready        VARCHAR(50)    NULL AFTER dry_time,
    ADD COLUMN coverage           VARCHAR(255)   NULL AFTER field_ready,
    ADD COLUMN dilution           VARCHAR(100)   NULL AFTER coverage,
    ADD COLUMN surface_use        VARCHAR(255)   NULL AFTER dilution,
    ADD COLUMN application        VARCHAR(255)   NULL AFTER surface_use,
    ADD COLUMN recommended_use    VARCHAR(255)   NULL AFTER application,
    ADD COLUMN clean_up           VARCHAR(100)   NULL AFTER recommended_use,
    ADD COLUMN shelf_life         VARCHAR(50)    NULL AFTER clean_up,
    ADD COLUMN storage_temp_min   SMALLINT       NULL AFTER shelf_life,
    ADD COLUMN storage_temp_max   SMALLINT       NULL AFTER storage_temp_min,
    ADD COLUMN warranty           VARCHAR(100)   NULL AFTER storage_temp_max,
    ADD COLUMN spec_notes         TEXT           NULL AFTER warranty,

    -- Data hygiene note carried over from the master sheet
    ADD COLUMN review_note        TEXT           NULL AFTER spec_notes;


-- Product image gallery
CREATE TABLE IF NOT EXISTS product_images (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED    NOT NULL,
    file_path       VARCHAR(500)    NOT NULL,          -- web path, e.g. /uploads/products/12/front.jpg
    file_name       VARCHAR(255)    NULL,              -- original upload name
    caption         VARCHAR(255)    NULL,
    is_primary      TINYINT(1)      NOT NULL DEFAULT 0,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by     INT UNSIGNED    NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_prodimg_product FOREIGN KEY (product_id)  REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_prodimg_user    FOREIGN KEY (uploaded_by) REFERENCES users (id)    ON DELETE SET NULL,
    INDEX idx_prodimg_product (product_id),
    INDEX idx_prodimg_primary (product_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Product document library (SDS, TDS, flyers, catalogs, color charts...)
CREATE TABLE IF NOT EXISTS product_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED    NOT NULL,
    doc_type        ENUM('sds','tds','sales_flyer','catalog','color_chart','spec_sheet','certificate','other')
                                    NOT NULL DEFAULT 'other',
    title           VARCHAR(255)    NOT NULL,
    file_path       VARCHAR(500)    NOT NULL,
    file_name       VARCHAR(255)    NULL,
    file_size       INT UNSIGNED    NULL,              -- bytes
    mime_type       VARCHAR(100)    NULL,
    is_public       TINYINT(1)      NOT NULL DEFAULT 1, -- show on website / customer portal
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by     INT UNSIGNED    NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_proddoc_product FOREIGN KEY (product_id)  REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_proddoc_user    FOREIGN KEY (uploaded_by) REFERENCES users (id)    ON DELETE SET NULL,
    INDEX idx_proddoc_product (product_id),
    INDEX idx_proddoc_type    (doc_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
