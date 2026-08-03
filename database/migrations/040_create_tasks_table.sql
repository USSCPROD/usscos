-- Migration: 040_create_tasks_table
-- Description: Tasks for CRM Phase 4

CREATE TABLE IF NOT EXISTS tasks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)        NOT NULL,
    description     TEXT                NULL,
    status          ENUM('open','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
    priority        ENUM('low','medium','high','urgent')               NOT NULL DEFAULT 'medium',
    due_date        DATE                NULL,
    completed_at    DATETIME            NULL,
    assigned_to     INT UNSIGNED        NULL,
    created_by      INT UNSIGNED        NOT NULL,
    -- Polymorphic links (all nullable)
    customer_id     INT UNSIGNED        NULL,
    lead_id         INT UNSIGNED        NULL,
    opportunity_id  INT UNSIGNED        NULL,
    quote_id        INT UNSIGNED        NULL,
    sales_order_id  INT UNSIGNED        NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_assigned_to    (assigned_to),
    INDEX idx_status         (status),
    INDEX idx_due_date       (due_date),
    INDEX idx_customer_id    (customer_id),
    INDEX idx_lead_id        (lead_id),
    INDEX idx_opportunity_id (opportunity_id),
    CONSTRAINT fk_tasks_assigned FOREIGN KEY (assigned_to) REFERENCES users(id)  ON DELETE SET NULL,
    CONSTRAINT fk_tasks_created  FOREIGN KEY (created_by)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_tasks_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_tasks_lead     FOREIGN KEY (lead_id)     REFERENCES leads(id)     ON DELETE SET NULL,
    CONSTRAINT fk_tasks_opp      FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE SET NULL,
    CONSTRAINT fk_tasks_quote    FOREIGN KEY (quote_id)    REFERENCES quotes(id)    ON DELETE SET NULL,
    CONSTRAINT fk_tasks_so       FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
