-- Amazon settlements: what was sold, what Amazon kept, and what actually arrived.
--
-- WHY THIS SHAPE. Amazon's settlement report is not a list of orders. It is a ledger of
-- amounts, several rows per order item, and the money only makes sense when they are read
-- together:
--
--   ItemPrice       Principal                              149.95
--   ItemPrice       Tax                                     11.17
--   ItemWithheldTax MarketplaceFacilitatorTax-Principal    -11.17
--   ItemFees        Commission                             -22.49
--
-- The tax rows cancel - Amazon charges the buyer and withholds the same amount, which is
-- the whole marketplace facilitator arrangement and the reason USSCOS must never put that
-- tax on an invoice. The commission is a flat 15 percent of principal, and shipping is
-- charged the same 15 percent as ShippingHB.
--
-- Some rows belong to no order at all: Cost of Advertising arrives as a period service
-- fee, and a reserve balance is carried between settlements. Those cannot hang off an
-- order and are why the line table allows a null order id.
--
-- STORED AS RECEIVED. Every line is kept with its own amount-type and description rather
-- than being summarised on import. Amazon adds new fee types without warning, and a
-- schema that only understood the ones in the first file we read would silently drop the
-- next one. The total of the lines must reconcile to the deposit, and it only can if
-- nothing was discarded on the way in.
--
-- NOTE no semicolons in these comments. See the note in 054.

CREATE TABLE IF NOT EXISTS marketplace_settlements (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    marketplace     VARCHAR(40)  NOT NULL DEFAULT 'amazon',
    settlement_id   VARCHAR(40)  NOT NULL COMMENT 'Amazon own settlement id',
    period_start    DATETIME     NULL,
    period_end      DATETIME     NULL,
    deposit_date    DATETIME     NULL,
    deposit_amount  DECIMAL(14,2) NULL COMMENT 'What Amazon says it paid - the figure to reconcile to',
    currency        CHAR(3)      NOT NULL DEFAULT 'USD',

    -- Filled from the lines, so the dashboard can show the split without re-aggregating.
    gross_sales     DECIMAL(14,2) NOT NULL DEFAULT 0,
    fees_total      DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_collected   DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_withheld    DECIMAL(14,2) NOT NULL DEFAULT 0,
    refunds_total   DECIMAL(14,2) NOT NULL DEFAULT 0,
    other_total     DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT 'Advertising, reserves, anything not per-order',
    computed_net    DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT 'Sum of every line - must equal deposit_amount',

    line_count      INT UNSIGNED NOT NULL DEFAULT 0,
    imported_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    source_file     VARCHAR(255) NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_settlement (marketplace, settlement_id),
    KEY idx_deposit (deposit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS marketplace_settlement_lines (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    settlement_id     INT UNSIGNED NOT NULL,

    transaction_type  VARCHAR(40)  NULL COMMENT 'Order, Refund, ServiceFee, other-transaction',
    order_id          VARCHAR(40)  NULL COMMENT 'Amazon order id - null on advertising and reserves',
    order_item_code   VARCHAR(40)  NULL,
    adjustment_id     VARCHAR(80)  NULL,
    shipment_id       VARCHAR(40)  NULL,
    sku               VARCHAR(80)  NULL COMMENT 'The SELLER sku as listed on Amazon, not necessarily a USSCOS sku',
    quantity          INT          NULL,

    amount_type       VARCHAR(60)  NULL COMMENT 'ItemPrice, ItemFees, ItemWithheldTax, Cost of Advertising',
    amount_description VARCHAR(80) NULL COMMENT 'Principal, Tax, Commission, MarketplaceFacilitatorTax-Principal',
    amount            DECIMAL(14,2) NOT NULL,

    posted_date       DATETIME     NULL,

    -- Resolved on import where possible. Null is normal and not a fault: an Amazon seller
    -- sku may not exist in USSCOS at all, and advertising lines have no product.
    product_id        INT UNSIGNED NULL,
    invoice_id        INT UNSIGNED NULL,

    PRIMARY KEY (id),
    KEY idx_settlement (settlement_id),
    KEY idx_order (order_id),
    KEY idx_sku (sku),
    KEY idx_kind (amount_type, amount_description),
    CONSTRAINT fk_msl_settlement FOREIGN KEY (settlement_id)
        REFERENCES marketplace_settlements (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Map an Amazon seller sku to a USSCOS product.
--
-- They are not the same string. Amazon skus look like DSW-10111010, DSW24, ASW10EZ and
-- - for listings Amazon generated itself - PY-ND1J-HXQC, which carries no relation to
-- anything. Guessing by prefix would work often enough to be dangerous, so the mapping is
-- recorded explicitly and anything unmapped is reported rather than assumed.
CREATE TABLE IF NOT EXISTS marketplace_sku_map (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    marketplace    VARCHAR(40)  NOT NULL DEFAULT 'amazon',
    marketplace_sku VARCHAR(80) NOT NULL,
    product_id     INT UNSIGNED NULL,
    units_per_marketplace_unit INT NOT NULL DEFAULT 1
        COMMENT 'DSW24 is two cases sold as one Amazon item - stock must move by 2',
    notes          VARCHAR(255) NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_mp_sku (marketplace, marketplace_sku),
    KEY idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
