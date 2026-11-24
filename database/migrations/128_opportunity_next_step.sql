-- The next step on an open opportunity, and when it is due.
--
-- WHY. An opportunity could sit in 'prospecting' for four months with nobody noticing.
-- Tasks exist and the dashboard already nags about the ones that are overdue, but nothing
-- ever CREATES a task from an opportunity, so an opportunity with nothing planned against
-- it generates no task, raises no flag, and is invisible until somebody scrolls the
-- pipeline and recognises the name.
--
-- probability and expected_close already exist and do not answer this. A probability is a
-- number nobody maintains honestly, and expected_close is a forecast - neither says what
-- anybody is supposed to DO next, which is the only part a rep can act on this morning.
--
-- next_step is free text on purpose: "ring the AD about field dimensions" is worth more
-- than any list of options we could write, and a dropdown would get a 'Other' entry within
-- a week.

ALTER TABLE opportunities
    ADD COLUMN next_step      VARCHAR(255) NULL AFTER probability,
    ADD COLUMN next_step_date DATE         NULL AFTER next_step;

-- The nag reads open opportunities ordered by how late their next step is.
ALTER TABLE opportunities
    ADD INDEX idx_opp_next_step (stage, next_step_date);
