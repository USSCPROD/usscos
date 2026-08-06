-- Migration: 049_correct_sales_reps
-- Description: Corrections from Chip's verification of the QuickBooks rep list.
--
--   1. 'Herman Irani' and 'HORMUZ P IRANI' are the same person, and he is the OWNER —
--      not a sales rep. Merge the duplicate, then reclassify.
--   2. 'Larry Fitz' is Larry Fitzpatrick, an EMPLOYEE — not a sales rep.
--
-- Historical attribution is preserved throughout. Those sales really were credited to
-- these people in QuickBooks, so the invoice links stay; only the classification changes.
-- Reporting filters on rep_type = 'person' to get actual commissioned reps, which now
-- correctly excludes the owner and employees.

-- Two new classifications alongside the existing person/house/website/partner/none
ALTER TABLE sales_reps
    MODIFY COLUMN rep_type
        ENUM('person','employee','owner','house','website','partner','none')
        NOT NULL DEFAULT 'person';

-- ---------------------------------------------------------------------------
-- 1. Merge Herman Irani into Hormuz P Irani, then mark him as the owner
-- ---------------------------------------------------------------------------

UPDATE invoices i
JOIN sales_reps herman ON herman.quickbooks_name = 'Herman Irani'
JOIN sales_reps hormuz ON hormuz.quickbooks_name = 'HORMUZ P IRANI'
SET i.sales_rep_id = hormuz.id
WHERE i.sales_rep_id = herman.id;

UPDATE customers c
JOIN sales_reps herman ON herman.quickbooks_name = 'Herman Irani'
JOIN sales_reps hormuz ON hormuz.quickbooks_name = 'HORMUZ P IRANI'
SET c.sales_rep_id = hormuz.id
WHERE c.sales_rep_id = herman.id;

DELETE FROM sales_reps WHERE quickbooks_name = 'Herman Irani';

UPDATE sales_reps
SET name     = 'Hormuz Irani',
    rep_type = 'owner',
    notes    = 'Owner, not a sales rep. QuickBooks also used "Herman Irani" for him — merged here.'
WHERE quickbooks_name = 'HORMUZ P IRANI';

-- ---------------------------------------------------------------------------
-- 2. Larry Fitzpatrick — employee, not a rep
-- ---------------------------------------------------------------------------

UPDATE sales_reps
SET name     = 'Larry Fitzpatrick',
    rep_type = 'employee',
    notes    = 'Employee, not a sales rep. QuickBooks rep name is "Larry Fitz".'
WHERE quickbooks_name = 'Larry Fitz';
