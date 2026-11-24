-- Email logged into the CRM via the BCC mailbox.
--
-- WHY A TABLE AND NOT JUST activity_log. Three things the log cannot do:
--
--  1. Deduplicate. The same message can arrive twice - a retried delivery, a flow run
--     twice, a rep who BCCs and forwards. message_id is UNIQUE, so a repeat is rejected by
--     the database rather than by remembering to check.
--  2. Hold what did NOT match. An email to an address nobody has on record is the whole
--     reason this needs a queue: silently dropping it means the feature quietly misses
--     half the mail and nobody finds out, which is exactly how the website's artwork
--     uploads failed for two months.
--  3. Keep the full body. activity_log.description is a sentence for a timeline; an email
--     is an email, and the original has to survive so an unmatched one can be read and
--     attached by hand later.
--
-- A matched message gets a note.email entry in activity_log as well, and activity_id
-- points at it. The log stays the single timeline; this is the intake record behind it.

CREATE TABLE IF NOT EXISTS email_intake (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- RFC 5322 Message-ID. UNIQUE is the dedup: a second POST of the same message is
    -- refused by the index, not by application logic that could be skipped.
    message_id          VARCHAR(255)  NOT NULL,

    direction           ENUM('inbound','outbound') NOT NULL DEFAULT 'inbound',
    from_email          VARCHAR(255)  NOT NULL,
    from_name           VARCHAR(255)  NULL,
    to_emails           TEXT          NULL COMMENT 'Every recipient, comma separated, as received',
    subject             VARCHAR(500)  NULL,
    body                MEDIUMTEXT    NULL COMMENT 'Plain text; HTML is reduced before storing',
    sent_at             DATETIME      NULL COMMENT 'From the message itself, not when we received it',

    matched_customer_id INT UNSIGNED  NULL,
    matched_lead_id     INT UNSIGNED  NULL,
    matched_on          VARCHAR(255)  NULL COMMENT 'The address or domain that matched',
    match_type          VARCHAR(20)   NULL COMMENT 'address | domain | manual',
    activity_id         BIGINT UNSIGNED NULL COMMENT 'The activity_log row this produced',

    status              ENUM('matched','unmatched','ignored') NOT NULL DEFAULT 'unmatched',
    created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_email_message (message_id),
    INDEX idx_email_status (status, created_at),
    INDEX idx_email_customer (matched_customer_id),
    INDEX idx_email_lead (matched_lead_id),

    -- SET NULL, not CASCADE. Deleting a customer must not delete the record of what was
    -- said to them.
    CONSTRAINT fk_email_customer FOREIGN KEY (matched_customer_id)
        REFERENCES customers (id) ON DELETE SET NULL,
    CONSTRAINT fk_email_lead FOREIGN KEY (matched_lead_id)
        REFERENCES leads (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
