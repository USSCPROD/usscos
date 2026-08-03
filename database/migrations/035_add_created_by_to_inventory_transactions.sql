ALTER TABLE inventory_transactions
    ADD COLUMN created_by INT UNSIGNED NULL AFTER notes,
    ADD CONSTRAINT fk_invtxn_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL;
