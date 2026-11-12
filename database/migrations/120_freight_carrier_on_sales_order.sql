-- Who is taking the pallet, decided before it ships.
--
-- Ship Via answers how an order goes and stops at Freight. That is a method, not a
-- carrier: the carrier is Old Dominion or TForce. When somebody rings round for a
-- price and books a pallet by phone, that name needs recording at the point the
-- decision is made - on the order - rather than only when a PRO number turns up at
-- the bench afterwards.
--
-- Nullable, because most orders are parcel and never need it, and because an order
-- can be entered long before anyone has chosen a carrier.
--
-- shipment_tracking.freight_carrier_id still records who actually moved each
-- shipment. This is the intention; that is the record. They can differ, and when
-- they do the shipment is right.

ALTER TABLE sales_orders
    ADD COLUMN freight_carrier_id INT UNSIGNED NULL AFTER ship_via_id,
    ADD CONSTRAINT fk_so_freight_carrier
        FOREIGN KEY (freight_carrier_id) REFERENCES freight_carriers(id)
        ON DELETE SET NULL;
