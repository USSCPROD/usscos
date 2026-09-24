-- Why stock was adjusted, as a field rather than buried in free text.
--
-- An adjustment is the honest correction path: the moment somebody says the count is
-- wrong and fixes it. Without one people work around the system, which is how the current
-- numbers rotted in the first place. But an adjustment with no reason is just a number
-- changing on its own, and a table full of those teaches nobody anything.
--
-- A reason CODE, not only a note, because the useful question is asked across many
-- adjustments at once: how much are we writing off to damage in a year, is one product
-- always short after a count, is one location worse than the others. Free text cannot
-- answer any of that.
--
-- The codes are the ways stock actually goes missing or turns up here. `found` matters as
-- much as `damaged` - an adjustment upward is as much a signal as one downward, and a
-- system that only lets people write stock off quietly teaches them to hide the rest.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE inventory_transactions
    ADD COLUMN reason_code ENUM(
                    'count_correction', -- a physical count disagreed with the system
                    'damaged',          -- dented, leaking, unsellable
                    'spilled',          -- lost in handling
                    'expired',          -- past shelf life
                    'found',            -- turned up - an upward correction
                    'sample',           -- given away as a sample or for testing
                    'internal_use',     -- consumed by us rather than sold
                    'theft',            -- known loss
                    'other'
                ) NULL
        COMMENT 'Why an adjustment was made - reporting needs this as a field, not prose'
        AFTER transaction_type,
    ADD INDEX idx_invtxn_reason (reason_code);


-- Where stock was counted, and by whom. product_stock already carries counted_at and
-- counted_by from 057 but nothing has ever written to them - setting a location's count is
-- the first thing that does, and "when was this last actually counted" is the question
-- that decides whether a figure can be trusted.
