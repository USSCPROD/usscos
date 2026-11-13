-- Which form a website lead actually came from.
--
-- The webhook handlers each set a descriptive source - "Website — Contact Form",
-- "Website — Paint Quote Request", "Website — Stencil Quote Request" - and
-- createLead() discarded it and wrote the enum value 'web' instead. So every website
-- lead looked identical and nobody could tell a contact-form enquiry from somebody
-- asking to be quoted for a pallet of paint.
--
-- leads.source stays the enum it is: it answers "which channel", and the reporting
-- and filters built on it keep working. This answers "which form", which is a
-- different question and needs its own column rather than a wider enum - the next
-- source is as likely to be a trade show badge scan as another web form.

ALTER TABLE leads
    ADD COLUMN source_detail VARCHAR(100) NULL AFTER source;
