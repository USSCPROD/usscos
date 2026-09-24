-- A ZIP belongs to a county, not to a dated rate row.
--
-- Loading Georgia's October chart alongside July's exposed a fault in 067. A ZIP pointed
-- at one tax_rates row by id, but a rate row is only valid for its quarter - so every
-- Georgia ZIP ended up pointing at the October row, which is not in effect yet, and the
-- lookup found nothing and fell through to the state default. Every Georgia address was
-- being quoted the wrong rate, and would have silently started working on 1 October.
--
-- The ZIP is a fact about geography and does not change every quarter. The rate does. So
-- the ZIP now records its COUNTY, and the rate for a given date is looked up from that -
-- which is what makes a quarterly rate change take effect on its own.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE tax_zip_jurisdictions
    ADD COLUMN county VARCHAR(80) NULL AFTER state_code,
    ADD INDEX idx_tax_zip_county (state_code, county);

UPDATE tax_zip_jurisdictions z
JOIN tax_rates tr ON tr.id = z.tax_rate_id
SET z.county = tr.county
WHERE z.county IS NULL;


-- Only a row explicitly marked as the state default may act as one.
--
-- stateDefault() ordered by is_state_default and then by id, so with nothing marked it
-- returned whichever county happened to have the lowest id - Appling, 8 percent - and
-- presented it as the rate for the whole of Georgia. An arbitrary county's rate wearing
-- the state's name is worse than having no answer, because nothing about it looks wrong.
--
-- Nothing is marked as a default now, deliberately. Neither state has a meaningful
-- statewide rate, so an address that does not resolve should say so rather than be
-- quietly charged something plausible.
UPDATE tax_rates SET is_state_default = 0 WHERE is_state_default = 1;
