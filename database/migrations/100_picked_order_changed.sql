-- Tell the warehouse when an order they have already picked gets changed.
--
-- A salesperson can legitimately edit an order after picking has started - a customer
-- changes their mind before anything has gone out. The software copes with that: the
-- pick is carried across and the status drops back. What the software cannot do is put
-- the paint back on the shelf.
--
-- So when a picked line is cut or removed, there are cases of paint sitting on a bench
-- with nothing behind them. Nobody was told. The order looked tidy and the shelf did
-- not, and the difference surfaces weeks later as a count that will not reconcile.
--
-- This records what changed, in words, for whoever next walks to that bay. It is
-- deliberately plain text rather than a structured diff: the reader is a person holding
-- a pallet, and 'DSW cut from 10 to 4 - 6 to go back' is what they need.
--
-- Cleared when somebody acknowledges it, because a warning that never goes away stops
-- being read.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE sales_orders
    ADD COLUMN picked_change_note VARCHAR(600) NULL
        COMMENT 'What changed on an order that had already been picked - for the warehouse'
        AFTER pick_note,
    ADD COLUMN picked_change_at DATETIME NULL
        COMMENT 'When it changed. Cleared when the warehouse acknowledges it'
        AFTER picked_change_note;
