-- Two gates, and two benches.
--
-- GATE 1. The clerk says an order may be worked before the warehouse sees it. That
-- approval gets its own column rather than a status value: collecting a payment sets
-- status to 'paid' and releasing sets 'partially_shipped', so an approval recorded in
-- status would be silently erased by the next thing that happens to the order. A
-- timestamp is also worth more than a flag - who let this out, and when.
--
-- TWO BENCHES. Parcel and freight are worked by different people, and neither wants
-- the other's orders mixed into their list. ship_via says which carrier; it has never
-- said which kind of shipping that is, so every screen had to guess from the name.

ALTER TABLE sales_orders
    ADD COLUMN approved_at DATETIME     NULL AFTER status,
    ADD COLUMN approved_by INT UNSIGNED NULL AFTER approved_at,
    ADD CONSTRAINT fk_so_approved_by FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL;

CREATE INDEX idx_so_approved ON sales_orders (approved_at);

ALTER TABLE ship_via
    ADD COLUMN ship_mode ENUM('parcel','freight','pickup','other') NOT NULL DEFAULT 'other'
        COMMENT 'Which bench works it: parcel, freight, or neither'
        AFTER carrier_code;

UPDATE ship_via SET ship_mode = 'parcel'  WHERE name IN ('UPS', 'FedEx', 'USPS');
UPDATE ship_via SET ship_mode = 'freight' WHERE name IN ('FedEx Freight', 'Freight');
UPDATE ship_via SET ship_mode = 'pickup'  WHERE name IN ('Customer Pick-Up', 'Will Call');

-- Orders already being worked are approved by fact. Anything picked, invoiced or part
-- shipped would otherwise vanish from the shipping queue the moment this runs, which
-- is a worse first impression of the new gate than it deserves.
UPDATE sales_orders so
SET so.approved_at = COALESCE(so.updated_at, so.order_date, NOW())
WHERE so.approved_at IS NULL
  AND (
        so.status IN ('partially_shipped', 'shipped', 'invoiced')
     OR so.pick_status <> 'not_started'
     OR EXISTS (SELECT 1 FROM invoices i WHERE i.sales_order_id = so.id)
  );
