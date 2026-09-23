-- Digital Job Binder, part two: paperwork filed against the job.
--
-- Artwork already hangs off the sales order. This is everything else that arrives on
-- paper - starting with the CUSTOMER PURCHASE ORDER, the document the customer sent to
-- place the order.
--
-- Today the PO exists only as sales_orders.po_number, a loose string. The document
-- itself - the PDF, the scan, the emailed attachment - has nowhere to live, so when a
-- question comes up months later about what was actually ordered, the answer is in
-- somebody's email rather than in the system.
--
-- KEYED TO THE SALES ORDER, like artwork, so it stays with the job for good. Invoicing
-- does not move or close a binder, and an invoice links back to its sales order, so the
-- PO remains one click away long after the job is closed. That is the whole point.
--
-- NO REVISIONS HERE, unlike artwork. A proof goes through versions and needs an approval
-- trail. A customer PO does not get revised - a changed order means a new PO, which is
-- another document filed alongside the first. Both are kept.
--
-- doc_type is an enum rather than free text so the PO can be found by kind rather than
-- by whatever somebody typed in a title. The other kinds are listed now because they are
-- the paperwork this job already generates, and adding them later would mean an ALTER
-- on a table in use.
--
-- NOTE no semicolons in these comments - database/migrate.php splits on them.

CREATE TABLE IF NOT EXISTS job_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id  INT UNSIGNED NOT NULL,

    doc_type        ENUM(
                        'customer_po',    -- what the customer sent to place the order
                        'signed_proof',   -- a proof returned signed, outside the artwork trail
                        'bol',            -- bill of lading
                        'packing_slip',
                        'correspondence', -- an email or letter worth keeping with the job
                        'other'
                    ) NOT NULL DEFAULT 'other',

    title           VARCHAR(255) NULL COMMENT 'Optional label - the doc_type is usually enough',

    -- The customer's own reference on the document, which is what people search by. Kept
    -- here as well as on the sales order because a job can carry more than one PO and
    -- sales_orders.po_number can only hold the first.
    reference_num   VARCHAR(100) NULL,

    file_path       VARCHAR(500) NOT NULL COMMENT 'Web path under /uploads, not a filesystem path',
    file_name       VARCHAR(255) NOT NULL COMMENT 'What the sender called it',
    file_size       INT UNSIGNED NULL,
    mime_type       VARCHAR(100) NULL,

    notes           VARCHAR(500) NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,

    uploaded_by     INT UNSIGNED NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_job_doc_so FOREIGN KEY (sales_order_id)
        REFERENCES sales_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_job_doc_user FOREIGN KEY (uploaded_by)
        REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_job_doc_so   (sales_order_id),
    INDEX idx_job_doc_type (doc_type),
    INDEX idx_job_doc_ref  (reference_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
