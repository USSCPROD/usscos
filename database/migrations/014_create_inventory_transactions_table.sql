-- Inventory Transactions
-- Source: QuickBooks Inventory Valuation Summary
-- Tracks every movement: sales (negative), receipts (positive), adjustments
-- Columns observed: Type, Date, Name, Num, Qty, Cost, On Hand, U/M, Avg Cost, Asset Value
CREATE TABLE inventory_transactions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      INT UNSIGNED NOT NULL,
    transaction_type ENUM(
                        'receipt',      -- purchase / bill receipt
                        'sale',         -- invoice shipment (qty negative)
                        'adjustment',   -- manual inventory adjustment
                        'assembly_in',  -- finished assembly added to stock
                        'assembly_out', -- components consumed in assembly
                        'transfer',     -- location transfer (future)
                        'return_in',    -- customer return
                        'return_out'    -- vendor return
                    ) NOT NULL,

    reference_type  ENUM('invoice','bill','adjustment','assembly') NULL,
    reference_id    INT UNSIGNED NULL,   -- FK polymorphic — invoice_id or bill_id
    reference_num   VARCHAR(50) NULL,    -- QB Num field for display

    transaction_date DATE NOT NULL,
    qty             DECIMAL(12,4) NOT NULL,   -- positive = in, negative = out
    unit_cost       DECIMAL(12,4) NULL,
    total_cost      DECIMAL(14,2) NULL,
    qty_on_hand_after DECIMAL(12,4) NULL,     -- running balance after this txn
    avg_cost_after  DECIMAL(12,4) NULL,
    asset_value_after DECIMAL(14,2) NULL,

    uom_id          INT UNSIGNED NULL,
    notes           TEXT NULL,

    -- QuickBooks sync
    quickbooks_id   VARCHAR(50) NULL,
    last_synced_at  TIMESTAMP NULL,

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_invtxn_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_invtxn_uom     FOREIGN KEY (uom_id)     REFERENCES units_of_measure (id) ON DELETE SET NULL,

    INDEX idx_invtxn_product  (product_id),
    INDEX idx_invtxn_date     (transaction_date),
    INDEX idx_invtxn_type     (transaction_type),
    INDEX idx_invtxn_ref      (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
