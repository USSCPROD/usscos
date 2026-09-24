-- Sales tax, calculated by USSCOS rather than by QuickBooks.
--
-- Invoices are now raised here and pushed to QuickBooks, which makes USSCOS the
-- calculator of record: the number on the customer's invoice is the one an auditor asks
-- about. Three things follow from that, and none of them are true of the old setup.
--
-- 1. TAX FOLLOWS THE SHIP-TO ADDRESS, not the customer's billing address and not one
--    fixed county. Georgia is destination-sourced across 159 counties, so "Forsyth
--    County" as a single rate is wrong for every delivery outside it.
--
-- 2. THE RATE MUST BE FROZEN ONTO THE INVOICE. Rates change. An invoice that stores only
--    an amount cannot be explained afterwards, and the Georgia return is filed BY
--    JURISDICTION - sales and tax broken out per county - which is unanswerable later if
--    the county was never recorded.
--
-- 3. WE ONLY COLLECT WHERE WE ARE REGISTERED. After Wayfair a state can require
--    collection once nexus exists, by physical presence or by crossing an economic
--    threshold. Having a rate for a state is not the same as owing tax there, so the two
--    are separate: tax_nexus_states says where we collect, tax_rates says how much.
--
-- MARKETPLACE SALES ARE NOT OURS TO TAX. Amazon collects and remits on its own orders. We
-- record what it collected as a memo figure so the deposit can be reconciled and the
-- return can report and deduct it - but it never lands in the same column as tax we owe.
--
-- NOTE no semicolons in these comments. See the note in 054.


-- Where we are registered to collect. Absent or inactive means we do not charge, however
-- many rates happen to exist for that state.
CREATE TABLE IF NOT EXISTS tax_nexus_states (
    state_code    CHAR(2)      NOT NULL PRIMARY KEY,
    collects      TINYINT(1)   NOT NULL DEFAULT 1,
    basis         ENUM('physical','economic','voluntary') NOT NULL DEFAULT 'physical',
    registered_on DATE         NULL,
    notes         VARCHAR(255) NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tax_nexus_states (state_code, collects, basis, notes) VALUES
    ('GA', 1, 'physical', 'Premises and warehouses'),
    ('NC', 1, 'physical', 'Confirmed by the bookkeeper as a state we collect in')
ON DUPLICATE KEY UPDATE collects = VALUES(collects);


-- tax_rates becomes the jurisdiction table rather than a short list of named rates. It
-- keeps its id, because customers, quotes and sales orders already point at it.
ALTER TABLE tax_rates
    ADD COLUMN county          VARCHAR(80) NULL AFTER state_code,
    ADD COLUMN is_state_default TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Used when no county can be resolved for an address in this state'
        AFTER county,
    ADD COLUMN effective_from  DATE NOT NULL DEFAULT '2000-01-01' AFTER rate,
    ADD COLUMN effective_to    DATE NULL
        COMMENT 'NULL means current - a superseded rate is closed off, never edited'
        AFTER effective_from,
    ADD COLUMN needs_review    TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Rate is unverified and must be confirmed before it is trusted'
        AFTER effective_to,
    ADD COLUMN source_note     VARCHAR(255) NULL AFTER needs_review,
    ADD INDEX idx_tax_rate_state (state_code, is_state_default);


-- A delivery address resolves to a jurisdiction through its ZIP. ZIPs do not respect
-- county lines, so a ZIP that spans two counties with different rates is marked ambiguous
-- rather than silently resolved to one of them - that is exactly where tax gets assessed
-- on audit, and a guess that looks confident is worse than a flag.
CREATE TABLE IF NOT EXISTS tax_zip_jurisdictions (
    zip          CHAR(5)      NOT NULL PRIMARY KEY,
    state_code   CHAR(2)      NOT NULL,
    tax_rate_id  INT UNSIGNED NOT NULL,
    is_ambiguous TINYINT(1)   NOT NULL DEFAULT 0
        COMMENT 'ZIP spans more than one jurisdiction - the resolved rate is a best guess',
    notes        VARCHAR(255) NULL,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tax_zip_rate FOREIGN KEY (tax_rate_id) REFERENCES tax_rates (id) ON DELETE CASCADE,
    INDEX idx_tax_zip_state (state_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- What was charged, and why, kept on the invoice itself.
ALTER TABLE invoices
    ADD COLUMN tax_rate_id INT UNSIGNED NULL
        COMMENT 'Jurisdiction this invoice was taxed in'
        AFTER tax_amount,
    ADD COLUMN tax_rate_applied DECIMAL(7,5) NOT NULL DEFAULT 0
        COMMENT 'The rate actually used, frozen - later rate changes must not rewrite history'
        AFTER tax_rate_id,
    ADD COLUMN taxable_subtotal DECIMAL(14,2) NOT NULL DEFAULT 0
        COMMENT 'The base the rate was applied to, so the arithmetic can be re-checked'
        AFTER tax_rate_applied,
    ADD COLUMN tax_source ENUM('none','usscos','marketplace') NOT NULL DEFAULT 'none'
        COMMENT 'Who collected - marketplace tax is Amazon money passing through'
        AFTER taxable_subtotal,
    ADD COLUMN tax_reason VARCHAR(255) NULL
        COMMENT 'Why this rate, or why none - exempt, no nexus, collected by Amazon'
        AFTER tax_source,
    ADD COLUMN marketplace_tax_collected DECIMAL(14,2) NOT NULL DEFAULT 0
        COMMENT 'Tax the marketplace charged the buyer - NOT our liability, never in tax_amount'
        AFTER tax_reason,
    ADD CONSTRAINT fk_invoice_tax_rate FOREIGN KEY (tax_rate_id) REFERENCES tax_rates (id) ON DELETE SET NULL;


-- The same on a sales order, so an Amazon order carries the figure from the moment it is
-- keyed off the packing slip.
ALTER TABLE sales_orders
    ADD COLUMN tax_source ENUM('none','usscos','marketplace') NOT NULL DEFAULT 'none' AFTER tax_amount,
    ADD COLUMN marketplace_tax_collected DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tax_source;


-- The existing rows, corrected as far as the evidence allows.
--
-- The NC row said 8.25 percent. A real Amazon order delivered to Ayden NC 28513 on
-- 14 September 2026 was taxed at 5.60 on 79.95, which is 7.00 percent - Amazon resolves
-- the jurisdiction from the delivery address, so that figure is good evidence and 8.25 is
-- not a North Carolina rate. It is left in place but flagged, because silently replacing
-- one unverified number with another is not an improvement. The bookkeeper confirms it.
UPDATE tax_rates
SET needs_review = 1,
    source_note  = 'Unverified. Amazon charged 7.00 percent into Ayden NC 28513 - confirm the correct county rates'
WHERE state_code = 'NC';

UPDATE tax_rates
SET county       = 'Forsyth',
    needs_review = 1,
    source_note  = 'Single county only. Georgia is destination-sourced - the other counties are needed'
WHERE state_code = 'GA';

-- Pitt County NC, evidenced by that order. Marked verified because an actual transaction
-- proves it, which is more than can be said for the rest of the table.
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Pitt County', 'NC', 'Pitt', 0, 0.0700, '2026-01-01', 0,
       'Evidenced by Amazon order 112-1936872-6186629 into Ayden NC 28513', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates WHERE state_code = 'NC' AND county = 'Pitt');

INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28513', 'NC', id, 0, 'Ayden - from the Amazon order'
FROM tax_rates WHERE state_code = 'NC' AND county = 'Pitt'
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id);
