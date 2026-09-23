-- Receiving against a purchase order, and the variance when it does not match.
--
-- A PO to TCC is an expectation, not a fact. We order 600 cases, the batch yields less,
-- and the PO is edited afterwards to what was actually made - sometimes twice, once when
-- the bulk is done and again after canning. Whether anyone remembers to edit it is the
-- problem: a receipt that quietly disagrees with its PO is how the count and the
-- paperwork drift apart.
--
-- So the receipt is the truth and the difference is recorded rather than hidden. The old
-- receiving code did the opposite - it capped what it accepted at the outstanding
-- quantity, so 620 arriving against a PO for 600 silently became 600 and twenty cases
-- vanished with no error and no note. That cap is removed with this change.
--
-- These columns are how a variance gets closed: somebody looks at it and either sets the
-- PO to what actually arrived, or accepts the difference with a reason. Until one of
-- those happens the line stays on the review queue.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE purchase_order_lines
    ADD COLUMN variance_note   VARCHAR(500) NULL
        COMMENT 'Why received and ordered differ, entered when the variance is reviewed'
        AFTER qty_received,
    ADD COLUMN variance_ack_by INT UNSIGNED NULL
        COMMENT 'Who reviewed the difference'
        AFTER variance_note,
    ADD COLUMN variance_ack_at DATETIME NULL
        COMMENT 'When it was reviewed - NULL means it is still on the queue'
        AFTER variance_ack_by,
    ADD CONSTRAINT fk_pol_variance_user FOREIGN KEY (variance_ack_by)
        REFERENCES users (id) ON DELETE SET NULL;


-- A stock movement can now point at the purchase order it came in against, which is what
-- makes "show me every receipt on this PO" answerable.
ALTER TABLE inventory_transactions
    MODIFY COLUMN reference_type ENUM('invoice','bill','adjustment','assembly','purchase_order') NULL;
