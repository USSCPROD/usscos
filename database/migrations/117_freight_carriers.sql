-- The LTL carriers USSC actually ships on.
--
-- Separate from ship_via on purpose. ship_via answers "how does this order ship" on the
-- sales order — Freight, FedEx, CPU — and a salesperson picks it weeks before anybody
-- books anything. This answers "who moved it", which is known on the day the pallet
-- goes and changes shipment to shipment because Keubix book whoever is cheapest.
--
-- tracking_url_template is here for the same reason it is on ship_via: a PRO number is
-- only useful if it becomes a link. {tracking} is substituted. Left null where a
-- carrier has no public lookup, because a link that goes nowhere is worse than plain
-- text.

CREATE TABLE IF NOT EXISTS freight_carriers (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                  VARCHAR(100) NOT NULL,
    scac                  VARCHAR(10)  NULL COMMENT 'Standard Carrier Alpha Code, when they have one',
    tracking_url_template VARCHAR(255) NULL COMMENT '{tracking} is substituted',
    phone                 VARCHAR(30)  NULL,
    notes                 VARCHAR(255) NULL,
    is_active             TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_freight_carrier_name (name),
    INDEX idx_freight_carrier_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which carrier a shipment actually went on. carrier_name stays for anything typed in
-- before this table existed, and for a one-off nobody wants to add to the list.
ALTER TABLE shipment_tracking
    ADD COLUMN freight_carrier_id INT UNSIGNED NULL AFTER carrier_name,
    ADD CONSTRAINT fk_tracking_freight_carrier
        FOREIGN KEY (freight_carrier_id) REFERENCES freight_carriers (id) ON DELETE SET NULL;
