-- Who keyed the order in, from the QuickBooks "Processed by" column.
--
-- This is deliberately NOT an attribution field. Sales credit belongs to the rep in
-- `sales_rep_id`; `processed_by` only records who typed the order. The two differ
-- constantly today because reps and distributors cannot enter their own orders, so a
-- salesperson does it for them. Never sum revenue by processed_by.
--
-- Two columns because QuickBooks is loosely kept:
--   * processed_by     — the raw string, always stored, so nothing is lost to a failed match
--   * processed_by_rep_id — resolved to sales_reps where the name matches, for clean joins
-- Most of these names are the rep_type='employee' rows already in sales_reps
-- (Larry Fitz, Crystal/Sam, Wes Lee, Crystal Thompson), but the raw value is kept
-- regardless so unmatched or misspelled entries stay visible and fixable.

ALTER TABLE invoices
    ADD COLUMN processed_by        VARCHAR(150) NULL AFTER sales_rep_id,
    ADD COLUMN processed_by_rep_id INT UNSIGNED NULL AFTER processed_by,
    ADD CONSTRAINT fk_invoices_processed_by FOREIGN KEY (processed_by_rep_id)
        REFERENCES sales_reps (id) ON DELETE SET NULL,
    ADD INDEX idx_invoices_processed_by (processed_by_rep_id);
