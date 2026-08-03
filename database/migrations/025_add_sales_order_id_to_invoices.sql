ALTER TABLE invoices
    ADD COLUMN sales_order_id INT UNSIGNED NULL AFTER customer_id,
    ADD CONSTRAINT fk_invoices_sales_order FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE SET NULL,
    ADD INDEX idx_invoices_sales_order (sales_order_id);
