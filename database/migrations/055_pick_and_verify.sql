-- Pick-and-verify for the shipping station.
--
-- Picking progress is stored per line rather than held in the browser, because the
-- station is shared: someone starts an order, walks to the shelves, and a different
-- person may finish it. Progress has to survive a walk-away, a re-login, and the
-- tablet going to sleep.
--
-- qty_picked is separate from qty_shipped on purpose. Picked means it is on the bench
-- and verified by scan. Shipped means it left the building and is what Ship & Invoice
-- acts on. Collapsing the two would make a half-picked order look partly shipped.

ALTER TABLE sales_order_line_items
    ADD COLUMN qty_picked DECIMAL(12,4) NOT NULL DEFAULT 0
        COMMENT 'Verified onto the bench by scan or manual confirm'
        AFTER qty_shipped,
    ADD COLUMN pick_note VARCHAR(255) NULL
        COMMENT 'Why this line is short, when it is'
        AFTER qty_picked;


-- Short stock is reported against the order, not just the line, because the shipping
-- team needs to hand the whole order back to whoever owns it. A line-level note alone
-- would sit in a screen nobody else opens.
ALTER TABLE sales_orders
    ADD COLUMN pick_status ENUM('not_started','in_progress','short','ready') NOT NULL DEFAULT 'not_started'
        COMMENT 'Shipping-floor progress, separate from the order status'
        AFTER status,
    ADD COLUMN pick_note VARCHAR(500) NULL
        COMMENT 'What is missing, entered by the shipping team'
        AFTER pick_status,
    ADD COLUMN picked_by INT UNSIGNED NULL AFTER pick_note,
    ADD COLUMN picked_at DATETIME NULL AFTER picked_by,
    ADD CONSTRAINT fk_so_picked_by FOREIGN KEY (picked_by) REFERENCES users (id) ON DELETE SET NULL;
