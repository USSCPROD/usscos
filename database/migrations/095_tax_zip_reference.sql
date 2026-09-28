-- A second opinion on every ZIP, so ambiguity is found rather than guessed at.
--
-- Our rates come from the state revenue departments and each ZIP is assigned to a county
-- from the Census ZCTA file, by dominant land area. Comparing that against Avalara's
-- independently researched ZIP tables showed the two agree on 61 percent of Georgia ZIPs
-- and 69 percent of North Carolina ones.
--
-- THE DISAGREEMENTS ARE THE POINT. Land area turns out to be a weak proxy for where the
-- addresses are - a ZIP can have most of its acreage in a rural county and nearly all its
-- mail in the urban one next door. And some ZIPs genuinely straddle, where neither answer
-- is wrong because the question has no single answer at ZIP level. Both vendors say as
-- much in their own disclaimers.
--
-- So rather than guessing which counties are difficult - the previous approach flagged
-- every ZIP in Fulton, DeKalb and Clayton because Atlanta is known to be awkward - a ZIP is
-- flagged when two independent sources disagree about it. That catches ambiguity nobody
-- suspected, including a cluster on the Stokes and Forsyth county line in North Carolina.
--
-- IT ALSO CLOSES A HOLE. Avalara lists 418 ZIPs we had no mapping for at all, mostly PO
-- Box and newer ZIPs that are not in the Census file. Those resolved to nothing and
-- charged no tax, which is under-collecting rather than being careful.
--
-- The reference rate is never used in preference to ours where we have a county, because
-- the Georgia return is filed BY JURISDICTION and a flat ZIP rate cannot produce that. It
-- is a check, and a fallback where we know nothing.
--
-- NOTE no semicolons in these comments. See the note in 054.

CREATE TABLE IF NOT EXISTS tax_zip_reference (
    zip           CHAR(5)      NOT NULL,
    state_code    CHAR(2)      NOT NULL,
    source        VARCHAR(30)  NOT NULL DEFAULT 'avalara',

    region_name   VARCHAR(120) NULL COMMENT 'What the source calls this area - a county, a city, or a district',
    combined_rate DECIMAL(7,5) NOT NULL,
    state_rate    DECIMAL(7,5) NULL,
    county_rate   DECIMAL(7,5) NULL,
    city_rate     DECIMAL(7,5) NULL,
    special_rate  DECIMAL(7,5) NULL,

    as_of         VARCHAR(7)   NOT NULL COMMENT 'The file month, e.g. 2026-09 - these go stale and it should be visible',
    imported_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (zip, source),
    INDEX idx_zip_ref_state (state_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
