-- Keep the label PDF with the tracking number it belongs to.
--
-- createLabel already saves the PDF under public/uploads/labels and returns its path,
-- and then nothing stored it. The file was on disk with no way to find it again, so a
-- label could be created exactly once and never reprinted - and a label that cannot be
-- reprinted is one jammed printer away from a parcel nobody can ship.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE shipment_tracking
    ADD COLUMN label_path VARCHAR(500) NULL
        COMMENT 'Web path to the carrier label PDF, so it can be reprinted'
        AFTER tracking_type;
