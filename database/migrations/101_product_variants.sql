-- Group products that are the same article in a different form, so the storefront
-- can offer one listing with a choice instead of several near-identical listings.
--
-- Stencils are the case in hand: the same cut in 1/8", 3/32" and 1/16" is three
-- products, and the shop currently shows all three side by side with almost the
-- same name. 208 stencils are really 107 things to buy.
--
-- They stay separate product rows deliberately. Each thickness has its own SKU,
-- its own price, its own stock and its own QuickBooks item, and an order line must
-- name exactly one of them. Merging them into a single product would be a data loss
-- dressed up as a tidier catalogue. This groups them for display only.
--
-- variant_group  an arbitrary key shared by the members of one listing. The stencil
--                backfill uses the SKU with the thickness segment removed, but
--                nothing depends on that being how it is derived.
-- variant_label  what the customer picks, shown on the button: '1/8"'.
-- variant_order  controls the order the choices appear in, so the list does not
--                reorder itself alphabetically into 1/16, 1/8, 3/32.

ALTER TABLE products
    ADD COLUMN variant_group VARCHAR(100)     NULL AFTER sku,
    ADD COLUMN variant_label VARCHAR(50)      NULL AFTER variant_group,
    ADD COLUMN variant_order SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER variant_label;

CREATE INDEX idx_products_variant_group ON products (variant_group);
