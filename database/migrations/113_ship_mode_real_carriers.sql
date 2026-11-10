-- The carrier list on the server is not the one the seed migration created.
--
-- 112 set ship_mode by name against 'Customer Pick-Up' and 'Will Call', which do not
-- exist here: the real rows are 'Customer Pickup' and 'USSC Delivery'. Both fell
-- through to 'other', and 'other' is drawn as "no carrier yet" — so an order going out
-- on USSC's own truck appeared on the screens as one nobody had routed.
--
-- 'delivery' is added rather than folded into an existing mode. Our own truck is not
-- a parcel, not an LTL carrier and not the customer collecting; putting it in any of
-- those three puts it on somebody's bench who is not doing that work.

ALTER TABLE ship_via
    MODIFY COLUMN ship_mode ENUM('parcel','freight','pickup','delivery','other')
        NOT NULL DEFAULT 'other'
        COMMENT 'Which bench works it: parcel, freight, our own truck, collection, or neither';

UPDATE ship_via SET ship_mode = 'pickup'   WHERE name IN ('Customer Pickup', 'Customer Pick-Up', 'Will Call');
UPDATE ship_via SET ship_mode = 'delivery' WHERE name = 'USSC Delivery';
