-- Where an order came from, how urgent it is, and whether stock can actually fill it.
--
-- CHANNEL. Orders arrive three ways today — a phone call or customer PO keyed by sales,
-- Amazon, and customer pickup. Amazon orders are currently printed and typed into
-- QuickBooks, so they never reach USSCOS at all. When the Amazon link is built they will
-- arrive here directly as sales orders, and shipping needs to see at a glance which
-- channel an order came from because the obligations differ.
--
-- PRIORITY. Amazon orders must ship the same day or account standing suffers. That is a
-- harder deadline than anything a phone order carries, so the shipping queue has to put
-- them first rather than relying on someone remembering. Priority is stored separately
-- from channel because an ordinary order can also be urgent.
--
-- SHIP BY. A date is not enough for a same-day obligation — "today" stops being useful at
-- about 3pm. A datetime lets the queue show what is at risk while there is still time to
-- act on it.
--
-- FULFILLABLE. Sales orders that cannot be filled from stock need to be visible as a
-- group rather than discovered one at a time at the shelf. This column is a cached
-- verdict, recalculated when stock moves or the order changes — not a source of truth.
-- The truth is always stock minus what is committed.
--
-- NOTE no semicolons in these comments — database/migrate.php splits on them.

ALTER TABLE sales_orders
    ADD COLUMN channel ENUM('direct','amazon','website','pickup','edi','other')
        NOT NULL DEFAULT 'direct'
        COMMENT 'How the order reached us - drives priority and obligations'
        AFTER status,
    ADD COLUMN channel_reference VARCHAR(100) NULL
        COMMENT 'The order id in the originating system, e.g. the Amazon order number'
        AFTER channel,
    ADD COLUMN priority ENUM('normal','high','urgent') NOT NULL DEFAULT 'normal'
        COMMENT 'Amazon defaults to urgent - same-day ship'
        AFTER channel_reference,
    ADD COLUMN ship_by DATETIME NULL
        COMMENT 'Hard deadline where one exists - a date alone cannot express same-day'
        AFTER priority,
    ADD COLUMN fulfillable ENUM('unknown','yes','partial','no') NOT NULL DEFAULT 'unknown'
        COMMENT 'Cached verdict on whether stock can fill this order'
        AFTER ship_by,
    ADD COLUMN fulfillable_checked_at DATETIME NULL AFTER fulfillable,
    ADD INDEX idx_so_channel (channel),
    ADD INDEX idx_so_priority (priority),
    ADD INDEX idx_so_fulfillable (fulfillable);


-- Per line, how much of the shortfall sits on this item. Lets the blocked-orders screen
-- say which product is holding an order up, which is the question that actually gets
-- asked — "what are we waiting on".
ALTER TABLE sales_order_line_items
    ADD COLUMN qty_available DECIMAL(12,4) NULL
        COMMENT 'Stock that could be allocated to this line when last checked'
        AFTER qty_picked;
