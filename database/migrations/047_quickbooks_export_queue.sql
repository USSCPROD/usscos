-- Migration: 047_quickbooks_export_queue
-- Description: Tracking so records can be handed to QuickBooks exactly once.
--
-- USSCOS is NOT the ledger of record — QuickBooks is. Invoices, sales orders and
-- payments are pushed across daily by a Windows-side script that reads an API endpoint
-- here and writes through QODBC. See docs/QUICKBOOKS_SYNC.md.
--
-- The design goal is that a lost acknowledgement can never cause a duplicate in
-- QuickBooks:
--   qb_exported_at  when the Windows side confirmed a successful write
--   qb_txn_id       the TxnID QuickBooks assigned — the definitive proof it landed
--   qb_export_error last failure message, so a stuck record is visible
-- A record is "pending" only while qb_exported_at IS NULL. If an ack is lost the record
-- stays pending and is offered again; the Windows side checks QuickBooks for the
-- RefNumber before inserting, so the retry updates rather than duplicates.

ALTER TABLE invoices
    ADD COLUMN qb_exported_at  TIMESTAMP    NULL AFTER last_synced_at,
    ADD COLUMN qb_txn_id       VARCHAR(64)  NULL AFTER qb_exported_at,
    ADD COLUMN qb_export_error TEXT         NULL AFTER qb_txn_id,
    ADD INDEX idx_invoices_qb_pending (qb_exported_at);

ALTER TABLE sales_orders
    ADD COLUMN qb_exported_at  TIMESTAMP    NULL,
    ADD COLUMN qb_txn_id       VARCHAR(64)  NULL,
    ADD COLUMN qb_export_error TEXT         NULL,
    ADD INDEX idx_so_qb_pending (qb_exported_at);

ALTER TABLE payments
    ADD COLUMN qb_exported_at  TIMESTAMP    NULL,
    ADD COLUMN qb_txn_id       VARCHAR(64)  NULL,
    ADD COLUMN qb_export_error TEXT         NULL,
    ADD INDEX idx_payments_qb_pending (qb_exported_at);


-- Audit trail of every batch handed over, so "did last night's sync run?" is answerable.
CREATE TABLE IF NOT EXISTS qb_export_log (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    record_type   ENUM('invoice','sales_order','payment') NOT NULL,
    record_id     INT UNSIGNED  NOT NULL,
    reference     VARCHAR(64)   NULL,        -- invoice/SO number, for reading the log
    action        ENUM('sent','acknowledged','failed') NOT NULL,
    qb_txn_id     VARCHAR(64)   NULL,
    message       TEXT          NULL,
    batch_id      VARCHAR(40)   NULL,        -- groups one pull together
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_qbl_record (record_type, record_id),
    INDEX idx_qbl_batch  (batch_id),
    INDEX idx_qbl_action (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- Backfill: everything that predates the sync is already in QuickBooks —
-- the invoice history was imported FROM it. Marking these exported prevents the
-- first run from trying to push 34,729 historical invoices back into QuickBooks.
-- ---------------------------------------------------------------------------

UPDATE invoices
SET qb_exported_at = COALESCE(last_synced_at, created_at, NOW()),
    qb_txn_id      = quickbooks_id
WHERE qb_exported_at IS NULL;

UPDATE sales_orders SET qb_exported_at = COALESCE(created_at, NOW()) WHERE qb_exported_at IS NULL;
UPDATE payments     SET qb_exported_at = COALESCE(created_at, NOW()) WHERE qb_exported_at IS NULL;
