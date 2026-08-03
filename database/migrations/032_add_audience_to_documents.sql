-- Move audience control from categories to individual documents
ALTER TABLE documents
    ADD COLUMN audience SET('internal','reps','distributors','customers') NOT NULL DEFAULT 'internal' AFTER document_category_id;

-- Set existing documents to 'internal' by default (already set by DEFAULT)
-- Categories table keeps its audience column for UI grouping hints only
