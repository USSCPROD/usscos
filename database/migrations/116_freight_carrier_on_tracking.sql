-- Who actually moved the pallet.
--
-- ship_via_id says how an order ships — Freight, FedEx Freight — but not which LTL
-- carrier turned up. Keubix books it, and the carrier changes shipment to shipment:
-- Old Dominion this week, XPO the next. That name is what somebody needs when the
-- customer rings asking where their pallet is, and there was nowhere to put it.
--
-- On the tracking row rather than the order, because a part-shipped order can go out
-- on two carriers on two days.

ALTER TABLE shipment_tracking
    ADD COLUMN carrier_name VARCHAR(100) NULL
        COMMENT 'The LTL carrier that actually moved it, when ship_via does not name one'
        AFTER ship_via_id;
