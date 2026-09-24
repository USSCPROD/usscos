-- Customer returns.
--
-- Named as a main cause of the count drifting, and until now there was nowhere to record
-- one at all. Paint comes back, somebody puts it on a shelf, and the system never hears
-- about it - so the count is short by exactly the amount that is physically present.
--
-- ONLY RESELLABLE STOCK GOES BACK ON. That is the whole judgement in a return, and it is
-- made by the person holding the pail, not by the paperwork. Goods left inventory when
-- they were sold, so returning something that gets scrapped needs NO stock movement - the
-- inventory position is already correct. Putting scrap back and writing it off again would
-- be two wrongs that happen to cancel, and would overstate both returns and write-offs.
--
-- THE CREDIT IS RECORDED, NOT ISSUED. The amount owed back is calculated here - including
-- tax at the rate frozen on the original invoice, which is exactly why that rate is stored
-- - but no credit memo is raised. `invoices.invoice_type` has a credit_memo value and
-- nothing in the application filters on it, so a credit memo today would be counted as
-- revenue by Sales by Rep, A/R aging, customer lifetime value and the tax report. Raising
-- credits has to wait until those queries know what a credit memo is.
--
-- NOTE no semicolons in these comments. See the note in 054.

CREATE TABLE IF NOT EXISTS stock_returns (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_number  VARCHAR(20)  NOT NULL,

    customer_id    INT UNSIGNED NOT NULL,
    invoice_id     INT UNSIGNED NULL COMMENT 'What they are returning against, when it is known',
    sales_order_id INT UNSIGNED NULL,

    status         ENUM('draft','received','closed','cancelled') NOT NULL DEFAULT 'draft',

    reason         ENUM(
                       'damaged_in_transit',
                       'wrong_item_sent',    -- ours, and worth counting separately
                       'wrong_item_ordered', -- theirs
                       'over_ordered',
                       'not_needed',
                       'quality',
                       'other'
                   ) NOT NULL DEFAULT 'other',

    -- What we owe them back. Calculated, kept, and handed to the bookkeeper - see the note
    -- above on why no credit memo is raised from here yet.
    credit_amount  DECIMAL(14,2) NOT NULL DEFAULT 0,
    credit_tax     DECIMAL(14,2) NOT NULL DEFAULT 0,
    credit_status  ENUM('not_due','pending','issued') NOT NULL DEFAULT 'pending'
                   COMMENT 'pending means the bookkeeper still owes this customer a credit',

    notes          VARCHAR(500) NULL,

    received_by    INT UNSIGNED NULL,
    received_at    DATETIME     NULL,
    created_by     INT UNSIGNED NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_return_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_return_invoice  FOREIGN KEY (invoice_id)  REFERENCES invoices (id)  ON DELETE SET NULL,
    CONSTRAINT fk_return_so       FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE SET NULL,
    CONSTRAINT fk_return_recv_by  FOREIGN KEY (received_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_return_crea_by  FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL,

    UNIQUE KEY uq_return_number (return_number),
    INDEX idx_return_status (status),
    INDEX idx_return_credit (credit_status),
    INDEX idx_return_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS stock_return_lines (
    id          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    return_id   INT UNSIGNED  NOT NULL,
    product_id  INT UNSIGNED  NOT NULL,

    qty         DECIMAL(12,4) NOT NULL DEFAULT 0,

    -- The judgement that decides whether stock goes back on the shelf.
    item_condition ENUM('resellable','damaged','opened','expired','scrap') NOT NULL DEFAULT 'resellable',

    -- Only set for resellable goods, because only those go anywhere.
    location_id INT UNSIGNED  NULL,

    unit_price  DECIMAL(12,4) NOT NULL DEFAULT 0 COMMENT 'What they paid, from the original invoice',
    line_credit DECIMAL(14,2) NOT NULL DEFAULT 0,

    note        VARCHAR(255)  NULL,

    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_return_line_return   FOREIGN KEY (return_id)   REFERENCES stock_returns (id) ON DELETE CASCADE,
    CONSTRAINT fk_return_line_product  FOREIGN KEY (product_id)  REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_return_line_location FOREIGN KEY (location_id) REFERENCES stock_locations (id) ON DELETE SET NULL,

    INDEX idx_return_line_return (return_id),
    INDEX idx_return_line_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE inventory_transactions
    MODIFY COLUMN reference_type
        ENUM('invoice','bill','adjustment','assembly','purchase_order','transfer','return') NULL;
