-- Rename 'sent' status to 'pending' on invoices
ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','pending','partial','paid','void','overdue') NOT NULL DEFAULT 'draft';
UPDATE invoices SET status = 'pending' WHERE status = 'sent';
