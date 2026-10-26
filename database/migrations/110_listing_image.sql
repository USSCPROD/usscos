-- A listing photo is not the same thing as a product photo.
--
-- A variant group is one listing on the website with a colour picker on it, and the
-- picture that sells it is the group shot of every can. But that shot was uploaded
-- against whichever product the group leads with - White - so White had no way to
-- carry a photo of its own: its slot was already spent on the group.
--
-- is_listing marks an image as belonging to the whole group rather than to the one
-- colour it happens to hang off. Exactly one per group, enforced in the repository.

ALTER TABLE product_images
    ADD COLUMN is_listing TINYINT(1) NOT NULL DEFAULT 0 AFTER is_primary;

CREATE INDEX idx_prodimg_listing ON product_images (is_listing);
