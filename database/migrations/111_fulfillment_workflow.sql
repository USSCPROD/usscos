-- Separate physical fulfillment from invoicing. Existing orders are not migrated.
CREATE TABLE fulfillment_orders (
 sales_order_id INT UNSIGNED PRIMARY KEY,
 billing_policy ENUM('cod','terms') NOT NULL,
 payment_term_id INT UNSIGNED NOT NULL,
 created_by INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id),
 FOREIGN KEY (payment_term_id) REFERENCES payment_terms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE fulfillments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sales_order_id INT UNSIGNED NOT NULL,
 method ENUM('carrier','pickup','delivery') NOT NULL,
 status ENUM('planned','ready','out_for_delivery','completed','cancelled') NOT NULL DEFAULT 'planned',
 request_key VARCHAR(64) NOT NULL UNIQUE,
 scheduled_date DATE NULL,
 driver VARCHAR(150) NULL,
 recipient VARCHAR(150) NULL,
 tracking TEXT NULL,
 notes TEXT NULL,
 invoice_id INT UNSIGNED NULL,
 completed_at DATETIME NULL,
 created_by INT UNSIGNED NOT NULL,
 FOREIGN KEY (sales_order_id) REFERENCES fulfillment_orders(sales_order_id),
 FOREIGN KEY (invoice_id) REFERENCES invoices(id),
 INDEX fulfillment_queue (status,method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE fulfillment_lines (
 fulfillment_id INT UNSIGNED NOT NULL,
 sales_order_line_id INT UNSIGNED NOT NULL,
 qty DECIMAL(12,4) NOT NULL,
 PRIMARY KEY (fulfillment_id,sales_order_line_id),
 FOREIGN KEY (fulfillment_id) REFERENCES fulfillments(id),
 FOREIGN KEY (sales_order_line_id) REFERENCES sales_order_line_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE fulfillment_events (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fulfillment_id INT UNSIGNED NOT NULL,
 event VARCHAR(40) NOT NULL,
 details TEXT NULL,
 user_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (fulfillment_id) REFERENCES fulfillments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE delivery_check_receipts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fulfillment_id INT UNSIGNED NOT NULL UNIQUE,
 invoice_id INT UNSIGNED NOT NULL,
 check_number VARCHAR(100) NOT NULL,
 amount DECIMAL(14,2) NOT NULL,
 collected_by INT UNSIGNED NOT NULL,
 collected_at DATETIME NOT NULL,
 handed_to_accounting_at DATETIME NULL,
 posted_payment_id INT UNSIGNED NULL,
 FOREIGN KEY (fulfillment_id) REFERENCES fulfillments(id),
 FOREIGN KEY (invoice_id) REFERENCES invoices(id),
 FOREIGN KEY (posted_payment_id) REFERENCES payments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE inventory_transactions MODIFY COLUMN reference_type
 ENUM('invoice','bill','adjustment','assembly','purchase_order','transfer','return','count','fulfillment') NULL;
