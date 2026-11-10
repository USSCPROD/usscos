-- A rep is an employee, not a customer.
--
-- USSC invoices the customer; the rep never does. So there is nothing for a "Rep"
-- customer account to be, and offering the type invites somebody to classify a real
-- account as one and quietly give it distributor pricing.
--
-- Deactivated rather than deleted. No customer carries it today, but a classification
-- that disappears from the table takes any future import's row with it — and deleting
-- a lookup is not the same as not offering it.

UPDATE customer_types SET is_active = 0 WHERE name = 'Rep';
