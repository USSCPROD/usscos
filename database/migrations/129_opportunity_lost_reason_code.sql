-- A groupable reason for a lost deal, alongside the free-text one.
--
-- WHY A CODE AS WELL AS THE TEXT. lost_reason has existed since the initial commit and has
-- never held a single value - pressing "Lost" on the pipeline board posted the stage and
-- nothing else, so every deal closed lost with no reason recorded. Reporting on it would
-- have produced an empty page.
--
-- Fixing the capture is not enough on its own: free text gives forty spellings of "price"
-- and cannot be grouped, which is how a "top reasons we lose" report ends up being a list
-- of sentences. So the code is what reporting counts, and lost_reason stays as the detail
-- somebody actually reads - "went with Pioneer, 12% under us on the 18oz case".
--
-- The codes themselves live in App\Services\LostReasons, not in an enum here. A new reason
-- should not need a migration, and an enum that has to be ALTERed is how a list of reasons
-- stops reflecting why deals are really being lost.

ALTER TABLE opportunities
    ADD COLUMN lost_reason_code VARCHAR(40) NULL AFTER lost_reason;

-- The report groups by code across closed deals.
ALTER TABLE opportunities
    ADD INDEX idx_opp_lost_reason (stage, lost_reason_code);
