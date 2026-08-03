-- CRM Phase 2: Leads + Pipeline

CREATE TABLE IF NOT EXISTS leads (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name    VARCHAR(255) NOT NULL,
    first_name      VARCHAR(100) NULL,
    last_name       VARCHAR(100) NULL,
    email           VARCHAR(255) NULL,
    phone           VARCHAR(50)  NULL,
    website         VARCHAR(255) NULL,
    source          ENUM('web','referral','trade_show','cold_call','social','email_campaign','other') NOT NULL DEFAULT 'other',
    status          ENUM('new','contacted','qualified','converted','dead') NOT NULL DEFAULT 'new',
    rep_id          INT UNSIGNED NULL,
    customer_id     INT UNSIGNED NULL,   -- set when converted
    notes           TEXT NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_leads_rep      FOREIGN KEY (rep_id)      REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_leads_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_leads_created  FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_leads_status (status),
    INDEX idx_leads_rep    (rep_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opportunities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    lead_id         INT UNSIGNED NULL,
    customer_id     INT UNSIGNED NULL,
    rep_id          INT UNSIGNED NULL,
    stage           ENUM('prospecting','proposal','negotiation','closed_won','closed_lost') NOT NULL DEFAULT 'prospecting',
    expected_value  DECIMAL(14,2) NOT NULL DEFAULT 0,
    probability     TINYINT UNSIGNED NOT NULL DEFAULT 20,  -- percentage
    expected_close  DATE NULL,
    notes           TEXT NULL,
    lost_reason     VARCHAR(255) NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_opp_lead     FOREIGN KEY (lead_id)     REFERENCES leads (id) ON DELETE SET NULL,
    CONSTRAINT fk_opp_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_opp_rep      FOREIGN KEY (rep_id)      REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_opp_created  FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_opp_stage    (stage),
    INDEX idx_opp_rep      (rep_id),
    INDEX idx_opp_lead     (lead_id),
    INDEX idx_opp_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
