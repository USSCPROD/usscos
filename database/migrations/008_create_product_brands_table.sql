-- Product Brands
-- Derived from QuickBooks BRAND:SKU item naming convention
-- e.g. "DURASTRIPE:DSBLK", "ECOSTRIPE-UMA:UESO"
CREATE TABLE product_brands (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,   -- e.g. DURASTRIPE, ECOSTRIPE-UMA
    slug        VARCHAR(100) NOT NULL UNIQUE,   -- url-safe, e.g. durastripe
    description TEXT NULL,
    vendor_id   INT UNSIGNED NULL,             -- preferred/primary vendor for this brand
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_brands_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
