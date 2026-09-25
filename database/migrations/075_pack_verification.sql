-- Pack verification: the second scan, at the bench, before the box is sealed.
--
-- Wrong shipments happen several times a week. Picking already scans, and it refuses an
-- item that is not on the order - but the error that actually happens is subtler than
-- that. Somebody scans the label on the shelf and then grabs from the next bay, or scans
-- ten and puts eight in the box. The pick scan cannot catch either, because as far as it
-- knows the right barcode was read.
--
-- So the box is scanned again as it is packed, and compared against WHAT WAS PICKED rather
-- than what was ordered. A deliberate short pick is not an error - four of ten picked means
-- four in the box is correct - and comparing against the order would flag every short ship
-- as a mismatch and teach people to ignore the warning.
--
-- VERIFICATION IS WORTH SOMETHING ONLY IF IT IS A SECOND LOOK. packed_by is recorded
-- separately from picked_by so that "the same person verified their own pick" is at least
-- visible. Whether it must be a different person is a policy decision for the shipping
-- manager, not something to hard-code before anyone has been asked.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE sales_order_line_items
    ADD COLUMN qty_packed DECIMAL(12,4) NOT NULL DEFAULT 0
        COMMENT 'Scanned into the box at the bench, checked against qty_picked'
        AFTER qty_picked,
    ADD COLUMN pack_note VARCHAR(255) NULL
        COMMENT 'Why packed and picked differ, when they do'
        AFTER qty_packed;

ALTER TABLE sales_orders
    ADD COLUMN pack_status ENUM('not_started','in_progress','verified','mismatch')
        NOT NULL DEFAULT 'not_started'
        COMMENT 'mismatch means the box did not match the pick and somebody accepted it anyway'
        AFTER pick_status,
    ADD COLUMN pack_note VARCHAR(500) NULL AFTER pack_status,
    ADD COLUMN packed_by INT UNSIGNED NULL AFTER pack_note,
    ADD COLUMN packed_at DATETIME NULL AFTER packed_by,
    ADD CONSTRAINT fk_so_packed_by FOREIGN KEY (packed_by) REFERENCES users (id) ON DELETE SET NULL;
