-- User accounts for the staff who key orders in, taken from the QuickBooks
-- "Processed By" values on 2026 invoices.
--
-- Five of the thirteen names already had accounts and are left alone (Chip Curle,
-- Shane Mooney, Herman Irani, Samantha Bruechert, Poncho Wilson). Three are excluded
-- on purpose:
--   Crystal, Wes  former employees, per the classification in migration 050
--   New1          a generic QuickBooks login, not a person
--
-- PASSWORDS ARE DELIBERATELY UNUSABLE. The placeholder below is not a bcrypt hash, so
-- password_verify() returns false for every input and none of these accounts can log in
-- until a real password is set through Edit User. Do not replace it with a shared or
-- guessable default.
--
-- Email follows the firstname@usscproducts.com pattern used by all seven existing
-- accounts. Last names are unknown for Caleb, Matt, Sharon and Hannah and are left
-- blank for someone to fill in, because the column is NOT NULL.
--
-- INSERT IGNORE keys off the unique email, so re-running changes nothing.

INSERT IGNORE INTO users (company_id, first_name, last_name, email, password, role, is_active)
VALUES
    (1, 'Larry',  'Fitzpatrick', 'larry@usscproducts.com',  '!login-disabled-set-a-password!', 'employee', 1),
    (1, 'Caleb',  '',            'caleb@usscproducts.com',  '!login-disabled-set-a-password!', 'employee', 1),
    (1, 'Matt',   '',            'matt@usscproducts.com',   '!login-disabled-set-a-password!', 'employee', 1),
    (1, 'Sharon', '',            'sharon@usscproducts.com', '!login-disabled-set-a-password!', 'employee', 1),
    (1, 'Hannah', '',            'hannah@usscproducts.com', '!login-disabled-set-a-password!', 'employee', 1);


-- Larry already exists in sales_reps as an employee. Point that row at his new login so
-- the two records are joined up.
UPDATE sales_reps sr
JOIN users u ON u.email = 'larry@usscproducts.com'
SET sr.user_id = u.id
WHERE sr.quickbooks_name = 'Larry Fitz' AND sr.user_id IS NULL;
