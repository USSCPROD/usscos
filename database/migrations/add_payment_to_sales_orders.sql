-- Add paid status and payment fields to sales_orders
ALTER TABLE sales_orders
    MODIFY COLUMN status ENUM('draft','confirmed','processing','partially_shipped','shipped','paid','invoiced','cancelled') NOT NULL DEFAULT 'draft',
    ADD COLUMN payment_method    VARCHAR(50)     NULL AFTER internal_notes,
    ADD COLUMN payment_reference VARCHAR(100)    NULL AFTER payment_method,
    ADD COLUMN payment_amount    DECIMAL(10,2)   NULL AFTER payment_reference,
    ADD COLUMN paid_at           DATETIME        NULL AFTER payment_amount;
