-- Add rep tracking to invoices
ALTER TABLE invoices
    ADD COLUMN rep_id INT UNSIGNED NULL AFTER created_by,
    ADD CONSTRAINT fk_invoices_rep FOREIGN KEY (rep_id) REFERENCES users (id) ON DELETE SET NULL,
    ADD INDEX idx_invoices_rep (rep_id);
