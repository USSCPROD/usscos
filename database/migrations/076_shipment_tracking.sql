-- Tracking numbers, and telling the customer without anybody retyping.
--
-- Today the tracking number is one varchar on the invoice, typed in by hand after the
-- fact, and the customer is told by Sharon sending an email. Two problems with that: a
-- shipment is often more than one number, and a person in the loop is a person who can be
-- busy.
--
-- MORE THAN ONE NUMBER PER SHIPMENT. Four cartons on a parcel order means four FedEx
-- numbers. An LTL pallet gets a PRO number instead, and sometimes a BOL number as well.
-- One column cannot hold that, so tracking becomes rows - and "look it up if needed"
-- becomes a search rather than a memory.
--
-- invoices.tracking_number IS KEPT as the first number, because the invoice view, the
-- QuickBooks export and the packing slip all read it. It is the headline, not the record.
--
-- EMAILING IS GATED BY THE ENVIRONMENT, ON PURPOSE. 28,646 customers have real email
-- addresses in this database and USSCOS is still in testing. The mode lives in .env rather
-- than in a settings screen precisely so that nobody can turn it on with a mis-click:
-- turning it on requires someone on the server who meant to.
--
--   SHIPMENT_EMAILS=off   nothing is sent, but what would have been sent is recorded
--   SHIPMENT_EMAILS=test  everything goes to SHIPMENT_EMAIL_TEST_TO instead
--   SHIPMENT_EMAILS=on    the customer is emailed
--
-- NOTE no semicolons in these comments. See the note in 054.

CREATE TABLE IF NOT EXISTS shipment_tracking (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    invoice_id     INT UNSIGNED NULL,
    sales_order_id INT UNSIGNED NULL,
    ship_via_id    INT UNSIGNED NULL,

    tracking_number VARCHAR(100) NOT NULL,
    tracking_type   ENUM('parcel','pro','bol','other') NOT NULL DEFAULT 'parcel'
                    COMMENT 'parcel = FedEx/UPS/USPS number, pro = LTL freight PRO, bol = bill of lading',

    notes          VARCHAR(255) NULL,

    created_by     INT UNSIGNED NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_tracking_invoice FOREIGN KEY (invoice_id)     REFERENCES invoices (id) ON DELETE CASCADE,
    CONSTRAINT fk_tracking_so      FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE SET NULL,
    CONSTRAINT fk_tracking_via     FOREIGN KEY (ship_via_id)    REFERENCES ship_via (id) ON DELETE SET NULL,
    CONSTRAINT fk_tracking_user    FOREIGN KEY (created_by)     REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_tracking_invoice (invoice_id),
    INDEX idx_tracking_number  (tracking_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- A tracking number is only useful if it is a link. {tracking} is replaced with the number.
ALTER TABLE ship_via
    ADD COLUMN tracking_url_template VARCHAR(255) NULL
        COMMENT 'Where to look this carrier up - {tracking} is substituted'
        AFTER name,
    ADD COLUMN carrier_code VARCHAR(20) NULL
        COMMENT 'For the carrier API later - fedex, ups, usps'
        AFTER tracking_url_template;

UPDATE ship_via SET tracking_url_template = 'https://www.fedex.com/fedextrack/?trknbr={tracking}', carrier_code = 'fedex' WHERE name = 'FedEx';
UPDATE ship_via SET tracking_url_template = 'https://www.fedex.com/fedextrack/?trknbr={tracking}', carrier_code = 'fedex_freight' WHERE name = 'FedEx Freight';
UPDATE ship_via SET tracking_url_template = 'https://www.ups.com/track?tracknum={tracking}', carrier_code = 'ups' WHERE name = 'UPS';
UPDATE ship_via SET tracking_url_template = 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking}', carrier_code = 'usps' WHERE name = 'USPS';

-- Generic 'Freight' is deliberately left without a template. A PRO number belongs to
-- whichever LTL carrier moved it, so guessing a URL would produce a link that goes nowhere,
-- which is worse than showing the number plainly.


-- Whether the customer has been told, so nobody tells them twice and anybody can see
-- whether it happened.
ALTER TABLE invoices
    ADD COLUMN shipment_email_sent_at DATETIME NULL
        COMMENT 'When the customer was emailed their tracking'
        AFTER tracking_number,
    ADD COLUMN shipment_email_to VARCHAR(255) NULL
        COMMENT 'Where it actually went - during testing this is the test address'
        AFTER shipment_email_sent_at,
    ADD COLUMN shipment_email_status VARCHAR(40) NULL
        COMMENT 'sent, suppressed (emails off), no_address, or the failure'
        AFTER shipment_email_to;
