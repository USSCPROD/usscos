ALTER TABLE payments
    ADD COLUMN sales_order_id INT UNSIGNED NULL AFTER customer_id,
    ADD INDEX idx_payments_so (sales_order_id);
