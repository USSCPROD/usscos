-- Shipped invoices wait for the bookkeeper before anything leaves the building.
--
-- Shipping used to email the customer their tracking straight away. It should not: the
-- invoice is not finished at the moment the box goes on the truck, because handling fees
-- are added afterwards. Emailing first means the customer receives one figure and is then
-- billed another, which is the kind of small thing that costs an afternoon on the phone.
--
-- So shipping now QUEUES the invoice rather than announcing it. Sharon reviews it, adds
-- what needs adding, approves it - and approving is what sends the email. One action,
-- with the send as its consequence, rather than two things somebody has to remember to do
-- in the right order.
--
-- review_status is on the INVOICE rather than on the email, because the thing being
-- reviewed is the invoice. The email is what approval causes, not what it is.
--
-- 'not_required' exists for invoices that never went through shipping at all - a counter
-- sale does not need reviewing before it is sent, and lumping those in with the queue
-- would make the queue something people learn to ignore.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE invoices
    ADD COLUMN review_status ENUM('not_required','pending','approved') NOT NULL DEFAULT 'not_required'
        COMMENT 'pending means it shipped and the bookkeeper has not finished with it yet'
        AFTER shipment_email_status,
    ADD COLUMN reviewed_by INT UNSIGNED NULL AFTER review_status,
    ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
    ADD CONSTRAINT fk_invoice_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users (id) ON DELETE SET NULL,
    ADD INDEX idx_invoice_review (review_status);
