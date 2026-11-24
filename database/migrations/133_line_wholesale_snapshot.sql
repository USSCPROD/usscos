-- The wholesale price, snapshotted onto the invoice line.
--
-- WHY. A rep is sold at wholesale and keeps whatever they sell above it, so their
-- commission is the margin on each line: (what it sold for - wholesale) x quantity.
--
-- That arithmetic needs the wholesale price AS IT WAS when the sale happened, and nothing
-- recorded it. Computing margin from products.dist_price_2026 today means every past
-- commission figure silently restates the moment a price list is updated - somebody's
-- earnings for March change in April because an unrelated price moved, and nobody is told.
-- For a number that is paid to a person, that is not acceptable.
--
-- So the line keeps its own copy, written when the invoice is written. NULL means the line
-- predates this column: commission for those lines can only be estimated against today's
-- price list, and the statement says so rather than presenting it as settled.
--
-- Not backfilled. Filling it with today's distributor price would make 34,737 historical
-- lines look snapshotted when they are a guess, and the NULL is the only thing that tells
-- a reader which is which.

ALTER TABLE invoice_line_items
    ADD COLUMN wholesale_price DECIMAL(12,4) NULL AFTER unit_price
        COMMENT 'Wholesale at the time of invoicing; NULL for lines written before 24 Nov 2026';

-- Commission reads every line of every invoice in a period for one rep, so the join path
-- is invoice_id first.
ALTER TABLE invoice_line_items
    ADD INDEX idx_ili_invoice_product (invoice_id, product_id);
