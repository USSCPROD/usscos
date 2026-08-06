-- Migration: 045_customer_intelligence
-- Description: Prerequisites for the Customer Intelligence work.
--
--   1. customers.rep_id BACKFILL — the column already exists (added by migration 026)
--      but is effectively empty: 1 of 41,233 customers had a rep. Without it, "my
--      customers" in the Rep Portal has nothing to query. Reps are recorded on orders,
--      invoices, quotes and leads, so derive it from those.
--   2. sales_goals — nothing anywhere tracks targets, so MTD/YTD vs. goal is impossible.
--
-- Additive and re-runnable. Nothing existing reads these yet.

-- ---------------------------------------------------------------------------
-- 1. Backfill the primary rep on each customer
-- ---------------------------------------------------------------------------
-- Most authoritative source first; each statement only fills rows still null, so the
-- better sources win.

-- a) the rep on their most recent sales order
UPDATE customers c
SET c.rep_id = (
    SELECT so.rep_id FROM sales_orders so
    WHERE so.customer_id = c.id AND so.rep_id IS NOT NULL
    ORDER BY so.order_date DESC, so.id DESC LIMIT 1
)
WHERE c.rep_id IS NULL;

-- b) failing that, their most recent invoice
UPDATE customers c
SET c.rep_id = (
    SELECT i.rep_id FROM invoices i
    WHERE i.customer_id = c.id AND i.rep_id IS NOT NULL
    ORDER BY i.invoice_date DESC, i.id DESC LIMIT 1
)
WHERE c.rep_id IS NULL;

-- c) failing that, their most recent quote
UPDATE customers c
SET c.rep_id = (
    SELECT q.rep_id FROM quotes q
    WHERE q.customer_id = c.id AND q.rep_id IS NOT NULL
    ORDER BY q.quote_date DESC, q.id DESC LIMIT 1
)
WHERE c.rep_id IS NULL;

-- d) failing that, the rep on the lead they were converted from
UPDATE customers c
SET c.rep_id = (
    SELECT l.rep_id FROM leads l
    WHERE l.customer_id = c.id AND l.rep_id IS NOT NULL
    ORDER BY l.id DESC LIMIT 1
)
WHERE c.rep_id IS NULL;


-- ---------------------------------------------------------------------------
-- 2. Sales goals — per rep, per period
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS sales_goals (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NULL,               -- NULL = a company-wide goal
    period_type     ENUM('month','quarter','year') NOT NULL DEFAULT 'month',
    period_start    DATE            NOT NULL,           -- first day of the period
    goal_amount     DECIMAL(14,2)   NOT NULL DEFAULT 0,
    notes           VARCHAR(255)    NULL,
    created_by      INT UNSIGNED    NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_goals_user    FOREIGN KEY (user_id)    REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_goals_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,

    UNIQUE KEY uq_goal_period (user_id, period_type, period_start),
    INDEX idx_goals_period (period_type, period_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- 3. Indexes the profile aggregates depend on
-- ---------------------------------------------------------------------------
-- The 360 block sums invoices per customer and finds their top products across ~41.5k
-- invoice lines. These make that cheap enough to run on every page load.
--
-- Run these individually and ignore "Duplicate key name" — MySQL has no
-- CREATE INDEX IF NOT EXISTS, and some may already be present.

ALTER TABLE invoices           ADD INDEX idx_invoices_cust_date (customer_id, invoice_date);
ALTER TABLE invoice_line_items ADD INDEX idx_ili_product        (product_id);
ALTER TABLE invoice_line_items ADD INDEX idx_ili_invoice        (invoice_id);
