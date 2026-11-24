-- Which SITE a web lead came from.
--
-- Two public sites post to the same webhook and both write source='web' with nothing
-- recording which one sent it. On 13 Nov 2026 a spam rule deleted 117 web leads on that
-- basis, and whether any of them were genuine usscproducts.com enquiries is a question
-- that cannot now be answered - it could only ever have been answered by a column that
-- did not exist.
--
-- leads.source stays the enum (which channel) and source_detail stays the form (which
-- form). This is a third question - which site - and a lead can be any combination of the
-- three, so it gets its own column rather than being folded into either.
--
-- NULL means "not recorded", which is the honest answer for every lead taken before this
-- ran and for any sender that does not identify itself. It must never be backfilled with
-- a guess: a wrong host asserted as fact is worse than a blank, because the blank is the
-- only thing that tells you not to trust it.

ALTER TABLE leads
    ADD COLUMN source_site VARCHAR(100) NULL AFTER source_detail;

-- Reporting reads "web leads from this site, newest first".
ALTER TABLE leads
    ADD INDEX idx_leads_source_site (source_site, created_at);
