-- North Carolina county sales tax rates, and the ZIPs that resolve to them.
--
-- Rates: NCDOR "Current Sales and Use Tax Rates", effective 1 July 2026 - all 100 counties,
-- the combined state, local and transit figure. Pitt at 7.00 percent matches the Amazon
-- order into Ayden 28513 that started this, which is a useful check on the whole table.
--
-- CORRECTION TO MIGRATION 067. That migration said 8.25 percent "is not a North Carolina
-- rate". That was wrong. Mecklenburg County went to 8.25 percent on 1 July 2026, so the
-- figure is real - it is simply not a statewide one, and was being applied to every NC
-- delivery. The old row is deactivated rather than deleted, since history may point at it.
--
-- ZIPs: the US Census 2020 ZCTA-to-county relationship file, which gives the county or
-- counties each ZIP area falls in. 328 of the 853 NC ZIPs straddle a county line. Each is
-- assigned to the county holding the most of its land area, and flagged ambiguous ONLY
-- where the counties it straddles charge different rates - a ZIP spanning two 7 percent
-- counties has one right answer either way, and flagging it would be noise that teaches
-- people to ignore the flag.
--
-- 28513 is a good example: it spans Greene and Pitt. Both charge 7 percent, so Amazon's
-- figure is right whichever county the address sits in.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE tax_rates
SET is_active = 0,
    source_note = 'Superseded by per-county NC rates. 8.25 percent is Mecklenburg only, and was being charged statewide'
WHERE state_code = 'NC' AND county IS NULL;


-- The 100 counties.
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Alamance County', 'NC', 'Alamance', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Alamance');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Alexander County', 'NC', 'Alexander', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Alexander');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Alleghany County', 'NC', 'Alleghany', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Alleghany');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Anson County', 'NC', 'Anson', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Anson');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Ashe County', 'NC', 'Ashe', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Ashe');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Avery County', 'NC', 'Avery', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Avery');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Beaufort County', 'NC', 'Beaufort', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Beaufort');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Bertie County', 'NC', 'Bertie', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Bertie');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Bladen County', 'NC', 'Bladen', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Bladen');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Brunswick County', 'NC', 'Brunswick', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Brunswick');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Buncombe County', 'NC', 'Buncombe', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Buncombe');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Burke County', 'NC', 'Burke', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Burke');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Cabarrus County', 'NC', 'Cabarrus', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Cabarrus');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Caldwell County', 'NC', 'Caldwell', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Caldwell');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Camden County', 'NC', 'Camden', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Camden');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Carteret County', 'NC', 'Carteret', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Carteret');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Caswell County', 'NC', 'Caswell', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Caswell');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Catawba County', 'NC', 'Catawba', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Catawba');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Chatham County', 'NC', 'Chatham', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Chatham');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Cherokee County', 'NC', 'Cherokee', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Cherokee');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Chowan County', 'NC', 'Chowan', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Chowan');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Clay County', 'NC', 'Clay', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Clay');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Cleveland County', 'NC', 'Cleveland', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Cleveland');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Columbus County', 'NC', 'Columbus', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Columbus');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Craven County', 'NC', 'Craven', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Craven');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Cumberland County', 'NC', 'Cumberland', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Cumberland');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Currituck County', 'NC', 'Currituck', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Currituck');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Dare County', 'NC', 'Dare', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Dare');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Davidson County', 'NC', 'Davidson', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Davidson');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Davie County', 'NC', 'Davie', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Davie');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Duplin County', 'NC', 'Duplin', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Duplin');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Durham County', 'NC', 'Durham', 0, 0.07500, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Durham');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Edgecombe County', 'NC', 'Edgecombe', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Edgecombe');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Forsyth County', 'NC', 'Forsyth', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Forsyth');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Franklin County', 'NC', 'Franklin', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Franklin');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Gaston County', 'NC', 'Gaston', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Gaston');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Gates County', 'NC', 'Gates', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Gates');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Graham County', 'NC', 'Graham', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Graham');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Granville County', 'NC', 'Granville', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Granville');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Greene County', 'NC', 'Greene', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Greene');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Guilford County', 'NC', 'Guilford', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Guilford');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Halifax County', 'NC', 'Halifax', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Halifax');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Harnett County', 'NC', 'Harnett', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Harnett');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Haywood County', 'NC', 'Haywood', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Haywood');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Henderson County', 'NC', 'Henderson', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Henderson');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Hertford County', 'NC', 'Hertford', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Hertford');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Hoke County', 'NC', 'Hoke', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Hoke');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Hyde County', 'NC', 'Hyde', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Hyde');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Iredell County', 'NC', 'Iredell', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Iredell');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Jackson County', 'NC', 'Jackson', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Jackson');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Johnston County', 'NC', 'Johnston', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Johnston');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Jones County', 'NC', 'Jones', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Jones');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Lee County', 'NC', 'Lee', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Lee');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Lenoir County', 'NC', 'Lenoir', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Lenoir');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Lincoln County', 'NC', 'Lincoln', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Lincoln');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Macon County', 'NC', 'Macon', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Macon');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Madison County', 'NC', 'Madison', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Madison');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Martin County', 'NC', 'Martin', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Martin');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - McDowell County', 'NC', 'McDowell', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'McDowell');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Mecklenburg County', 'NC', 'Mecklenburg', 0, 0.08250, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Mecklenburg');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Mitchell County', 'NC', 'Mitchell', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Mitchell');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Montgomery County', 'NC', 'Montgomery', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Montgomery');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Moore County', 'NC', 'Moore', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Moore');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Nash County', 'NC', 'Nash', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Nash');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - New Hanover County', 'NC', 'New Hanover', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'New Hanover');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Northampton County', 'NC', 'Northampton', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Northampton');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Onslow County', 'NC', 'Onslow', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Onslow');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Orange County', 'NC', 'Orange', 0, 0.07500, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Orange');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Pamlico County', 'NC', 'Pamlico', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Pamlico');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Pasquotank County', 'NC', 'Pasquotank', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Pasquotank');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Pender County', 'NC', 'Pender', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Pender');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Perquimans County', 'NC', 'Perquimans', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Perquimans');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Person County', 'NC', 'Person', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Person');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Pitt County', 'NC', 'Pitt', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Pitt');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Polk County', 'NC', 'Polk', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Polk');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Randolph County', 'NC', 'Randolph', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Randolph');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Richmond County', 'NC', 'Richmond', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Richmond');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Robeson County', 'NC', 'Robeson', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Robeson');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Rockingham County', 'NC', 'Rockingham', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Rockingham');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Rowan County', 'NC', 'Rowan', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Rowan');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Rutherford County', 'NC', 'Rutherford', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Rutherford');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Sampson County', 'NC', 'Sampson', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Sampson');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Scotland County', 'NC', 'Scotland', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Scotland');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Stanly County', 'NC', 'Stanly', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Stanly');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Stokes County', 'NC', 'Stokes', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Stokes');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Surry County', 'NC', 'Surry', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Surry');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Swain County', 'NC', 'Swain', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Swain');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Transylvania County', 'NC', 'Transylvania', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Transylvania');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Tyrrell County', 'NC', 'Tyrrell', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Tyrrell');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Union County', 'NC', 'Union', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Union');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Vance County', 'NC', 'Vance', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Vance');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Wake County', 'NC', 'Wake', 0, 0.07250, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Wake');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Warren County', 'NC', 'Warren', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Warren');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Washington County', 'NC', 'Washington', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Washington');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Watauga County', 'NC', 'Watauga', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Watauga');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Wayne County', 'NC', 'Wayne', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Wayne');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Wilkes County', 'NC', 'Wilkes', 0, 0.07000, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Wilkes');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Wilson County', 'NC', 'Wilson', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Wilson');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Yadkin County', 'NC', 'Yadkin', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Yadkin');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, needs_review, source_note, is_active)
SELECT 'NC - Yancey County', 'NC', 'Yancey', 0, 0.06750, '2026-07-01', 0, 'NCDOR current rates, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code = 'NC' AND t.county = 'Yancey');

-- Bring any pre-existing NC county row up to the published figure.
UPDATE tax_rates SET needs_review = 0, effective_from = '2026-07-01',
    source_note = 'NCDOR current rates, effective 1 July 2026'
WHERE state_code = 'NC' AND county IS NOT NULL AND is_active = 1;


-- ZIP to jurisdiction.
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27006', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27007', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27009', 'NC', id, 1, 'Spans Forsyth, Stokes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27011', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Yadkin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27012', 'NC', id, 0, 'Spans Forsyth, Davidson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27013', 'NC', id, 1, 'Spans Rowan, Iredell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27014', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27016', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27017', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27018', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Yadkin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27019', 'NC', id, 1, 'Spans Stokes, Forsyth' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27020', 'NC', id, 1, 'Spans Yadkin, Iredell, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Yadkin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27021', 'NC', id, 1, 'Spans Stokes, Forsyth' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27022', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27023', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27024', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27025', 'NC', id, 1, 'Spans Rockingham, Stokes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rockingham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27027', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rockingham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27028', 'NC', id, 0, 'Spans Davie, Iredell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27030', 'NC', id, 1, 'Spans Surry, Stokes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27040', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27041', 'NC', id, 1, 'Spans Surry, Stokes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27042', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27043', 'NC', id, 1, 'Spans Stokes, Surry, Forsyth' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27045', 'NC', id, 1, 'Spans Forsyth, Stokes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27046', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27047', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27048', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rockingham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27050', 'NC', id, 1, 'Spans Forsyth, Stokes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27051', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27052', 'NC', id, 1, 'Spans Stokes, Forsyth' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27053', 'NC', id, 1, 'Spans Stokes, Surry' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stokes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27054', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27055', 'NC', id, 0, 'Spans Yadkin, Davie, Iredell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Yadkin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27101', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27103', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27104', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27105', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27106', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27107', 'NC', id, 0, 'Spans Davidson, Forsyth' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davidson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27109', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27110', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27127', 'NC', id, 0, 'Spans Forsyth, Davidson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27201', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27202', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27203', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27205', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27207', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27208', 'NC', id, 0, 'Spans Chatham, Randolph, Moore' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27209', 'NC', id, 0, 'Spans Montgomery, Moore' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Montgomery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27212', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caswell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27213', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27214', 'NC', id, 1, 'Spans Guilford, Rockingham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27215', 'NC', id, 0, 'Spans Alamance, Guilford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27217', 'NC', id, 0, 'Spans Alamance, Caswell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27229', 'NC', id, 1, 'Spans Montgomery, Richmond' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Montgomery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27231', 'NC', id, 1, 'Spans Orange, Caswell, Person' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Orange' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27233', 'NC', id, 1, 'Spans Randolph, Guilford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27235', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27239', 'NC', id, 0, 'Spans Davidson, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davidson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27242', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27243', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Orange' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27244', 'NC', id, 0, 'Spans Alamance, Caswell, Guilford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27247', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Montgomery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27248', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27249', 'NC', id, 1, 'Spans Guilford, Alamance, Rockingham, Caswell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27252', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27253', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27256', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27258', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27259', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27260', 'NC', id, 1, 'Spans Guilford, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27262', 'NC', id, 1, 'Spans Guilford, Davidson, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27263', 'NC', id, 1, 'Spans Randolph, Guilford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27265', 'NC', id, 1, 'Spans Guilford, Davidson, Forsyth' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27268', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27278', 'NC', id, 0, 'Spans Orange, Durham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Orange' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27281', 'NC', id, 1, 'Spans Moore, Montgomery, Richmond' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27282', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27283', 'NC', id, 1, 'Spans Guilford, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27284', 'NC', id, 1, 'Spans Forsyth, Guilford, Davidson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Forsyth' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27288', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rockingham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27291', 'NC', id, 0, 'Spans Caswell, Person' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caswell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27292', 'NC', id, 0, 'Spans Davidson, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davidson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27295', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davidson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27298', 'NC', id, 1, 'Spans Randolph, Alamance, Guilford, Chatham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27299', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davidson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27301', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27302', 'NC', id, 1, 'Spans Alamance, Orange, Caswell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27305', 'NC', id, 0, 'Spans Caswell, Person' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caswell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27306', 'NC', id, 1, 'Spans Montgomery, Richmond' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Montgomery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27310', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27311', 'NC', id, 1, 'Spans Caswell, Rockingham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caswell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27312', 'NC', id, 1, 'Spans Chatham, Alamance' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27313', 'NC', id, 1, 'Spans Guilford, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27314', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caswell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27315', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caswell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27316', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27317', 'NC', id, 1, 'Spans Randolph, Guilford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27320', 'NC', id, 1, 'Spans Rockingham, Caswell, Guilford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rockingham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27325', 'NC', id, 0, 'Spans Moore, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27326', 'NC', id, 1, 'Spans Rockingham, Caswell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rockingham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27330', 'NC', id, 0, 'Spans Lee, Chatham, Moore, Harnett' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lee' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27332', 'NC', id, 0, 'Spans Lee, Harnett' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lee' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27340', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27341', 'NC', id, 0, 'Spans Randolph, Moore, Montgomery' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27342', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27343', 'NC', id, 0, 'Spans Person, Caswell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Person' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27344', 'NC', id, 0, 'Spans Chatham, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27349', 'NC', id, 1, 'Spans Alamance, Chatham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alamance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27350', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27351', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davidson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27355', 'NC', id, 0, 'Spans Randolph, Chatham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27356', 'NC', id, 0, 'Spans Montgomery, Moore' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Montgomery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27357', 'NC', id, 1, 'Spans Rockingham, Guilford, Stokes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rockingham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27358', 'NC', id, 1, 'Spans Guilford, Rockingham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27360', 'NC', id, 0, 'Spans Davidson, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davidson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27370', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Randolph' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27371', 'NC', id, 0, 'Spans Montgomery, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Montgomery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27374', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Davidson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27376', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27377', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27379', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caswell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27401', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27403', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27405', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27406', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27407', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27408', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27409', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27410', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27411', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27412', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27455', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Guilford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27501', 'NC', id, 1, 'Spans Harnett, Johnston, Wake' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27502', 'NC', id, 1, 'Spans Wake, Chatham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27503', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27504', 'NC', id, 1, 'Spans Johnston, Harnett' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27505', 'NC', id, 0, 'Spans Harnett, Lee' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27506', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27507', 'NC', id, 0, 'Spans Granville, Vance' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Granville' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27508', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Franklin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27509', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Granville' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27510', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Orange' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27511', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27513', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27514', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Orange' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27516', 'NC', id, 1, 'Spans Orange, Chatham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Orange' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27517', 'NC', id, 1, 'Spans Chatham, Orange, Durham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27518', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27519', 'NC', id, 1, 'Spans Wake, Chatham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27520', 'NC', id, 1, 'Spans Johnston, Wake' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27521', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27522', 'NC', id, 1, 'Spans Granville, Wake' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Granville' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27523', 'NC', id, 1, 'Spans Chatham, Wake' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27524', 'NC', id, 0, 'Spans Johnston, Wayne' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27525', 'NC', id, 0, 'Spans Franklin, Granville' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Franklin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27526', 'NC', id, 1, 'Spans Harnett, Wake' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27527', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27529', 'NC', id, 1, 'Spans Wake, Johnston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27530', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wayne' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27531', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wayne' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27533', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wayne' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27534', 'NC', id, 1, 'Spans Wayne, Greene' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wayne' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27536', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Vance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27537', 'NC', id, 0, 'Spans Vance, Franklin, Warren' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Vance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27539', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27540', 'NC', id, 1, 'Spans Wake, Harnett, Chatham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27541', 'NC', id, 1, 'Spans Person, Orange, Caswell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Person' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27542', 'NC', id, 0, 'Spans Johnston, Wilson, Wayne' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27544', 'NC', id, 0, 'Spans Vance, Franklin, Granville' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Vance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27545', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27546', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27549', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Franklin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27551', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Warren' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27553', 'NC', id, 0, 'Spans Warren, Vance' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Warren' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27555', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27556', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Vance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27557', 'NC', id, 0, 'Spans Nash, Johnston, Wilson, Franklin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Nash' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27559', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27560', 'NC', id, 1, 'Spans Wake, Durham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27562', 'NC', id, 1, 'Spans Chatham, Wake' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chatham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27563', 'NC', id, 0, 'Spans Warren, Vance' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Warren' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27565', 'NC', id, 0, 'Spans Granville, Vance, Person' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Granville' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27568', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27569', 'NC', id, 0, 'Spans Johnston, Wayne' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27570', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Warren' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27571', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27572', 'NC', id, 1, 'Spans Person, Durham, Orange, Granville' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Person' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27573', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Person' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27574', 'NC', id, 0, 'Spans Person, Granville' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Person' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27576', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27577', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27581', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Granville' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27582', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Granville' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27583', 'NC', id, 1, 'Spans Person, Orange, Durham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Person' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27584', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Vance' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27587', 'NC', id, 1, 'Spans Wake, Granville, Franklin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27589', 'NC', id, 0, 'Spans Warren, Franklin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Warren' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27591', 'NC', id, 1, 'Spans Wake, Johnston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27592', 'NC', id, 1, 'Spans Wake, Johnston, Harnett' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27593', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Johnston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27594', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Warren' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27596', 'NC', id, 1, 'Spans Franklin, Wake, Granville' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Franklin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27597', 'NC', id, 1, 'Spans Wake, Franklin, Johnston, Nash' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27599', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Orange' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27601', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27603', 'NC', id, 1, 'Spans Wake, Johnston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27604', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27605', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27606', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27607', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27608', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27609', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27610', 'NC', id, 1, 'Spans Wake, Johnston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27612', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27613', 'NC', id, 1, 'Spans Wake, Durham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27614', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27615', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27616', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27617', 'NC', id, 1, 'Spans Wake, Durham' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27695', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27697', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wake' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27701', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27703', 'NC', id, 1, 'Spans Durham, Wake' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27704', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27705', 'NC', id, 0, 'Spans Durham, Orange' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27707', 'NC', id, 0, 'Spans Durham, Orange' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27708', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27709', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27712', 'NC', id, 0, 'Spans Durham, Orange' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27713', 'NC', id, 1, 'Spans Durham, Chatham, Wake' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Durham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27801', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27803', 'NC', id, 1, 'Spans Nash, Wilson, Edgecombe' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Nash' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27804', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Nash' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27805', 'NC', id, 0, 'Spans Bertie, Hertford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bertie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27806', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Beaufort' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27807', 'NC', id, 0, 'Spans Nash, Wilson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Nash' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27808', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Beaufort' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27809', 'NC', id, 1, 'Spans Edgecombe, Nash' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27810', 'NC', id, 0, 'Spans Hyde, Beaufort' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hyde' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27812', 'NC', id, 0, 'Spans Pitt, Edgecombe, Martin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27813', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27814', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Beaufort' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27815', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27816', 'NC', id, 0, 'Spans Franklin, Nash' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Franklin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27817', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Beaufort' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27818', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hertford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27819', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27820', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27821', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Beaufort' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27822', 'NC', id, 1, 'Spans Wilson, Nash, Edgecombe' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27823', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Halifax' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27824', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hyde' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27825', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Martin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27826', 'NC', id, 0, 'Spans Hyde, Tyrrell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hyde' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27827', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27828', 'NC', id, 0, 'Spans Pitt, Greene' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27829', 'NC', id, 1, 'Spans Pitt, Edgecombe, Wilson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27830', 'NC', id, 0, 'Spans Wayne, Wilson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wayne' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27831', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27832', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27834', 'NC', id, 1, 'Spans Pitt, Beaufort, Edgecombe' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27837', 'NC', id, 1, 'Spans Pitt, Beaufort' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27839', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Halifax' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27840', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Martin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27841', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Martin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27842', 'NC', id, 0, 'Spans Northampton, Warren' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27843', 'NC', id, 0, 'Spans Edgecombe, Martin, Halifax' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27844', 'NC', id, 1, 'Spans Halifax, Warren, Nash, Franklin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Halifax' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27845', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27846', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Martin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27847', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bertie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27849', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bertie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27850', 'NC', id, 1, 'Spans Halifax, Warren' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Halifax' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27851', 'NC', id, 0, 'Spans Wilson, Wayne' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27852', 'NC', id, 1, 'Spans Edgecombe, Pitt, Wilson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27853', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27855', 'NC', id, 1, 'Spans Hertford, Northampton' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hertford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27856', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Nash' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27857', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Martin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27858', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27860', 'NC', id, 1, 'Spans Beaufort, Washington, Hyde' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Beaufort' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27861', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Martin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27862', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27863', 'NC', id, 1, 'Spans Wayne, Greene, Johnston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wayne' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27864', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27865', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Beaufort' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27866', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27869', 'NC', id, 1, 'Spans Northampton, Bertie' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27870', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Halifax' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27871', 'NC', id, 1, 'Spans Martin, Pitt, Beaufort' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Martin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27872', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bertie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27873', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27874', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Halifax' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27875', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hyde' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27876', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27877', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27878', 'NC', id, 1, 'Spans Nash, Wilson, Edgecombe' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Nash' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27879', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27880', 'NC', id, 0, 'Spans Wilson, Nash' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27881', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27882', 'NC', id, 0, 'Spans Nash, Franklin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Nash' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27883', 'NC', id, 1, 'Spans Wilson, Greene, Wayne' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27884', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27885', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hyde' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27886', 'NC', id, 0, 'Spans Edgecombe, Pitt' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Edgecombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27888', 'NC', id, 1, 'Spans Greene, Wilson, Pitt' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Greene' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27889', 'NC', id, 1, 'Spans Beaufort, Pitt, Martin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Beaufort' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27890', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Halifax' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27891', 'NC', id, 1, 'Spans Nash, Edgecombe, Halifax' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Nash' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27892', 'NC', id, 1, 'Spans Martin, Beaufort' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Martin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27893', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27896', 'NC', id, 0, 'Spans Wilson, Nash' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27897', 'NC', id, 1, 'Spans Northampton, Hertford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Northampton' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27909', 'NC', id, 1, 'Spans Pasquotank, Camden' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pasquotank' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27910', 'NC', id, 0, 'Spans Hertford, Bertie' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hertford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27915', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27916', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27917', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27919', 'NC', id, 0, 'Spans Perquimans, Chowan' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Perquimans' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27920', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27921', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Camden' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27922', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hertford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27923', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27924', 'NC', id, 0, 'Spans Bertie, Hertford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bertie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27925', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Tyrrell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27926', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gates' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27927', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27928', 'NC', id, 1, 'Spans Washington, Tyrrell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Washington' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27929', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27932', 'NC', id, 0, 'Spans Chowan, Perquimans' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27935', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gates' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27936', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27937', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gates' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27938', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gates' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27939', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27941', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27942', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hertford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27943', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27944', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Perquimans' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27946', 'NC', id, 0, 'Spans Gates, Chowan, Perquimans' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gates' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27947', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27948', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27949', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27950', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27953', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27954', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27956', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27957', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bertie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27958', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27959', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27960', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hyde' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27962', 'NC', id, 1, 'Spans Washington, Beaufort' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Washington' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27964', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27965', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27966', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27967', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bertie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27968', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27969', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gates' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27970', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Washington' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27972', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27973', 'NC', id, 0, 'Spans Currituck, Camden' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Currituck' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27974', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Camden' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27976', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Camden' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27978', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27979', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gates' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27980', 'NC', id, 0, 'Spans Chowan, Perquimans' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Chowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27981', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27982', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Dare' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27983', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bertie' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27985', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Perquimans' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '27986', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hertford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28001', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28006', 'NC', id, 0, 'Spans Gaston, Lincoln' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28007', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Anson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28009', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28012', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28016', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28017', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28018', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28019', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28020', 'NC', id, 1, 'Spans Cleveland, Rutherford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28021', 'NC', id, 1, 'Spans Gaston, Lincoln, Cleveland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28023', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28024', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28025', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cabarrus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28027', 'NC', id, 1, 'Spans Cabarrus, Mecklenburg' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cabarrus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28031', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28032', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28033', 'NC', id, 0, 'Spans Lincoln, Gaston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lincoln' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28034', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28036', 'NC', id, 1, 'Spans Mecklenburg, Cabarrus, Iredell, Rowan' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28037', 'NC', id, 0, 'Spans Lincoln, Catawba' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lincoln' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28039', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28040', 'NC', id, 1, 'Spans Rutherford, Cleveland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28041', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28042', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28043', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28052', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28054', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28056', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28071', 'NC', id, 0, 'Spans Rowan, Cabarrus, Stanly' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28072', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28073', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28074', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28075', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cabarrus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28076', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28077', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28078', 'NC', id, 1, 'Spans Mecklenburg, Cabarrus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28079', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Union' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28080', 'NC', id, 0, 'Spans Lincoln, Gaston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lincoln' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28081', 'NC', id, 0, 'Spans Cabarrus, Rowan' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cabarrus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28083', 'NC', id, 0, 'Spans Cabarrus, Rowan' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cabarrus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28086', 'NC', id, 1, 'Spans Cleveland, Gaston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28088', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28089', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28090', 'NC', id, 1, 'Spans Cleveland, Lincoln, Burke, Catawba' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28091', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Anson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28092', 'NC', id, 0, 'Spans Lincoln, Gaston, Catawba' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lincoln' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28097', 'NC', id, 0, 'Spans Stanly, Cabarrus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28098', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28101', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28102', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Anson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28103', 'NC', id, 1, 'Spans Union, Anson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Union' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28104', 'NC', id, 1, 'Spans Union, Mecklenburg' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Union' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28105', 'NC', id, 1, 'Spans Mecklenburg, Union' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28107', 'NC', id, 1, 'Spans Cabarrus, Mecklenburg, Union, Stanly' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cabarrus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28108', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Union' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28109', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28110', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Union' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28112', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Union' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28114', 'NC', id, 1, 'Spans Cleveland, Rutherford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28115', 'NC', id, 1, 'Spans Iredell, Rowan' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28117', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28119', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Anson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28120', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28124', 'NC', id, 0, 'Spans Cabarrus, Stanly' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cabarrus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28125', 'NC', id, 1, 'Spans Rowan, Iredell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28127', 'NC', id, 0, 'Spans Stanly, Montgomery, Davidson, Rowan, Randolph' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28128', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28129', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28133', 'NC', id, 1, 'Spans Anson, Union' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Anson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28134', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28135', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Anson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28136', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28137', 'NC', id, 0, 'Spans Stanly, Rowan' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28138', 'NC', id, 0, 'Spans Rowan, Cabarrus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28139', 'NC', id, 1, 'Spans Rutherford, Polk' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28144', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28146', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28147', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28150', 'NC', id, 1, 'Spans Cleveland, Rutherford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28152', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28159', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rowan' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28160', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28163', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Stanly' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28164', 'NC', id, 0, 'Spans Gaston, Lincoln' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Gaston' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28166', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28167', 'NC', id, 1, 'Spans Rutherford, McDowell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28168', 'NC', id, 1, 'Spans Lincoln, Catawba, Burke, Cleveland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lincoln' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28169', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cleveland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28170', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Anson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28173', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Union' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28174', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Union' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28202', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28203', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28204', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28205', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28206', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28207', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28208', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28209', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28210', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28211', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28212', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28213', 'NC', id, 1, 'Spans Mecklenburg, Cabarrus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28214', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28215', 'NC', id, 1, 'Spans Mecklenburg, Cabarrus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28216', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28217', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28223', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28226', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28227', 'NC', id, 1, 'Spans Mecklenburg, Union' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28244', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28262', 'NC', id, 1, 'Spans Mecklenburg, Cabarrus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28269', 'NC', id, 1, 'Spans Mecklenburg, Cabarrus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28270', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28273', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28274', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28277', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28278', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28280', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28282', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mecklenburg' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28301', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28303', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28304', 'NC', id, 1, 'Spans Cumberland, Hoke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28305', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28306', 'NC', id, 1, 'Spans Cumberland, Bladen, Hoke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28307', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28308', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28310', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28311', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28312', 'NC', id, 1, 'Spans Cumberland, Bladen' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28314', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28315', 'NC', id, 1, 'Spans Moore, Hoke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28318', 'NC', id, 0, 'Spans Sampson, Cumberland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28320', 'NC', id, 0, 'Spans Bladen, Columbus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28323', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28325', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28326', 'NC', id, 0, 'Spans Harnett, Moore, Lee' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28327', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28328', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28330', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Richmond' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28331', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28332', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28333', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wayne' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28334', 'NC', id, 1, 'Spans Sampson, Harnett, Cumberland, Johnston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28337', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28338', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Richmond' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28339', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28340', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28341', 'NC', id, 0, 'Spans Sampson, Duplin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28342', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28343', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Scotland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28344', 'NC', id, 0, 'Spans Sampson, Cumberland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28345', 'NC', id, 0, 'Spans Richmond, Scotland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Richmond' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28347', 'NC', id, 1, 'Spans Richmond, Moore' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Richmond' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28348', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28349', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28350', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28351', 'NC', id, 0, 'Spans Scotland, Richmond' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Scotland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28352', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Scotland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28355', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lee' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28356', 'NC', id, 0, 'Spans Cumberland, Harnett' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28357', 'NC', id, 1, 'Spans Robeson, Hoke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28358', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28359', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28360', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28362', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28363', 'NC', id, 0, 'Spans Scotland, Richmond' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Scotland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28364', 'NC', id, 1, 'Spans Robeson, Scotland, Hoke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28365', 'NC', id, 1, 'Spans Duplin, Wayne, Sampson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28366', 'NC', id, 1, 'Spans Sampson, Johnston' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28367', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Richmond' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28368', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28369', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28371', 'NC', id, 1, 'Spans Robeson, Cumberland, Hoke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28372', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28373', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28374', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28375', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28376', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hoke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28377', 'NC', id, 1, 'Spans Hoke, Robeson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Hoke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28378', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28379', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Richmond' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28382', 'NC', id, 1, 'Spans Sampson, Cumberland, Bladen' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28383', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28384', 'NC', id, 1, 'Spans Robeson, Bladen, Cumberland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28385', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28386', 'NC', id, 1, 'Spans Robeson, Hoke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Robeson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28387', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28390', 'NC', id, 0, 'Spans Harnett, Cumberland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Harnett' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28391', 'NC', id, 0, 'Spans Cumberland, Sampson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28392', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28393', 'NC', id, 0, 'Spans Sampson, Duplin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28394', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Moore' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28395', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cumberland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28396', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Scotland' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28398', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28399', 'NC', id, 1, 'Spans Bladen, Cumberland' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28401', 'NC', id, 1, 'Spans New Hanover, Pender' FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28403', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28405', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28409', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28411', 'NC', id, 1, 'Spans New Hanover, Pender' FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28412', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28420', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28421', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pender' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28422', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28423', 'NC', id, 0, 'Spans Columbus, Bladen' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28424', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28425', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pender' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28428', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28429', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28430', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28431', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28432', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28433', 'NC', id, 0, 'Spans Bladen, Columbus' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28434', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28435', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pender' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28436', 'NC', id, 0, 'Spans Columbus, Brunswick' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28438', 'NC', id, 0, 'Spans Columbus, Bladen' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28439', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28441', 'NC', id, 1, 'Spans Sampson, Bladen' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28442', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28443', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pender' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28444', 'NC', id, 1, 'Spans Sampson, Bladen, Duplin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Sampson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28445', 'NC', id, 1, 'Spans Onslow, Pender' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28447', 'NC', id, 1, 'Spans Bladen, Sampson, Pender' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28448', 'NC', id, 0, 'Spans Bladen, Pender' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Bladen' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28449', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28450', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28451', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28452', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28453', 'NC', id, 0, 'Spans Duplin, Sampson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28454', 'NC', id, 1, 'Spans Onslow, Pender' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28455', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28456', 'NC', id, 0, 'Spans Columbus, Bladen, Brunswick' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28457', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pender' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28458', 'NC', id, 0, 'Spans Duplin, Sampson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28460', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28461', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28462', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28463', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28464', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28465', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28466', 'NC', id, 1, 'Spans Duplin, Pender' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28467', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28468', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28469', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28470', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28472', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Columbus' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28478', 'NC', id, 1, 'Spans Pender, Sampson, Duplin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pender' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28479', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Brunswick' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28480', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'New Hanover' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28501', 'NC', id, 1, 'Spans Lenoir, Jones' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lenoir' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28504', 'NC', id, 1, 'Spans Lenoir, Jones' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lenoir' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28508', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28509', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28510', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28511', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28512', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28513', 'NC', id, 0, 'Spans Pitt, Greene' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28515', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28516', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28518', 'NC', id, 0, 'Spans Duplin, Onslow' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28519', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28520', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28521', 'NC', id, 0, 'Spans Duplin, Onslow' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28523', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28524', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28525', 'NC', id, 1, 'Spans Lenoir, Duplin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lenoir' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28526', 'NC', id, 1, 'Spans Craven, Jones, Lenoir' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28527', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28528', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28529', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28530', 'NC', id, 1, 'Spans Pitt, Lenoir, Craven, Greene' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28531', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28532', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28533', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28537', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28538', 'NC', id, 1, 'Spans Greene, Lenoir' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Greene' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28539', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28540', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28542', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28543', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28544', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28546', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28547', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28551', 'NC', id, 1, 'Spans Lenoir, Wayne, Greene' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Lenoir' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28552', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28553', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28554', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Greene' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28555', 'NC', id, 1, 'Spans Onslow, Jones, Carteret' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28556', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28557', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28560', 'NC', id, 0, 'Spans Craven, Pamlico' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28562', 'NC', id, 1, 'Spans Craven, Jones' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28570', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28571', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28572', 'NC', id, 1, 'Spans Duplin, Lenoir, Jones, Onslow' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Duplin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28573', 'NC', id, 1, 'Spans Jones, Craven' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jones' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28574', 'NC', id, 0, 'Spans Onslow, Duplin, Jones' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28575', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28577', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28578', 'NC', id, 1, 'Spans Wayne, Lenoir, Duplin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wayne' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28579', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28580', 'NC', id, 1, 'Spans Greene, Lenoir' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Greene' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28581', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28582', 'NC', id, 1, 'Spans Onslow, Carteret' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Onslow' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28584', 'NC', id, 1, 'Spans Carteret, Onslow' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28585', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jones' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28586', 'NC', id, 1, 'Spans Craven, Beaufort, Pitt' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Craven' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28587', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pamlico' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28589', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28590', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Pitt' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28594', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Carteret' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28601', 'NC', id, 1, 'Spans Catawba, Burke, Alexander, Caldwell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28602', 'NC', id, 1, 'Spans Catawba, Burke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28604', 'NC', id, 0, 'Spans Watauga, Avery' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Watauga' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28605', 'NC', id, 0, 'Spans Watauga, Caldwell, Avery' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Watauga' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28606', 'NC', id, 1, 'Spans Wilkes, Caldwell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28607', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Watauga' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28609', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28610', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28611', 'NC', id, 0, 'Spans Caldwell, Avery' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caldwell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28612', 'NC', id, 1, 'Spans Burke, Catawba' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28613', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28615', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28616', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Avery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28617', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28618', 'NC', id, 1, 'Spans Watauga, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Watauga' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28619', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28621', 'NC', id, 0, 'Spans Surry, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28622', 'NC', id, 0, 'Spans Avery, Watauga' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Avery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28623', 'NC', id, 0, 'Spans Alleghany, Surry' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alleghany' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28624', 'NC', id, 1, 'Spans Wilkes, Caldwell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28625', 'NC', id, 1, 'Spans Iredell, Alexander, Davie' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28626', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28627', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alleghany' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28628', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28629', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28630', 'NC', id, 1, 'Spans Caldwell, Alexander' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caldwell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28631', 'NC', id, 0, 'Spans Ashe, Alleghany' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28634', 'NC', id, 0, 'Spans Iredell, Davie, Yadkin' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28635', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28636', 'NC', id, 1, 'Spans Alexander, Iredell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alexander' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28637', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28638', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caldwell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28640', 'NC', id, 0, 'Spans Ashe, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28641', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28642', 'NC', id, 1, 'Spans Yadkin, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Yadkin' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28643', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28644', 'NC', id, 0, 'Spans Alleghany, Ashe, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alleghany' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28645', 'NC', id, 1, 'Spans Caldwell, Watauga, Burke, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caldwell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28646', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Avery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28649', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28650', 'NC', id, 0, 'Spans Catawba, Lincoln' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28651', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28652', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Avery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28653', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Avery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28654', 'NC', id, 0, 'Spans Wilkes, Alexander' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28655', 'NC', id, 0, 'Spans Burke, Caldwell, McDowell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28657', 'NC', id, 0, 'Spans Avery, Burke, McDowell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Avery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28658', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28659', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28660', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28662', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Avery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28663', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alleghany' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28664', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Avery' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28665', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28666', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28667', 'NC', id, 0, 'Spans Caldwell, Burke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Caldwell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28668', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alleghany' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28669', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28670', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28671', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28672', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28673', 'NC', id, 0, 'Spans Catawba, Lincoln' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28675', 'NC', id, 0, 'Spans Alleghany, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alleghany' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28676', 'NC', id, 0, 'Spans Surry, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28677', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28678', 'NC', id, 1, 'Spans Iredell, Alexander' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28679', 'NC', id, 0, 'Spans Watauga, Avery' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Watauga' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28681', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Alexander' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28682', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Catawba' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28683', 'NC', id, 0, 'Spans Surry, Wilkes' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Surry' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28684', 'NC', id, 1, 'Spans Ashe, Watauga' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28685', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28689', 'NC', id, 1, 'Spans Iredell, Wilkes, Yadkin, Alexander' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Iredell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28690', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Burke' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28692', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Watauga' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28693', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28694', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Ashe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28697', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Wilkes' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28698', 'NC', id, 1, 'Spans Watauga, Ashe' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Watauga' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28701', 'NC', id, 0, 'Spans Buncombe, Madison' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28702', 'NC', id, 0, 'Spans Graham, Swain' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Graham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28704', 'NC', id, 1, 'Spans Buncombe, Henderson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28705', 'NC', id, 0, 'Spans Mitchell, Yancey' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mitchell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28707', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28708', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Transylvania' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28709', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28710', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28711', 'NC', id, 1, 'Spans Buncombe, McDowell, Rutherford, Henderson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28712', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Transylvania' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28713', 'NC', id, 1, 'Spans Swain, Macon' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Swain' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28714', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Yancey' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28715', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28716', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Haywood' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28717', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28718', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Transylvania' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28719', 'NC', id, 0, 'Spans Swain, Jackson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Swain' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28720', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28721', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Haywood' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28722', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Polk' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28723', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28725', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28726', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28729', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28730', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28731', 'NC', id, 0, 'Spans Henderson, Polk' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28732', 'NC', id, 1, 'Spans Henderson, Buncombe' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28733', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Graham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28734', 'NC', id, 1, 'Spans Macon, Clay' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Macon' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28735', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28736', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28739', 'NC', id, 0, 'Spans Henderson, Transylvania' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28740', 'NC', id, 0, 'Spans Yancey, Mitchell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Yancey' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28741', 'NC', id, 1, 'Spans Macon, Jackson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Macon' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28742', 'NC', id, 0, 'Spans Henderson, Transylvania' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28743', 'NC', id, 0, 'Spans Madison, Haywood' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Madison' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28745', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Haywood' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28746', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Rutherford' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28747', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Transylvania' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28748', 'NC', id, 0, 'Spans Buncombe, Madison' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28749', 'NC', id, 0, 'Spans McDowell, Mitchell' FROM tax_rates
WHERE state_code = 'NC' AND county = 'McDowell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28751', 'NC', id, 0, 'Spans Haywood, Jackson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Haywood' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28752', 'NC', id, 1, 'Spans McDowell, Rutherford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'McDowell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28753', 'NC', id, 0, 'Spans Madison, Buncombe' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Madison' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28754', 'NC', id, 1, 'Spans Madison, Yancey' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Madison' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28755', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Yancey' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28756', 'NC', id, 1, 'Spans Polk, Rutherford' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Polk' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28757', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28758', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28759', 'NC', id, 1, 'Spans Henderson, Buncombe' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28761', 'NC', id, 0, 'Spans McDowell, Burke' FROM tax_rates
WHERE state_code = 'NC' AND county = 'McDowell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28762', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'McDowell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28763', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Macon' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28766', 'NC', id, 0, 'Spans Transylvania, Henderson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Transylvania' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28768', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Transylvania' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28770', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28771', 'NC', id, 0, 'Spans Graham, Swain' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Graham' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28772', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Transylvania' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28773', 'NC', id, 0, 'Spans Polk, Henderson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Polk' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28774', 'NC', id, 1, 'Spans Transylvania, Jackson' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Transylvania' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28775', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Macon' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28777', 'NC', id, 0, 'Spans Mitchell, Avery, McDowell, Yancey' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Mitchell' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28778', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28779', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28781', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Macon' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28782', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Polk' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28783', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28785', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Haywood' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28786', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Haywood' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28787', 'NC', id, 0, 'Spans Buncombe, Madison' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28788', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28789', 'NC', id, 0, 'Spans Jackson, Swain' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Jackson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28790', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28791', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28792', 'NC', id, 1, 'Spans Henderson, Buncombe, Polk' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Henderson' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28801', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28803', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28804', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28805', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28806', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Buncombe' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28901', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cherokee' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28902', 'NC', id, 0, 'Spans Clay, Cherokee' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Clay' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28904', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Clay' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28905', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cherokee' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28906', 'NC', id, 0, 'Spans Cherokee, Clay' FROM tax_rates
WHERE state_code = 'NC' AND county = 'Cherokee' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '28909', 'NC', id, 0, NULL FROM tax_rates
WHERE state_code = 'NC' AND county = 'Clay' AND is_active = 1 LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id = VALUES(tax_rate_id), is_ambiguous = VALUES(is_ambiguous), notes = VALUES(notes);
