-- Migration: 003_create_activity_log_table
-- Description: Audit log for all business events

CREATE TABLE IF NOT EXISTS activity_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED        NOT NULL,
    user_id         INT UNSIGNED        NULL,
    subject_type    VARCHAR(100)        NOT NULL COMMENT 'Model class name, e.g. Invoice',
    subject_id      INT UNSIGNED        NULL,
    event           VARCHAR(100)        NOT NULL COMMENT 'created, updated, deleted, etc.',
    description     TEXT                NULL,
    old_values      JSON                NULL,
    new_values      JSON                NULL,
    ip_address      VARCHAR(45)         NULL,
    user_agent      VARCHAR(500)        NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company_id (company_id),
    INDEX idx_user_id (user_id),
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_event (event),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
