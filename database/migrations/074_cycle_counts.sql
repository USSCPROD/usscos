-- Cycle counting: rolling counts, so an error surfaces in days instead of at year end.
--
-- COUNTING IS BLIND. The person counting is not shown what the system expects. Given the
-- expected figure, a tired human at the end of a shift confirms it rather than counts it,
-- and the count becomes a transcription of the number it was meant to check. The expected
-- quantity is recorded against each line - so the variance is calculable - but it is only
-- revealed on the review screen afterwards.
--
-- THE EXPECTED FIGURE IS TAKEN WHEN THE LINE IS COUNTED, not when the sheet is printed.
-- Stock keeps moving while somebody walks the racks, and comparing a fresh count against a
-- stale snapshot invents variances that are really just shipments.
--
-- NOTHING MOVES UNTIL THE COUNT IS REVIEWED AND APPLIED. A count is a claim about reality,
-- and a claim worth acting on is worth looking at first. Applying posts ordinary
-- adjustments with the reason count_correction, through the same path as everything else,
-- so a counted correction is as traceable as any other movement.
--
-- COUNTING SOMETHING THAT IS NOT ON THE SHEET IS THE POINT, not an edge case. Stock turning
-- up where the system says there is none is exactly the drift these counts exist to find,
-- so a line can be added to a sheet while counting.
--
-- The same machinery does the opening physical count - that is a full count rather than a
-- cycle, which is the only difference.
--
-- NOTE no semicolons in these comments. See the note in 054.

CREATE TABLE IF NOT EXISTS stock_counts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    count_number VARCHAR(20)  NOT NULL,

    location_id  INT UNSIGNED NOT NULL,
    count_type   ENUM('cycle','full') NOT NULL DEFAULT 'cycle',
    status       ENUM('counting','review','applied','cancelled') NOT NULL DEFAULT 'counting',

    notes        VARCHAR(500) NULL,

    created_by   INT UNSIGNED NULL,
    applied_by   INT UNSIGNED NULL,
    applied_at   DATETIME     NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_count_location FOREIGN KEY (location_id) REFERENCES stock_locations (id) ON DELETE RESTRICT,
    CONSTRAINT fk_count_created  FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_count_applied  FOREIGN KEY (applied_by)  REFERENCES users (id) ON DELETE SET NULL,

    UNIQUE KEY uq_count_number (count_number),
    INDEX idx_count_status (status),
    INDEX idx_count_location (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS stock_count_lines (
    id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    count_id     INT UNSIGNED  NOT NULL,
    product_id   INT UNSIGNED  NOT NULL,

    -- NULL until somebody actually counts it. That is what separates "not counted yet"
    -- from "counted, and there are none" - which are completely different findings and
    -- would be indistinguishable if this defaulted to zero.
    counted_qty  DECIMAL(12,4) NULL,

    -- What the system thought at the moment of counting, not when the sheet was made.
    expected_qty DECIMAL(12,4) NULL,

    counted_by   INT UNSIGNED  NULL,
    counted_at   DATETIME      NULL,
    note         VARCHAR(255)  NULL,

    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_count_line_count   FOREIGN KEY (count_id)   REFERENCES stock_counts (id) ON DELETE CASCADE,
    CONSTRAINT fk_count_line_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_count_line_user    FOREIGN KEY (counted_by) REFERENCES users (id) ON DELETE SET NULL,

    UNIQUE KEY uq_count_product (count_id, product_id),
    INDEX idx_count_line_count (count_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE inventory_transactions
    MODIFY COLUMN reference_type
        ENUM('invoice','bill','adjustment','assembly','purchase_order','transfer','return','count') NULL;
