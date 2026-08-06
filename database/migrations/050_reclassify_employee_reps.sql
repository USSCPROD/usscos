-- Migration: 050_reclassify_employee_reps
-- Description: Second round of corrections from Chip's verification.
--
--   Crystal/Sam       — employees, not reps. Crystal has left; Sam remains.
--   Wes Lee           — employee, no longer with the company.
--   Crystal Thompson  — former employee.
--
-- Everyone else on the list is confirmed as a genuine sales rep.
--
-- As with migration 049, historical attribution is untouched — these sales really were
-- credited to these people in QuickBooks. Only the classification changes, so reporting
-- filtered on rep_type = 'person' now returns actual commissioned reps and nothing else.
--
-- is_active = 0 marks someone no longer with the company. Their past invoices and any
-- customers still pointing at them are left alone; the flag keeps them out of pickers and
-- current-period reporting.

UPDATE sales_reps
SET rep_type  = 'employee',
    is_active = 1,
    notes     = 'Employees, not sales reps. Shared QuickBooks rep code for two people — Crystal has since left, Sam remains. Split if per-person commission is ever needed.'
WHERE quickbooks_name = 'Crystal/Sam';

UPDATE sales_reps
SET rep_type  = 'employee',
    is_active = 0,
    notes     = 'Employee, no longer with the company.'
WHERE quickbooks_name = 'Wes Lee';

UPDATE sales_reps
SET rep_type  = 'employee',
    is_active = 0,
    notes     = 'Former employee.'
WHERE quickbooks_name = 'Crystal Thompson';
