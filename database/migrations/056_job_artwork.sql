-- Digital Job Binder, part one: artwork with revision history.
--
-- Two tables, because an artwork item and its revisions are different things:
--
--   job_artwork            one design on a job — "48in Walking Man", "Team Logo"
--   job_artwork_revisions  each version of that design, in order
--
-- Approval hangs off the REVISION, not the artwork. The question that matters years
-- later is "which version did the customer actually sign off", and a single approved
-- flag on the artwork cannot answer it once a later revision exists.
--
-- Files are stored the same way as product documents: the web path goes in the table,
-- the bytes go under public/uploads, where nginx refuses to execute anything.
--
-- Deliberately NOT reusing the existing `documents` table. That one is a company-wide
-- library keyed to a category and an audience. Artwork belongs to one job, carries a
-- revision number and an approval trail, and is a different shape.
--
-- NOTE no semicolons in these comments — database/migrate.php splits on them.

CREATE TABLE IF NOT EXISTS job_artwork (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id  INT UNSIGNED NOT NULL,
    title           VARCHAR(255) NOT NULL,
    notes           TEXT         NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_by      INT UNSIGNED NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_job_artwork_so FOREIGN KEY (sales_order_id)
        REFERENCES sales_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_job_artwork_user FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_job_artwork_so (sales_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS job_artwork_revisions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    artwork_id      INT UNSIGNED NOT NULL,
    revision_no     SMALLINT UNSIGNED NOT NULL,

    file_path       VARCHAR(500) NOT NULL COMMENT 'Web path under /uploads, not a filesystem path',
    file_name       VARCHAR(255) NOT NULL COMMENT 'What the customer called it',
    file_size       INT UNSIGNED NULL,
    mime_type       VARCHAR(100) NULL,

    notes           VARCHAR(500) NULL COMMENT 'What changed in this revision',

    -- Approval state. `pending` is the resting state: a revision is neither approved nor
    -- rejected until somebody says so, and that distinction is the point of the binder.
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',

    -- How the decision arrived. Approval usually comes back by email rather than through
    -- the system, so record the route and the person's name as stated, rather than
    -- pretending a USSCOS user clicked approve.
    decision_source ENUM('internal','customer_email','customer_phone','customer_portal') NULL,
    decided_by      INT UNSIGNED NULL COMMENT 'USSCOS user who recorded the decision',
    decided_name    VARCHAR(150)  NULL COMMENT 'Customer contact who gave it, where relevant',
    decided_at      DATETIME      NULL,
    decision_note   VARCHAR(500)  NULL,

    uploaded_by     INT UNSIGNED NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_revision_artwork FOREIGN KEY (artwork_id)
        REFERENCES job_artwork (id) ON DELETE CASCADE,
    CONSTRAINT fk_revision_uploader FOREIGN KEY (uploaded_by)
        REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_revision_decider FOREIGN KEY (decided_by)
        REFERENCES users (id) ON DELETE SET NULL,

    UNIQUE KEY uq_artwork_revision (artwork_id, revision_no),
    INDEX idx_revision_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
