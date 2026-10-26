-- A pack status for "a person checked it" as distinct from "it was scanned".
--
-- Pallet volume makes scanning every case impractical, and pre-made double boxes
-- carry no barcode to scan at all. The shipper confirms the order matches the pick
-- and moves on. That is a real check and worth recording, but it is not the same
-- evidence as a scan, and folding it into 'verified' would quietly destroy the
-- difference between a box a machine read and a box somebody glanced at.
--
-- Ordered after 'in_progress' and before 'verified' because that is the strength
-- of the evidence, weakest to strongest.

ALTER TABLE sales_orders
    MODIFY COLUMN pack_status
    ENUM('not_started','in_progress','confirmed','verified','mismatch')
    NOT NULL DEFAULT 'not_started';
