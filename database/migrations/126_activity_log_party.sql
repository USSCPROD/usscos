-- Migration: 126_activity_log_party
-- Description: Give activity_log a party axis, so a timeline can be read per customer
--              or per lead rather than only per record.
--
-- WHY. The table as created in migration 003 is keyed on (subject_type, subject_id):
-- it answers "what happened to invoice 412". The question people actually ask is "what
-- has happened with this customer", and the answer spans invoices, orders, quotes,
-- shipments and payments - each of which is its own subject. Without a party column that
-- read is a seven-way UNION that has to be rewritten every time a module is added.
--
-- Both are nullable: plenty of events belong to neither (a product deactivated, a stock
-- count posted), and an event about a lead has no customer until the lead converts.

ALTER TABLE activity_log
    ADD COLUMN customer_id INT UNSIGNED NULL AFTER subject_id,
    ADD COLUMN lead_id     INT UNSIGNED NULL AFTER customer_id;

-- created_at is in the index because every timeline reads one party newest-first; without
-- it MySQL filters on the party then sorts the lot.
ALTER TABLE activity_log
    ADD INDEX idx_activity_customer (customer_id, created_at),
    ADD INDEX idx_activity_lead     (lead_id, created_at);

-- ON DELETE SET NULL, not CASCADE. A deleted customer must not take the history of what
-- was done to them with it - that history is the point.
ALTER TABLE activity_log
    ADD CONSTRAINT fk_activity_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_activity_lead FOREIGN KEY (lead_id)
        REFERENCES leads (id) ON DELETE SET NULL;
