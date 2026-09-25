-- Digital Job Binder, part three: what happened while the job was made, and what was
-- checked before it left.
--
-- Artwork and the customer's PO are already on the binder. These are the other two things
-- people currently keep in their heads, on a sticky note, or not at all:
--
--   NOTES    what happened - the colour was mixed twice, the customer rang about the
--            shade, the second run used a different batch. Append-only, because a job
--            history that can be quietly edited is not a history.
--
--   QA       what was checked before it shipped, who checked it, and what they found.
--
-- PHOTOS ARE JUST DOCUMENTS. job_documents already stores files against a job with an
-- upload path that refuses to execute anything, so a photo is a doc_type rather than a
-- fourth table. Adding a table would mean a second upload path to keep safe.
--
-- NO QA CHECKS ARE SEEDED. What USSC actually checks before a job ships is not something
-- to guess at - a plausible invented checklist would be accepted and followed, which is
-- worse than an empty one that prompts the right conversation. The checks are added
-- through the admin screen by whoever owns quality.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE job_documents
    MODIFY COLUMN doc_type ENUM(
        'customer_po',
        'signed_proof',
        'bol',
        'packing_slip',
        'correspondence',
        'photo',            -- what it looked like before it went
        'spec',             -- a specification or drawing supplied for the job
        'other'
    ) NOT NULL DEFAULT 'other';


-- What happened on the job, in order. Append-only on purpose.
CREATE TABLE IF NOT EXISTS job_notes (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id INT UNSIGNED NOT NULL,

    note_type      ENUM('production','quality','customer','general') NOT NULL DEFAULT 'general'
                   COMMENT 'Who would go looking for it later',

    body           TEXT         NOT NULL,

    created_by     INT UNSIGNED NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_job_note_so   FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_job_note_user FOREIGN KEY (created_by)     REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_job_note_so (sales_order_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- The checks themselves, which USSC defines. Deliberately empty to begin with.
CREATE TABLE IF NOT EXISTS qa_checks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label       VARCHAR(200) NOT NULL COMMENT 'The question as somebody at the bench would read it',
    help        VARCHAR(255) NULL     COMMENT 'What good looks like, when it is not obvious',
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_required TINYINT(1)   NOT NULL DEFAULT 1
                COMMENT 'A required check must be answered before the job can be signed off',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- What was found on one job. A fail is as much a result as a pass and is kept either way.
CREATE TABLE IF NOT EXISTS job_qa_results (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id INT UNSIGNED NOT NULL,
    qa_check_id    INT UNSIGNED NOT NULL,

    result         ENUM('pass','fail','not_applicable') NOT NULL,
    note           VARCHAR(500) NULL COMMENT 'Required on a fail - a fail with no reason teaches nobody anything',

    checked_by     INT UNSIGNED NULL,
    checked_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_qa_result_so    FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_qa_result_check FOREIGN KEY (qa_check_id)    REFERENCES qa_checks (id) ON DELETE CASCADE,
    CONSTRAINT fk_qa_result_user  FOREIGN KEY (checked_by)     REFERENCES users (id) ON DELETE SET NULL,

    UNIQUE KEY uq_qa_result (sales_order_id, qa_check_id),
    INDEX idx_qa_result_so (sales_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
