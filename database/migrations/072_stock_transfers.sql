-- Moving stock between buildings.
--
-- A TRANSFER IS A RECORD, NOT A PLACE. Migration 057 gave each warehouse a transit
-- location and 060 deleted them, because a location can only say where something is - it
-- cannot say where it came from AND where it is going, which is the only interesting thing
-- about a pallet on a truck. So the transfer itself is the thing that exists, and it knows
-- both ends.
--
-- STOCK LEAVES WHEN IT IS SCANNED OUT AND ARRIVES WHEN IT IS SCANNED IN. In between it
-- belongs to neither building, and `products.qty_on_hand` - the sum across locations -
-- genuinely dips. That is correct, not a bug: paint on a truck cannot be picked at either
-- end. The transfers screen shows what is in transit so the dip is explainable.
--
-- TWO SCANS IS THE POINT. Scanning only at one end would make the second building's count
-- a matter of trust. Because both ends are scanned, a pallet that leaves 1000 and never
-- reaches 730 is a visible, dated, named discrepancy rather than a slow mystery - and the
-- shipping clerk already told us wrong shipments happen several times a week.
--
-- NOTE no semicolons in these comments. See the note in 054.

CREATE TABLE IF NOT EXISTS stock_transfers (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_number  VARCHAR(20)  NOT NULL,

    from_location_id INT UNSIGNED NOT NULL,
    to_location_id   INT UNSIGNED NOT NULL,

    status           ENUM('draft','in_transit','received','short','cancelled')
                     NOT NULL DEFAULT 'draft'
                     COMMENT 'short means it arrived but not all of it did',

    notes            VARCHAR(500) NULL,

    sent_by          INT UNSIGNED NULL,
    sent_at          DATETIME     NULL,
    received_by      INT UNSIGNED NULL,
    received_at      DATETIME     NULL,

    created_by       INT UNSIGNED NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_transfer_from FOREIGN KEY (from_location_id) REFERENCES stock_locations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_transfer_to   FOREIGN KEY (to_location_id)   REFERENCES stock_locations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_transfer_sent_by     FOREIGN KEY (sent_by)     REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_received_by FOREIGN KEY (received_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_transfer_created_by  FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL,

    UNIQUE KEY uq_transfer_number (transfer_number),
    INDEX idx_transfer_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- qty_sent and qty_received are separate for the same reason qty_picked and qty_shipped
-- are on a sales order: they are different facts, and the gap between them is the finding.
CREATE TABLE IF NOT EXISTS stock_transfer_lines (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_id  INT UNSIGNED  NOT NULL,
    product_id   INT UNSIGNED  NOT NULL,

    qty_sent     DECIMAL(12,4) NOT NULL DEFAULT 0,
    qty_received DECIMAL(12,4) NOT NULL DEFAULT 0,

    note         VARCHAR(255)  NULL COMMENT 'Why this line came up short, when it does',

    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_transfer_line_transfer FOREIGN KEY (transfer_id) REFERENCES stock_transfers (id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_line_product  FOREIGN KEY (product_id)  REFERENCES products (id) ON DELETE RESTRICT,

    UNIQUE KEY uq_transfer_product (transfer_id, product_id),
    INDEX idx_transfer_line_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- A stock movement can point at the transfer it belongs to, which is what lets both legs
-- of one transfer be found together.
ALTER TABLE inventory_transactions
    MODIFY COLUMN reference_type
        ENUM('invoice','bill','adjustment','assembly','purchase_order','transfer') NULL;
