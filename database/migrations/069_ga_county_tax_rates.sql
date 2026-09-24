-- Georgia county sales tax rates, current quarter and the next one.
--
-- Source: GA DOR general rate chart. Georgia republishes EVERY QUARTER, and ten counties
-- change on 1 October 2026 - a week from now - so both charts are loaded with their dates
-- and the change happens on its own rather than needing somebody to remember.
--
-- Changing on 1 October 2026:
--   Charlton       7.0 percent to 8.0 percent
--   Dougherty      8.0 percent to 9.0 percent
--   Heard          7.0 percent to 8.0 percent
--   Houston        7.0 percent to 8.0 percent
--   Jenkins        8.0 percent to 9.0 percent
--   Monroe         8.0 percent to 9.0 percent
--   Richmond       8.5 percent to 9.0 percent
--   Tattnall       8.0 percent to 9.0 percent
--   Towns          8.0 percent to 7.0 percent
--   Wilcox         8.0 percent to 9.0 percent
--
-- GEORGIA IS NOT JUST COUNTIES. In Fulton, DeKalb and Clayton the rate depends on the CITY:
-- the Atlanta parts of Fulton and DeKalb are 8.9 percent against 7.75 and 8 elsewhere, and
-- College Park sits in both Fulton and Clayton at its own rate. A ZIP cannot settle that,
-- and "Atlanta" in an address does not mean inside the city limits - it is the mailing city
-- for a great deal of unincorporated Fulton and DeKalb. So every ZIP in those three
-- counties is flagged for a person to confirm rather than guessed at. Those are the busiest
-- delivery ZIPs in the state, so this matters more than the count of flags suggests.
--
-- ZIP mapping is the Census 2020 ZCTA-to-county file, same as North Carolina: dominant
-- county by land area, flagged where the counties straddled charge different rates.
--
-- NOTE no semicolons in these comments. See the note in 054.

-- A rate is only meaningful with its dates, so the same jurisdiction legitimately appears
-- once per quarter. The unique key on name alone made that impossible - it is the pair
-- that must be unique.
ALTER TABLE tax_rates DROP INDEX name;
ALTER TABLE tax_rates ADD UNIQUE KEY uq_tax_rate_name_period (name, effective_from);

-- The original hand-entered Forsyth row, superseded by the DOR chart. Deactivated rather
-- than deleted because customers and orders may still point at it.
UPDATE tax_rates
SET is_active = 0,
    source_note = 'Superseded by the per-county GA rates from the DOR chart'
WHERE name = 'GA - Forsyth County';


-- Current quarter, to 30 September 2026.
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Appling', 'GA', 'Appling', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Appling' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Atkinson', 'GA', 'Atkinson', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Atkinson' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bacon', 'GA', 'Bacon', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bacon' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Baker', 'GA', 'Baker', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Baker' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Baldwin', 'GA', 'Baldwin', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Baldwin' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Banks', 'GA', 'Banks', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Banks' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Barrow', 'GA', 'Barrow', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Barrow' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bartow', 'GA', 'Bartow', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bartow' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Ben Hill', 'GA', 'Ben Hill', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Ben Hill' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Berrien', 'GA', 'Berrien', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Berrien' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bibb', 'GA', 'Bibb', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bibb' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bleckley', 'GA', 'Bleckley', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bleckley' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Brantley', 'GA', 'Brantley', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Brantley' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Brooks', 'GA', 'Brooks', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Brooks' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bryan', 'GA', 'Bryan', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bryan' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bulloch', 'GA', 'Bulloch', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bulloch' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Burke', 'GA', 'Burke', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Burke' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Butts', 'GA', 'Butts', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Butts' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Calhoun', 'GA', 'Calhoun', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Calhoun' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Camden', 'GA', 'Camden', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Camden' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Candler', 'GA', 'Candler', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Candler' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Carroll', 'GA', 'Carroll', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Carroll' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Catoosa', 'GA', 'Catoosa', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Catoosa' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Charlton', 'GA', 'Charlton', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Charlton' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Chatham', 'GA', 'Chatham', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Chatham' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Chattahoochee', 'GA', 'Chattahoochee', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Chattahoochee' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Chattooga', 'GA', 'Chattooga', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Chattooga' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Cherokee', 'GA', 'Cherokee', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Cherokee' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Clarke', 'GA', 'Clarke', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Clarke' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Clay', 'GA', 'Clay', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Clay' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Clayton (Not Clg Prk)', 'GA', 'Clayton', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026. City-dependent: College Park portion 9%, elsewhere 8%', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Clayton (Not Clg Prk)' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Clinch', 'GA', 'Clinch', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Clinch' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Cobb', 'GA', 'Cobb', 0, 0.06000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Cobb' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Coffee', 'GA', 'Coffee', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Coffee' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Colquitt', 'GA', 'Colquitt', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Colquitt' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Columbia', 'GA', 'Columbia', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Columbia' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Cook', 'GA', 'Cook', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Cook' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Coweta', 'GA', 'Coweta', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Coweta' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Crawford', 'GA', 'Crawford', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Crawford' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Crisp', 'GA', 'Crisp', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Crisp' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dade', 'GA', 'Dade', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dade' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dawson', 'GA', 'Dawson', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dawson' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Decatur', 'GA', 'Decatur', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Decatur' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dekalb (Not Atlanta)', 'GA', 'Dekalb', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026. City-dependent: Atlanta portion 8.9%, elsewhere 8%', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dekalb (Not Atlanta)' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dodge', 'GA', 'Dodge', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dodge' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dooly', 'GA', 'Dooly', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dooly' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dougherty', 'GA', 'Dougherty', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dougherty' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Douglas', 'GA', 'Douglas', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Douglas' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Early', 'GA', 'Early', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Early' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Echols', 'GA', 'Echols', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Echols' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Effingham', 'GA', 'Effingham', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Effingham' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Elbert', 'GA', 'Elbert', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Elbert' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Emanuel', 'GA', 'Emanuel', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Emanuel' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Evans', 'GA', 'Evans', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Evans' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Fannin', 'GA', 'Fannin', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Fannin' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Fayette', 'GA', 'Fayette', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Fayette' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Floyd', 'GA', 'Floyd', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Floyd' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Forsyth', 'GA', 'Forsyth', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Forsyth' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Franklin', 'GA', 'Franklin', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Franklin' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Fulton', 'GA', 'Fulton', 0, 0.07750, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026. City-dependent: Atlanta 8.9%, Hapeville / College Park / East Point 8.75%, elsewhere 7.75%', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Fulton' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Gilmer', 'GA', 'Gilmer', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Gilmer' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Glascock', 'GA', 'Glascock', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Glascock' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Glynn', 'GA', 'Glynn', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Glynn' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Gordon', 'GA', 'Gordon', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Gordon' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Grady', 'GA', 'Grady', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Grady' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Greene', 'GA', 'Greene', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Greene' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Gwinnett', 'GA', 'Gwinnett', 0, 0.06000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Gwinnett' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Habersham', 'GA', 'Habersham', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Habersham' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Hall', 'GA', 'Hall', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Hall' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Hancock', 'GA', 'Hancock', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Hancock' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Haralson', 'GA', 'Haralson', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Haralson' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Harris', 'GA', 'Harris', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Harris' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Hart', 'GA', 'Hart', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Hart' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Heard', 'GA', 'Heard', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Heard' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Henry', 'GA', 'Henry', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Henry' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Houston', 'GA', 'Houston', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Houston' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Irwin', 'GA', 'Irwin', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Irwin' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jackson', 'GA', 'Jackson', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jackson' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jasper', 'GA', 'Jasper', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jasper' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jeff Davis', 'GA', 'Jeff Davis', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jeff Davis' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jefferson', 'GA', 'Jefferson', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jefferson' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jenkins', 'GA', 'Jenkins', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jenkins' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Johnson', 'GA', 'Johnson', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Johnson' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jones', 'GA', 'Jones', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jones' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lamar', 'GA', 'Lamar', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lamar' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lanier', 'GA', 'Lanier', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lanier' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Laurens', 'GA', 'Laurens', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Laurens' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lee', 'GA', 'Lee', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lee' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Liberty', 'GA', 'Liberty', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Liberty' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lincoln', 'GA', 'Lincoln', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lincoln' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Long', 'GA', 'Long', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Long' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lowndes', 'GA', 'Lowndes', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lowndes' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lumpkin', 'GA', 'Lumpkin', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lumpkin' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Macon', 'GA', 'Macon', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Macon' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Madison', 'GA', 'Madison', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Madison' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Marion', 'GA', 'Marion', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Marion' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - McDuffie', 'GA', 'McDuffie', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - McDuffie' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - McIntosh', 'GA', 'McIntosh', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - McIntosh' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Meriwether', 'GA', 'Meriwether', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Meriwether' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Miller', 'GA', 'Miller', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Miller' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Mitchell', 'GA', 'Mitchell', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Mitchell' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Monroe', 'GA', 'Monroe', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Monroe' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Montgomery', 'GA', 'Montgomery', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Montgomery' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Morgan', 'GA', 'Morgan', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Morgan' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Murray', 'GA', 'Murray', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Murray' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Muscogee', 'GA', 'Muscogee', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Muscogee' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Newton', 'GA', 'Newton', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Newton' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Oconee', 'GA', 'Oconee', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Oconee' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Oglethorpe', 'GA', 'Oglethorpe', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Oglethorpe' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Paulding', 'GA', 'Paulding', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Paulding' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Peach', 'GA', 'Peach', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Peach' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Pickens', 'GA', 'Pickens', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Pickens' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Pierce', 'GA', 'Pierce', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Pierce' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Pike', 'GA', 'Pike', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Pike' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Polk', 'GA', 'Polk', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Polk' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Pulaski', 'GA', 'Pulaski', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Pulaski' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Putnam', 'GA', 'Putnam', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Putnam' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Quitman', 'GA', 'Quitman', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Quitman' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Rabun', 'GA', 'Rabun', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Rabun' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Randolph', 'GA', 'Randolph', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Randolph' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Richmond', 'GA', 'Richmond', 0, 0.08500, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Richmond' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Rockdale', 'GA', 'Rockdale', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Rockdale' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Schley', 'GA', 'Schley', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Schley' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Screven', 'GA', 'Screven', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Screven' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Seminole', 'GA', 'Seminole', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Seminole' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Spalding', 'GA', 'Spalding', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Spalding' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Stephens', 'GA', 'Stephens', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Stephens' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Stewart', 'GA', 'Stewart', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Stewart' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Sumter', 'GA', 'Sumter', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Sumter' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Talbot', 'GA', 'Talbot', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Talbot' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Taliaferro', 'GA', 'Taliaferro', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Taliaferro' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Tattnall', 'GA', 'Tattnall', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Tattnall' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Taylor', 'GA', 'Taylor', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Taylor' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Telfair', 'GA', 'Telfair', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Telfair' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Terrell', 'GA', 'Terrell', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Terrell' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Thomas', 'GA', 'Thomas', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Thomas' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Tift', 'GA', 'Tift', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Tift' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Toombs', 'GA', 'Toombs', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Toombs' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Towns', 'GA', 'Towns', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Towns' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Treutlen', 'GA', 'Treutlen', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Treutlen' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Troup', 'GA', 'Troup', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Troup' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Turner', 'GA', 'Turner', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Turner' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Twiggs', 'GA', 'Twiggs', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Twiggs' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Union', 'GA', 'Union', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Union' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Upson', 'GA', 'Upson', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Upson' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Walker', 'GA', 'Walker', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Walker' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Walton', 'GA', 'Walton', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Walton' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Ware', 'GA', 'Ware', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Ware' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Warren', 'GA', 'Warren', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Warren' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Washington', 'GA', 'Washington', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Washington' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wayne', 'GA', 'Wayne', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wayne' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Webster', 'GA', 'Webster', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Webster' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wheeler', 'GA', 'Wheeler', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wheeler' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - White', 'GA', 'White', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - White' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Whitfield', 'GA', 'Whitfield', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Whitfield' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wilcox', 'GA', 'Wilcox', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wilcox' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wilkes', 'GA', 'Wilkes', 0, 0.09000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wilkes' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wilkinson', 'GA', 'Wilkinson', 0, 0.07000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wilkinson' AND t.effective_from='2026-07-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Worth', 'GA', 'Worth', 0, 0.08000, '2026-07-01', '2026-09-30', 0, 'GA DOR general rate chart, effective 1 July 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Worth' AND t.effective_from='2026-07-01');

-- From 1 October 2026.
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Appling', 'GA', 'Appling', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Appling' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Atkinson', 'GA', 'Atkinson', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Atkinson' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bacon', 'GA', 'Bacon', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bacon' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Baker', 'GA', 'Baker', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Baker' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Baldwin', 'GA', 'Baldwin', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Baldwin' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Banks', 'GA', 'Banks', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Banks' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Barrow', 'GA', 'Barrow', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Barrow' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bartow', 'GA', 'Bartow', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bartow' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Ben Hill', 'GA', 'Ben Hill', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Ben Hill' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Berrien', 'GA', 'Berrien', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Berrien' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bibb', 'GA', 'Bibb', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bibb' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bleckley', 'GA', 'Bleckley', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bleckley' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Brantley', 'GA', 'Brantley', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Brantley' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Brooks', 'GA', 'Brooks', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Brooks' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bryan', 'GA', 'Bryan', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bryan' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Bulloch', 'GA', 'Bulloch', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Bulloch' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Burke', 'GA', 'Burke', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Burke' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Butts', 'GA', 'Butts', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Butts' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Calhoun', 'GA', 'Calhoun', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Calhoun' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Camden', 'GA', 'Camden', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Camden' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Candler', 'GA', 'Candler', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Candler' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Carroll', 'GA', 'Carroll', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Carroll' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Catoosa', 'GA', 'Catoosa', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Catoosa' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Charlton', 'GA', 'Charlton', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Charlton' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Chatham', 'GA', 'Chatham', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Chatham' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Chattahoochee', 'GA', 'Chattahoochee', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Chattahoochee' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Chattooga', 'GA', 'Chattooga', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Chattooga' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Cherokee', 'GA', 'Cherokee', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Cherokee' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Clarke', 'GA', 'Clarke', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Clarke' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Clay', 'GA', 'Clay', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Clay' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Clayton (Not Clg Prk)', 'GA', 'Clayton', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026. City-dependent: College Park portion 9%, elsewhere 8%', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Clayton (Not Clg Prk)' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Clinch', 'GA', 'Clinch', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Clinch' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Cobb', 'GA', 'Cobb', 0, 0.06000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Cobb' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Coffee', 'GA', 'Coffee', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Coffee' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Colquitt', 'GA', 'Colquitt', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Colquitt' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Columbia', 'GA', 'Columbia', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Columbia' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Cook', 'GA', 'Cook', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Cook' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Coweta', 'GA', 'Coweta', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Coweta' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Crawford', 'GA', 'Crawford', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Crawford' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Crisp', 'GA', 'Crisp', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Crisp' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dade', 'GA', 'Dade', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dade' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dawson', 'GA', 'Dawson', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dawson' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Decatur', 'GA', 'Decatur', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Decatur' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dekalb (Not Atlanta)', 'GA', 'Dekalb', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026. City-dependent: Atlanta portion 8.9%, elsewhere 8%', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dekalb (Not Atlanta)' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dodge', 'GA', 'Dodge', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dodge' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dooly', 'GA', 'Dooly', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dooly' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Dougherty', 'GA', 'Dougherty', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Dougherty' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Douglas', 'GA', 'Douglas', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Douglas' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Early', 'GA', 'Early', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Early' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Echols', 'GA', 'Echols', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Echols' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Effingham', 'GA', 'Effingham', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Effingham' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Elbert', 'GA', 'Elbert', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Elbert' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Emanuel', 'GA', 'Emanuel', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Emanuel' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Evans', 'GA', 'Evans', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Evans' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Fannin', 'GA', 'Fannin', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Fannin' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Fayette', 'GA', 'Fayette', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Fayette' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Floyd', 'GA', 'Floyd', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Floyd' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Forsyth', 'GA', 'Forsyth', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Forsyth' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Franklin', 'GA', 'Franklin', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Franklin' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Fulton', 'GA', 'Fulton', 0, 0.07750, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026. City-dependent: Atlanta 8.9%, Hapeville / College Park / East Point 8.75%, elsewhere 7.75%', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Fulton' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Gilmer', 'GA', 'Gilmer', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Gilmer' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Glascock', 'GA', 'Glascock', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Glascock' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Glynn', 'GA', 'Glynn', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Glynn' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Gordon', 'GA', 'Gordon', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Gordon' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Grady', 'GA', 'Grady', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Grady' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Greene', 'GA', 'Greene', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Greene' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Gwinnett', 'GA', 'Gwinnett', 0, 0.06000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Gwinnett' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Habersham', 'GA', 'Habersham', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Habersham' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Hall', 'GA', 'Hall', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Hall' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Hancock', 'GA', 'Hancock', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Hancock' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Haralson', 'GA', 'Haralson', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Haralson' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Harris', 'GA', 'Harris', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Harris' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Hart', 'GA', 'Hart', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Hart' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Heard', 'GA', 'Heard', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Heard' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Henry', 'GA', 'Henry', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Henry' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Houston', 'GA', 'Houston', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Houston' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Irwin', 'GA', 'Irwin', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Irwin' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jackson', 'GA', 'Jackson', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jackson' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jasper', 'GA', 'Jasper', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jasper' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jeff Davis', 'GA', 'Jeff Davis', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jeff Davis' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jefferson', 'GA', 'Jefferson', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jefferson' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jenkins', 'GA', 'Jenkins', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jenkins' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Johnson', 'GA', 'Johnson', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Johnson' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Jones', 'GA', 'Jones', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Jones' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lamar', 'GA', 'Lamar', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lamar' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lanier', 'GA', 'Lanier', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lanier' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Laurens', 'GA', 'Laurens', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Laurens' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lee', 'GA', 'Lee', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lee' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Liberty', 'GA', 'Liberty', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Liberty' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lincoln', 'GA', 'Lincoln', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lincoln' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Long', 'GA', 'Long', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Long' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lowndes', 'GA', 'Lowndes', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lowndes' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Lumpkin', 'GA', 'Lumpkin', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Lumpkin' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Macon', 'GA', 'Macon', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Macon' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Madison', 'GA', 'Madison', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Madison' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Marion', 'GA', 'Marion', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Marion' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - McDuffie', 'GA', 'McDuffie', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - McDuffie' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - McIntosh', 'GA', 'McIntosh', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - McIntosh' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Meriwether', 'GA', 'Meriwether', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Meriwether' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Miller', 'GA', 'Miller', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Miller' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Mitchell', 'GA', 'Mitchell', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Mitchell' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Monroe', 'GA', 'Monroe', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Monroe' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Montgomery', 'GA', 'Montgomery', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Montgomery' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Morgan', 'GA', 'Morgan', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Morgan' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Murray', 'GA', 'Murray', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Murray' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Muscogee', 'GA', 'Muscogee', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Muscogee' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Newton', 'GA', 'Newton', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Newton' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Oconee', 'GA', 'Oconee', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Oconee' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Oglethorpe', 'GA', 'Oglethorpe', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Oglethorpe' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Paulding', 'GA', 'Paulding', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Paulding' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Peach', 'GA', 'Peach', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Peach' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Pickens', 'GA', 'Pickens', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Pickens' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Pierce', 'GA', 'Pierce', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Pierce' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Pike', 'GA', 'Pike', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Pike' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Polk', 'GA', 'Polk', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Polk' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Pulaski', 'GA', 'Pulaski', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Pulaski' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Putnam', 'GA', 'Putnam', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Putnam' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Quitman', 'GA', 'Quitman', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Quitman' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Rabun', 'GA', 'Rabun', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Rabun' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Randolph', 'GA', 'Randolph', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Randolph' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Richmond', 'GA', 'Richmond', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Richmond' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Rockdale', 'GA', 'Rockdale', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Rockdale' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Schley', 'GA', 'Schley', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Schley' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Screven', 'GA', 'Screven', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Screven' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Seminole', 'GA', 'Seminole', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Seminole' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Spalding', 'GA', 'Spalding', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Spalding' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Stephens', 'GA', 'Stephens', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Stephens' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Stewart', 'GA', 'Stewart', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Stewart' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Sumter', 'GA', 'Sumter', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Sumter' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Talbot', 'GA', 'Talbot', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Talbot' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Taliaferro', 'GA', 'Taliaferro', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Taliaferro' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Tattnall', 'GA', 'Tattnall', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Tattnall' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Taylor', 'GA', 'Taylor', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Taylor' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Telfair', 'GA', 'Telfair', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Telfair' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Terrell', 'GA', 'Terrell', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Terrell' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Thomas', 'GA', 'Thomas', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Thomas' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Tift', 'GA', 'Tift', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Tift' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Toombs', 'GA', 'Toombs', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Toombs' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Towns', 'GA', 'Towns', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Towns' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Treutlen', 'GA', 'Treutlen', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Treutlen' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Troup', 'GA', 'Troup', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Troup' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Turner', 'GA', 'Turner', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Turner' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Twiggs', 'GA', 'Twiggs', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Twiggs' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Union', 'GA', 'Union', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Union' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Upson', 'GA', 'Upson', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Upson' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Walker', 'GA', 'Walker', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Walker' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Walton', 'GA', 'Walton', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Walton' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Ware', 'GA', 'Ware', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Ware' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Warren', 'GA', 'Warren', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Warren' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Washington', 'GA', 'Washington', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Washington' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wayne', 'GA', 'Wayne', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wayne' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Webster', 'GA', 'Webster', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Webster' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wheeler', 'GA', 'Wheeler', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wheeler' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - White', 'GA', 'White', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - White' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Whitfield', 'GA', 'Whitfield', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Whitfield' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wilcox', 'GA', 'Wilcox', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wilcox' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wilkes', 'GA', 'Wilkes', 0, 0.09000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wilkes' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Wilkinson', 'GA', 'Wilkinson', 0, 0.07000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Wilkinson' AND t.effective_from='2026-10-01');
INSERT INTO tax_rates (name, state_code, county, is_state_default, rate, effective_from, effective_to, needs_review, source_note, is_active)
SELECT 'GA - Worth', 'GA', 'Worth', 0, 0.08000, '2026-10-01', NULL, 0, 'GA DOR general rate chart, effective 1 October 2026', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tax_rates t WHERE t.state_code='GA' AND t.name='GA - Worth' AND t.effective_from='2026-10-01');

-- ZIP to jurisdiction, pointing at the current rows.
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30002', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30004', 'GA', id, 1, 'Spans Fulton, Forsyth, Cherokee. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30005', 'GA', id, 1, 'Spans Fulton, Forsyth. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30008', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30009', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30011', 'GA', id, 1, 'Spans Barrow, Gwinnett' FROM tax_rates
WHERE state_code='GA' AND county='Barrow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30012', 'GA', id, 1, 'Spans Rockdale, Walton, Dekalb. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Rockdale' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30013', 'GA', id, 0, 'Spans Rockdale, Newton' FROM tax_rates
WHERE state_code='GA' AND county='Rockdale' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30014', 'GA', id, 1, 'Spans Newton, Walton, Jasper' FROM tax_rates
WHERE state_code='GA' AND county='Newton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30016', 'GA', id, 0, 'Spans Newton, Rockdale' FROM tax_rates
WHERE state_code='GA' AND county='Newton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30017', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30018', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Walton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30019', 'GA', id, 1, 'Spans Gwinnett, Walton' FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30021', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30022', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30024', 'GA', id, 1, 'Spans Gwinnett, Forsyth, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30025', 'GA', id, 1, 'Spans Walton, Newton, Morgan' FROM tax_rates
WHERE state_code='GA' AND county='Walton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30028', 'GA', id, 0, 'Spans Forsyth, Cherokee' FROM tax_rates
WHERE state_code='GA' AND county='Forsyth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30030', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30032', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30033', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30034', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30035', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30038', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30039', 'GA', id, 1, 'Spans Gwinnett, Dekalb. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30040', 'GA', id, 0, 'Spans Forsyth, Cherokee' FROM tax_rates
WHERE state_code='GA' AND county='Forsyth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30041', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Forsyth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30043', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30044', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30045', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30046', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30047', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30052', 'GA', id, 1, 'Spans Walton, Gwinnett, Rockdale, Newton' FROM tax_rates
WHERE state_code='GA' AND county='Walton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30054', 'GA', id, 0, 'Spans Newton, Walton' FROM tax_rates
WHERE state_code='GA' AND county='Newton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30055', 'GA', id, 1, 'Spans Newton, Jasper, Morgan' FROM tax_rates
WHERE state_code='GA' AND county='Newton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30056', 'GA', id, 1, 'Spans Jasper, Morgan, Newton' FROM tax_rates
WHERE state_code='GA' AND county='Jasper' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30058', 'GA', id, 1, 'Spans Dekalb, Rockdale. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30060', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30062', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30064', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30066', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30067', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30068', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30070', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Newton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30071', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30072', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30075', 'GA', id, 1, 'Spans Fulton, Cobb, Cherokee. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30076', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30078', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30079', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30080', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30082', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30083', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30084', 'GA', id, 1, 'Spans Dekalb, Gwinnett. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30087', 'GA', id, 1, 'Spans Dekalb, Gwinnett. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30088', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30090', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30092', 'GA', id, 1, 'Spans Gwinnett, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30093', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30094', 'GA', id, 1, 'Spans Rockdale, Dekalb. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Rockdale' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30096', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30097', 'GA', id, 1, 'Spans Fulton, Gwinnett, Forsyth. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30101', 'GA', id, 1, 'Spans Cobb, Paulding, Bartow, Cherokee' FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30102', 'GA', id, 1, 'Spans Cherokee, Bartow, Cobb' FROM tax_rates
WHERE state_code='GA' AND county='Cherokee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30103', 'GA', id, 1, 'Spans Bartow, Gordon, Floyd' FROM tax_rates
WHERE state_code='GA' AND county='Bartow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30104', 'GA', id, 0, 'Spans Polk, Floyd, Bartow' FROM tax_rates
WHERE state_code='GA' AND county='Polk' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30105', 'GA', id, 1, 'Spans Floyd, Chattooga' FROM tax_rates
WHERE state_code='GA' AND county='Floyd' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30106', 'GA', id, 1, 'Spans Cobb, Douglas' FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30107', 'GA', id, 0, 'Spans Cherokee, Pickens, Forsyth' FROM tax_rates
WHERE state_code='GA' AND county='Cherokee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30108', 'GA', id, 1, 'Spans Carroll, Heard' FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30110', 'GA', id, 1, 'Spans Haralson, Carroll' FROM tax_rates
WHERE state_code='GA' AND county='Haralson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30111', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30113', 'GA', id, 1, 'Spans Haralson, Polk' FROM tax_rates
WHERE state_code='GA' AND county='Haralson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30114', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cherokee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30115', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cherokee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30116', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30117', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30118', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30120', 'GA', id, 0, 'Spans Bartow, Paulding' FROM tax_rates
WHERE state_code='GA' AND county='Bartow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30121', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bartow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30122', 'GA', id, 1, 'Spans Douglas, Cobb' FROM tax_rates
WHERE state_code='GA' AND county='Douglas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30124', 'GA', id, 0, 'Spans Floyd, Polk' FROM tax_rates
WHERE state_code='GA' AND county='Floyd' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30125', 'GA', id, 1, 'Spans Polk, Floyd, Haralson' FROM tax_rates
WHERE state_code='GA' AND county='Polk' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30126', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30127', 'GA', id, 1, 'Spans Cobb, Paulding' FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30132', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Paulding' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30134', 'GA', id, 0, 'Spans Douglas, Paulding' FROM tax_rates
WHERE state_code='GA' AND county='Douglas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30135', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Douglas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30137', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bartow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30139', 'GA', id, 1, 'Spans Gordon, Pickens, Bartow' FROM tax_rates
WHERE state_code='GA' AND county='Gordon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30141', 'GA', id, 1, 'Spans Paulding, Cobb' FROM tax_rates
WHERE state_code='GA' AND county='Paulding' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30143', 'GA', id, 1, 'Spans Pickens, Dawson, Cherokee' FROM tax_rates
WHERE state_code='GA' AND county='Pickens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30144', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30145', 'GA', id, 0, 'Spans Bartow, Floyd' FROM tax_rates
WHERE state_code='GA' AND county='Bartow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30147', 'GA', id, 0, 'Spans Floyd, Polk' FROM tax_rates
WHERE state_code='GA' AND county='Floyd' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30148', 'GA', id, 1, 'Spans Dawson, Pickens' FROM tax_rates
WHERE state_code='GA' AND county='Dawson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30149', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Floyd' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30152', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30153', 'GA', id, 1, 'Spans Polk, Paulding, Haralson' FROM tax_rates
WHERE state_code='GA' AND county='Polk' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30157', 'GA', id, 1, 'Spans Paulding, Cobb' FROM tax_rates
WHERE state_code='GA' AND county='Paulding' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30161', 'GA', id, 0, 'Spans Floyd, Bartow' FROM tax_rates
WHERE state_code='GA' AND county='Floyd' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30165', 'GA', id, 1, 'Spans Floyd, Chattooga' FROM tax_rates
WHERE state_code='GA' AND county='Floyd' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30168', 'GA', id, 1, 'Spans Cobb, Douglas' FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30170', 'GA', id, 1, 'Spans Carroll, Heard' FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30171', 'GA', id, 1, 'Spans Bartow, Gordon' FROM tax_rates
WHERE state_code='GA' AND county='Bartow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30173', 'GA', id, 0, 'Spans Floyd, Polk' FROM tax_rates
WHERE state_code='GA' AND county='Floyd' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30175', 'GA', id, 0, 'Spans Pickens, Gilmer' FROM tax_rates
WHERE state_code='GA' AND county='Pickens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30176', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Haralson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30177', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Pickens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30178', 'GA', id, 0, 'Spans Bartow, Polk, Paulding' FROM tax_rates
WHERE state_code='GA' AND county='Bartow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30179', 'GA', id, 1, 'Spans Carroll, Paulding, Haralson' FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30180', 'GA', id, 0, 'Spans Carroll, Douglas, Paulding' FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30182', 'GA', id, 1, 'Spans Carroll, Haralson' FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30183', 'GA', id, 0, 'Spans Cherokee, Pickens, Bartow' FROM tax_rates
WHERE state_code='GA' AND county='Cherokee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30184', 'GA', id, 0, 'Spans Bartow, Cherokee' FROM tax_rates
WHERE state_code='GA' AND county='Bartow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30185', 'GA', id, 0, 'Spans Carroll, Douglas' FROM tax_rates
WHERE state_code='GA' AND county='Carroll' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30187', 'GA', id, 0, 'Spans Douglas, Carroll' FROM tax_rates
WHERE state_code='GA' AND county='Douglas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30188', 'GA', id, 1, 'Spans Cherokee, Cobb' FROM tax_rates
WHERE state_code='GA' AND county='Cherokee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30189', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cherokee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30204', 'GA', id, 0, 'Spans Lamar, Upson, Monroe' FROM tax_rates
WHERE state_code='GA' AND county='Lamar' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30205', 'GA', id, 0, 'Spans Fayette, Spalding' FROM tax_rates
WHERE state_code='GA' AND county='Fayette' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30206', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Pike' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30213', 'GA', id, 1, 'Spans Fulton, Fayette. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30214', 'GA', id, 1, 'Spans Fayette, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fayette' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30215', 'GA', id, 1, 'Spans Fayette, Clayton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fayette' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30216', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Butts' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30217', 'GA', id, 1, 'Spans Heard, Troup' FROM tax_rates
WHERE state_code='GA' AND county='Heard' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30218', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Meriwether' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30220', 'GA', id, 0, 'Spans Coweta, Meriwether' FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30222', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Meriwether' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30223', 'GA', id, 1, 'Spans Spalding, Henry' FROM tax_rates
WHERE state_code='GA' AND county='Spalding' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30224', 'GA', id, 1, 'Spans Spalding, Pike, Lamar' FROM tax_rates
WHERE state_code='GA' AND county='Spalding' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30228', 'GA', id, 1, 'Spans Henry, Clayton, Spalding. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Henry' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30229', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30230', 'GA', id, 1, 'Spans Troup, Meriwether, Heard, Coweta' FROM tax_rates
WHERE state_code='GA' AND county='Troup' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30233', 'GA', id, 1, 'Spans Butts, Monroe, Henry, Lamar, Newton' FROM tax_rates
WHERE state_code='GA' AND county='Butts' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30234', 'GA', id, 0, 'Spans Butts, Henry' FROM tax_rates
WHERE state_code='GA' AND county='Butts' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30236', 'GA', id, 1, 'Spans Clayton, Henry. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Clayton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30238', 'GA', id, 1, 'Spans Clayton, Fayette. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Clayton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30240', 'GA', id, 1, 'Spans Troup, Heard' FROM tax_rates
WHERE state_code='GA' AND county='Troup' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30241', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Troup' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30248', 'GA', id, 1, 'Spans Henry, Butts, Spalding' FROM tax_rates
WHERE state_code='GA' AND county='Henry' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30250', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Clayton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30251', 'GA', id, 0, 'Spans Meriwether, Coweta' FROM tax_rates
WHERE state_code='GA' AND county='Meriwether' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30252', 'GA', id, 1, 'Spans Henry, Rockdale' FROM tax_rates
WHERE state_code='GA' AND county='Henry' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30253', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Henry' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30256', 'GA', id, 1, 'Spans Pike, Upson, Lamar' FROM tax_rates
WHERE state_code='GA' AND county='Pike' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30257', 'GA', id, 1, 'Spans Lamar, Pike' FROM tax_rates
WHERE state_code='GA' AND county='Lamar' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30258', 'GA', id, 1, 'Spans Pike, Upson' FROM tax_rates
WHERE state_code='GA' AND county='Pike' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30259', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30260', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Clayton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30263', 'GA', id, 0, 'Spans Coweta, Heard' FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30265', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30268', 'GA', id, 1, 'Spans Fulton, Coweta. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30269', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Fayette' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30272', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30273', 'GA', id, 1, 'Spans Clayton, Henry. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Clayton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30274', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Clayton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30275', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30276', 'GA', id, 1, 'Spans Coweta, Meriwether, Fayette' FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30277', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30281', 'GA', id, 1, 'Spans Henry, Rockdale, Clayton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Henry' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30284', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Spalding' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30285', 'GA', id, 1, 'Spans Upson, Lamar, Pike' FROM tax_rates
WHERE state_code='GA' AND county='Upson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30286', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Upson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30288', 'GA', id, 1, 'Spans Dekalb, Clayton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30289', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coweta' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30290', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Fayette' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30291', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30292', 'GA', id, 0, 'Spans Pike, Spalding' FROM tax_rates
WHERE state_code='GA' AND county='Pike' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30293', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Meriwether' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30294', 'GA', id, 1, 'Spans Dekalb, Clayton, Henry, Rockdale. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30295', 'GA', id, 1, 'Spans Pike, Lamar' FROM tax_rates
WHERE state_code='GA' AND county='Pike' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30296', 'GA', id, 1, 'Spans Clayton, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Clayton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30297', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Clayton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30303', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30305', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30306', 'GA', id, 1, 'Spans Fulton, Dekalb. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30307', 'GA', id, 1, 'Spans Dekalb, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30308', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30309', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30310', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30311', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30312', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30313', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30314', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30315', 'GA', id, 1, 'Spans Fulton, Dekalb. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30316', 'GA', id, 1, 'Spans Dekalb, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30317', 'GA', id, 1, 'Spans Dekalb, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30318', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30319', 'GA', id, 1, 'Spans Dekalb, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30322', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30324', 'GA', id, 1, 'Spans Fulton, Dekalb. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30326', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30327', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30328', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30329', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30331', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30332', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30334', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30336', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30337', 'GA', id, 1, 'Spans Fulton, Clayton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30338', 'GA', id, 1, 'Spans Dekalb, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30339', 'GA', id, 1, 'Spans Cobb, Fulton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Cobb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30340', 'GA', id, 1, 'Spans Dekalb, Gwinnett. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30341', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30342', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30344', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30345', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30346', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30349', 'GA', id, 1, 'Spans Fulton, Clayton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30350', 'GA', id, 1, 'Spans Fulton, Dekalb. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30354', 'GA', id, 1, 'Spans Fulton, Clayton. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30360', 'GA', id, 1, 'Spans Dekalb, Gwinnett. rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Dekalb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30363', 'GA', id, 1, 'rate depends on the city within the county' FROM tax_rates
WHERE state_code='GA' AND county='Fulton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30401', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Emanuel' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30410', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Montgomery' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30411', 'GA', id, 0, 'Spans Wheeler, Laurens' FROM tax_rates
WHERE state_code='GA' AND county='Wheeler' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30412', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Montgomery' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30413', 'GA', id, 1, 'Spans Jefferson, Washington, Johnson' FROM tax_rates
WHERE state_code='GA' AND county='Jefferson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30414', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Evans' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30415', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bulloch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30417', 'GA', id, 0, 'Spans Evans, Tattnall' FROM tax_rates
WHERE state_code='GA' AND county='Evans' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30420', 'GA', id, 0, 'Spans Candler, Tattnall, Evans' FROM tax_rates
WHERE state_code='GA' AND county='Candler' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30421', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Tattnall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30423', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Evans' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30425', 'GA', id, 0, 'Spans Emanuel, Jenkins, Bulloch' FROM tax_rates
WHERE state_code='GA' AND county='Emanuel' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30426', 'GA', id, 1, 'Spans Burke, Screven' FROM tax_rates
WHERE state_code='GA' AND county='Burke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30427', 'GA', id, 0, 'Spans Tattnall, Long, Evans' FROM tax_rates
WHERE state_code='GA' AND county='Tattnall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30428', 'GA', id, 0, 'Spans Wheeler, Laurens' FROM tax_rates
WHERE state_code='GA' AND county='Wheeler' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30429', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Evans' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30434', 'GA', id, 1, 'Spans Jefferson, Burke' FROM tax_rates
WHERE state_code='GA' AND county='Jefferson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30436', 'GA', id, 1, 'Spans Toombs, Emanuel, Tattnall, Treutlen' FROM tax_rates
WHERE state_code='GA' AND county='Toombs' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30439', 'GA', id, 0, 'Spans Candler, Emanuel, Bulloch' FROM tax_rates
WHERE state_code='GA' AND county='Candler' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30441', 'GA', id, 1, 'Spans Emanuel, Burke, Jenkins' FROM tax_rates
WHERE state_code='GA' AND county='Emanuel' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30442', 'GA', id, 1, 'Spans Jenkins, Burke, Screven' FROM tax_rates
WHERE state_code='GA' AND county='Jenkins' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30445', 'GA', id, 0, 'Spans Montgomery, Treutlen' FROM tax_rates
WHERE state_code='GA' AND county='Montgomery' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30446', 'GA', id, 0, 'Spans Screven, Effingham' FROM tax_rates
WHERE state_code='GA' AND county='Screven' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30448', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Emanuel' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30449', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Screven' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30450', 'GA', id, 0, 'Spans Bulloch, Emanuel' FROM tax_rates
WHERE state_code='GA' AND county='Bulloch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30451', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Candler' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30452', 'GA', id, 0, 'Spans Bulloch, Evans, Candler' FROM tax_rates
WHERE state_code='GA' AND county='Bulloch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30453', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Tattnall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30454', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Laurens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30455', 'GA', id, 1, 'Spans Screven, Jenkins' FROM tax_rates
WHERE state_code='GA' AND county='Screven' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30456', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Burke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30457', 'GA', id, 1, 'Spans Treutlen, Montgomery, Emanuel' FROM tax_rates
WHERE state_code='GA' AND county='Treutlen' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30458', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bulloch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30460', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bulloch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30461', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bulloch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30464', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Emanuel' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30467', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Screven' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30470', 'GA', id, 0, 'Spans Montgomery, Treutlen' FROM tax_rates
WHERE state_code='GA' AND county='Montgomery' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30471', 'GA', id, 0, 'Spans Emanuel, Bulloch, Candler' FROM tax_rates
WHERE state_code='GA' AND county='Emanuel' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30473', 'GA', id, 0, 'Spans Toombs, Montgomery' FROM tax_rates
WHERE state_code='GA' AND county='Toombs' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30474', 'GA', id, 1, 'Spans Toombs, Montgomery, Emanuel, Treutlen' FROM tax_rates
WHERE state_code='GA' AND county='Toombs' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30477', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Jefferson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30501', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30504', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30506', 'GA', id, 0, 'Spans Hall, Forsyth' FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30507', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30510', 'GA', id, 1, 'Spans Banks, Habersham, Hall' FROM tax_rates
WHERE state_code='GA' AND county='Banks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30511', 'GA', id, 1, 'Spans Banks, Stephens, Habersham' FROM tax_rates
WHERE state_code='GA' AND county='Banks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30512', 'GA', id, 0, 'Spans Union, Fannin' FROM tax_rates
WHERE state_code='GA' AND county='Union' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30513', 'GA', id, 0, 'Spans Fannin, Gilmer' FROM tax_rates
WHERE state_code='GA' AND county='Fannin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30516', 'GA', id, 0, 'Spans Hart, Franklin' FROM tax_rates
WHERE state_code='GA' AND county='Hart' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30517', 'GA', id, 1, 'Spans Jackson, Hall, Gwinnett, Barrow' FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30518', 'GA', id, 1, 'Spans Gwinnett, Hall' FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30519', 'GA', id, 1, 'Spans Gwinnett, Hall' FROM tax_rates
WHERE state_code='GA' AND county='Gwinnett' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30520', 'GA', id, 0, 'Spans Hart, Franklin' FROM tax_rates
WHERE state_code='GA' AND county='Hart' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30521', 'GA', id, 1, 'Spans Franklin, Banks' FROM tax_rates
WHERE state_code='GA' AND county='Franklin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30522', 'GA', id, 0, 'Spans Gilmer, Fannin' FROM tax_rates
WHERE state_code='GA' AND county='Gilmer' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30523', 'GA', id, 1, 'Spans Habersham, Rabun, White' FROM tax_rates
WHERE state_code='GA' AND county='Habersham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30525', 'GA', id, 1, 'Spans Rabun, Towns' FROM tax_rates
WHERE state_code='GA' AND county='Rabun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30527', 'GA', id, 1, 'Spans Hall, White' FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30528', 'GA', id, 0, 'Spans White, Lumpkin' FROM tax_rates
WHERE state_code='GA' AND county='White' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30529', 'GA', id, 1, 'Spans Jackson, Banks' FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30530', 'GA', id, 1, 'Spans Banks, Jackson, Madison, Franklin' FROM tax_rates
WHERE state_code='GA' AND county='Banks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30531', 'GA', id, 0, 'Spans Habersham, Hall' FROM tax_rates
WHERE state_code='GA' AND county='Habersham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30533', 'GA', id, 1, 'Spans Lumpkin, Dawson, Hall, White' FROM tax_rates
WHERE state_code='GA' AND county='Lumpkin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30534', 'GA', id, 1, 'Spans Dawson, Lumpkin, Forsyth, Pickens, Cherokee' FROM tax_rates
WHERE state_code='GA' AND county='Dawson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30535', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Habersham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30536', 'GA', id, 1, 'Spans Gilmer, Dawson' FROM tax_rates
WHERE state_code='GA' AND county='Gilmer' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30537', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Rabun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30538', 'GA', id, 0, 'Spans Stephens, Franklin' FROM tax_rates
WHERE state_code='GA' AND county='Stephens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30539', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gilmer' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30540', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Gilmer' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30541', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Fannin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30542', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30543', 'GA', id, 1, 'Spans Hall, Banks, Jackson' FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30545', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='White' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30546', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Towns' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30547', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Banks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30548', 'GA', id, 1, 'Spans Jackson, Barrow, Hall, Gwinnett' FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30549', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30552', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Rabun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30553', 'GA', id, 0, 'Spans Franklin, Hart' FROM tax_rates
WHERE state_code='GA' AND county='Franklin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30554', 'GA', id, 1, 'Spans Hall, Banks' FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30555', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Fannin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30557', 'GA', id, 0, 'Spans Franklin, Stephens' FROM tax_rates
WHERE state_code='GA' AND county='Franklin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30558', 'GA', id, 1, 'Spans Jackson, Banks' FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30559', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Fannin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30560', 'GA', id, 0, 'Spans Fannin, Union' FROM tax_rates
WHERE state_code='GA' AND county='Fannin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30562', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Rabun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30563', 'GA', id, 0, 'Spans Habersham, Stephens' FROM tax_rates
WHERE state_code='GA' AND county='Habersham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30564', 'GA', id, 1, 'Spans Hall, Lumpkin, White' FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30565', 'GA', id, 1, 'Spans Jackson, Madison' FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30566', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Hall' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30567', 'GA', id, 1, 'Spans Jackson, Hall' FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30568', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Rabun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30571', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='White' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30572', 'GA', id, 0, 'Spans Union, Fannin' FROM tax_rates
WHERE state_code='GA' AND county='Union' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30573', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Rabun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30575', 'GA', id, 1, 'Spans Jackson, Hall' FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30576', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Rabun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30577', 'GA', id, 1, 'Spans Stephens, Franklin, Habersham, Banks' FROM tax_rates
WHERE state_code='GA' AND county='Stephens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30581', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Rabun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30582', 'GA', id, 0, 'Spans Towns, Union' FROM tax_rates
WHERE state_code='GA' AND county='Towns' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30597', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Lumpkin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30598', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Stephens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30601', 'GA', id, 1, 'Spans Clarke, Madison, Jackson' FROM tax_rates
WHERE state_code='GA' AND county='Clarke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30602', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Clarke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30605', 'GA', id, 0, 'Spans Clarke, Oconee' FROM tax_rates
WHERE state_code='GA' AND county='Clarke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30606', 'GA', id, 0, 'Spans Clarke, Oconee' FROM tax_rates
WHERE state_code='GA' AND county='Clarke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30607', 'GA', id, 0, 'Spans Jackson, Clarke' FROM tax_rates
WHERE state_code='GA' AND county='Jackson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30609', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Clarke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30619', 'GA', id, 1, 'Spans Oglethorpe, Oconee' FROM tax_rates
WHERE state_code='GA' AND county='Oglethorpe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30620', 'GA', id, 1, 'Spans Barrow, Gwinnett, Walton' FROM tax_rates
WHERE state_code='GA' AND county='Barrow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30621', 'GA', id, 0, 'Spans Oconee, Morgan' FROM tax_rates
WHERE state_code='GA' AND county='Oconee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30622', 'GA', id, 0, 'Spans Oconee, Clarke, Jackson, Barrow' FROM tax_rates
WHERE state_code='GA' AND county='Oconee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30623', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Morgan' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30624', 'GA', id, 1, 'Spans Elbert, Hart, Madison' FROM tax_rates
WHERE state_code='GA' AND county='Elbert' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30625', 'GA', id, 0, 'Spans Morgan, Putnam' FROM tax_rates
WHERE state_code='GA' AND county='Morgan' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30627', 'GA', id, 1, 'Spans Oglethorpe, Madison' FROM tax_rates
WHERE state_code='GA' AND county='Oglethorpe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30628', 'GA', id, 1, 'Spans Madison, Oglethorpe' FROM tax_rates
WHERE state_code='GA' AND county='Madison' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30629', 'GA', id, 1, 'Spans Madison, Oglethorpe' FROM tax_rates
WHERE state_code='GA' AND county='Madison' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30630', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Oglethorpe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30631', 'GA', id, 1, 'Spans Taliaferro, Wilkes, Greene' FROM tax_rates
WHERE state_code='GA' AND county='Taliaferro' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30633', 'GA', id, 1, 'Spans Madison, Franklin, Banks' FROM tax_rates
WHERE state_code='GA' AND county='Madison' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30634', 'GA', id, 1, 'Spans Elbert, Hart' FROM tax_rates
WHERE state_code='GA' AND county='Elbert' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30635', 'GA', id, 1, 'Spans Elbert, Hart' FROM tax_rates
WHERE state_code='GA' AND county='Elbert' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30639', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Franklin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30641', 'GA', id, 1, 'Spans Walton, Morgan' FROM tax_rates
WHERE state_code='GA' AND county='Walton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30642', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Greene' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30643', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Hart' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30646', 'GA', id, 1, 'Spans Madison, Clarke, Jackson' FROM tax_rates
WHERE state_code='GA' AND county='Madison' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30648', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Oglethorpe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30650', 'GA', id, 1, 'Spans Morgan, Greene, Walton, Putnam' FROM tax_rates
WHERE state_code='GA' AND county='Morgan' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30655', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Walton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30656', 'GA', id, 1, 'Spans Walton, Barrow' FROM tax_rates
WHERE state_code='GA' AND county='Walton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30660', 'GA', id, 1, 'Spans Wilkes, Oglethorpe, Taliaferro' FROM tax_rates
WHERE state_code='GA' AND county='Wilkes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30662', 'GA', id, 1, 'Spans Franklin, Hart, Madison, Elbert' FROM tax_rates
WHERE state_code='GA' AND county='Franklin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30663', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Morgan' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30664', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Taliaferro' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30665', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Greene' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30666', 'GA', id, 0, 'Spans Barrow, Oconee, Jackson' FROM tax_rates
WHERE state_code='GA' AND county='Barrow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30667', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Oglethorpe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30668', 'GA', id, 0, 'Spans Wilkes, Lincoln' FROM tax_rates
WHERE state_code='GA' AND county='Wilkes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30669', 'GA', id, 1, 'Spans Greene, Oglethorpe, Taliaferro' FROM tax_rates
WHERE state_code='GA' AND county='Greene' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30673', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wilkes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30677', 'GA', id, 0, 'Spans Oconee, Greene' FROM tax_rates
WHERE state_code='GA' AND county='Oconee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30678', 'GA', id, 0, 'Spans Greene, Hancock, Taliaferro' FROM tax_rates
WHERE state_code='GA' AND county='Greene' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30680', 'GA', id, 0, 'Spans Barrow, Jackson, Oconee' FROM tax_rates
WHERE state_code='GA' AND county='Barrow' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30683', 'GA', id, 1, 'Spans Oglethorpe, Clarke, Madison' FROM tax_rates
WHERE state_code='GA' AND county='Oglethorpe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30701', 'GA', id, 1, 'Spans Gordon, Floyd' FROM tax_rates
WHERE state_code='GA' AND county='Gordon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30705', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Murray' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30707', 'GA', id, 1, 'Spans Walker, Catoosa' FROM tax_rates
WHERE state_code='GA' AND county='Walker' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30708', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Murray' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30710', 'GA', id, 0, 'Spans Whitfield, Catoosa' FROM tax_rates
WHERE state_code='GA' AND county='Whitfield' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30711', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Murray' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30720', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Whitfield' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30721', 'GA', id, 1, 'Spans Whitfield, Murray' FROM tax_rates
WHERE state_code='GA' AND county='Whitfield' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30724', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Murray' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30725', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Walker' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30726', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Catoosa' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30728', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Walker' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30730', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chattooga' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30731', 'GA', id, 1, 'Spans Chattooga, Walker, Dade' FROM tax_rates
WHERE state_code='GA' AND county='Chattooga' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30733', 'GA', id, 1, 'Spans Gordon, Floyd' FROM tax_rates
WHERE state_code='GA' AND county='Gordon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30734', 'GA', id, 1, 'Spans Gordon, Pickens' FROM tax_rates
WHERE state_code='GA' AND county='Gordon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30735', 'GA', id, 1, 'Spans Gordon, Murray, Whitfield' FROM tax_rates
WHERE state_code='GA' AND county='Gordon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30736', 'GA', id, 1, 'Spans Catoosa, Whitfield, Walker' FROM tax_rates
WHERE state_code='GA' AND county='Catoosa' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30738', 'GA', id, 1, 'Spans Dade, Walker' FROM tax_rates
WHERE state_code='GA' AND county='Dade' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30739', 'GA', id, 1, 'Spans Walker, Catoosa' FROM tax_rates
WHERE state_code='GA' AND county='Walker' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30740', 'GA', id, 1, 'Spans Whitfield, Walker' FROM tax_rates
WHERE state_code='GA' AND county='Whitfield' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30741', 'GA', id, 1, 'Spans Walker, Catoosa' FROM tax_rates
WHERE state_code='GA' AND county='Walker' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30742', 'GA', id, 1, 'Spans Catoosa, Walker' FROM tax_rates
WHERE state_code='GA' AND county='Catoosa' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30746', 'GA', id, 0, 'Spans Gordon, Walker' FROM tax_rates
WHERE state_code='GA' AND county='Gordon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30747', 'GA', id, 1, 'Spans Chattooga, Walker' FROM tax_rates
WHERE state_code='GA' AND county='Chattooga' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30750', 'GA', id, 1, 'Spans Walker, Dade' FROM tax_rates
WHERE state_code='GA' AND county='Walker' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30751', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Murray' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30752', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Dade' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30753', 'GA', id, 1, 'Spans Chattooga, Walker' FROM tax_rates
WHERE state_code='GA' AND county='Chattooga' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30755', 'GA', id, 0, 'Spans Whitfield, Catoosa' FROM tax_rates
WHERE state_code='GA' AND county='Whitfield' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30756', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Whitfield' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30757', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Dade' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30802', 'GA', id, 1, 'Spans Columbia, McDuffie' FROM tax_rates
WHERE state_code='GA' AND county='Columbia' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30803', 'GA', id, 1, 'Spans Jefferson, Glascock' FROM tax_rates
WHERE state_code='GA' AND county='Jefferson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30805', 'GA', id, 1, 'Spans Richmond, Burke' FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30807', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Warren' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30808', 'GA', id, 0, 'Spans McDuffie, Warren' FROM tax_rates
WHERE state_code='GA' AND county='McDuffie' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30809', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Columbia' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30810', 'GA', id, 1, 'Spans Glascock, Jefferson, Warren' FROM tax_rates
WHERE state_code='GA' AND county='Glascock' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30812', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30813', 'GA', id, 1, 'Spans Columbia, Richmond' FROM tax_rates
WHERE state_code='GA' AND county='Columbia' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30814', 'GA', id, 1, 'Spans Columbia, McDuffie' FROM tax_rates
WHERE state_code='GA' AND county='Columbia' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30815', 'GA', id, 1, 'Spans Richmond, Burke' FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30816', 'GA', id, 1, 'Spans Burke, Jefferson' FROM tax_rates
WHERE state_code='GA' AND county='Burke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30817', 'GA', id, 0, 'Spans Lincoln, Wilkes' FROM tax_rates
WHERE state_code='GA' AND county='Lincoln' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30818', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Jefferson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30820', 'GA', id, 1, 'Spans Glascock, Warren, Washington, Hancock' FROM tax_rates
WHERE state_code='GA' AND county='Glascock' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30821', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Warren' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30822', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Jenkins' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30823', 'GA', id, 1, 'Spans Jefferson, Warren, Glascock' FROM tax_rates
WHERE state_code='GA' AND county='Jefferson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30824', 'GA', id, 1, 'Spans McDuffie, Warren, Columbia' FROM tax_rates
WHERE state_code='GA' AND county='McDuffie' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30828', 'GA', id, 1, 'Spans Warren, Glascock' FROM tax_rates
WHERE state_code='GA' AND county='Warren' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30830', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Burke' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30833', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Jefferson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30901', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30904', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30905', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30906', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30907', 'GA', id, 1, 'Spans Columbia, Richmond' FROM tax_rates
WHERE state_code='GA' AND county='Columbia' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30909', 'GA', id, 1, 'Spans Richmond, Columbia' FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '30912', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Richmond' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31001', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wilcox' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31002', 'GA', id, 1, 'Spans Emanuel, Laurens, Johnson, Treutlen' FROM tax_rates
WHERE state_code='GA' AND county='Emanuel' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31003', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wilkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31004', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Monroe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31005', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Houston' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31006', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Taylor' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31007', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Dooly' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31008', 'GA', id, 1, 'Spans Peach, Crawford, Houston' FROM tax_rates
WHERE state_code='GA' AND county='Peach' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31009', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Laurens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31011', 'GA', id, 0, 'Spans Dodge, Laurens' FROM tax_rates
WHERE state_code='GA' AND county='Dodge' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31012', 'GA', id, 0, 'Spans Dodge, Bleckley, Laurens' FROM tax_rates
WHERE state_code='GA' AND county='Dodge' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31014', 'GA', id, 0, 'Spans Bleckley, Dodge, Twiggs, Pulaski' FROM tax_rates
WHERE state_code='GA' AND county='Bleckley' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31015', 'GA', id, 1, 'Spans Crisp, Wilcox, Dooly' FROM tax_rates
WHERE state_code='GA' AND county='Crisp' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31016', 'GA', id, 1, 'Spans Monroe, Upson, Crawford, Lamar' FROM tax_rates
WHERE state_code='GA' AND county='Monroe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31017', 'GA', id, 1, 'Spans Wilkinson, Twiggs, Bleckley' FROM tax_rates
WHERE state_code='GA' AND county='Wilkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31018', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Washington' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31019', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Laurens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31020', 'GA', id, 0, 'Spans Twiggs, Bibb' FROM tax_rates
WHERE state_code='GA' AND county='Twiggs' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31021', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Laurens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31022', 'GA', id, 0, 'Spans Laurens, Bleckley' FROM tax_rates
WHERE state_code='GA' AND county='Laurens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31023', 'GA', id, 0, 'Spans Dodge, Pulaski' FROM tax_rates
WHERE state_code='GA' AND county='Dodge' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31024', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Putnam' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31025', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Houston' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31027', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Laurens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31028', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Houston' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31029', 'GA', id, 1, 'Spans Monroe, Butts' FROM tax_rates
WHERE state_code='GA' AND county='Monroe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31030', 'GA', id, 1, 'Spans Peach, Crawford, Macon, Houston' FROM tax_rates
WHERE state_code='GA' AND county='Peach' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31031', 'GA', id, 1, 'Spans Wilkinson, Twiggs, Baldwin, Jones' FROM tax_rates
WHERE state_code='GA' AND county='Wilkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31032', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Jones' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31033', 'GA', id, 1, 'Spans Jones, Baldwin' FROM tax_rates
WHERE state_code='GA' AND county='Jones' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31034', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Baldwin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31035', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Washington' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31036', 'GA', id, 0, 'Spans Pulaski, Houston, Bleckley, Dodge' FROM tax_rates
WHERE state_code='GA' AND county='Pulaski' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31037', 'GA', id, 0, 'Spans Telfair, Dodge, Wheeler, Laurens' FROM tax_rates
WHERE state_code='GA' AND county='Telfair' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31038', 'GA', id, 1, 'Spans Jones, Jasper, Putnam' FROM tax_rates
WHERE state_code='GA' AND county='Jones' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31039', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Taylor' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31041', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Macon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31042', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wilkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31044', 'GA', id, 1, 'Spans Twiggs, Wilkinson' FROM tax_rates
WHERE state_code='GA' AND county='Twiggs' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31045', 'GA', id, 1, 'Spans Hancock, Warren' FROM tax_rates
WHERE state_code='GA' AND county='Hancock' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31046', 'GA', id, 1, 'Spans Monroe, Jones' FROM tax_rates
WHERE state_code='GA' AND county='Monroe' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31047', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Houston' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31049', 'GA', id, 1, 'Spans Johnson, Emanuel' FROM tax_rates
WHERE state_code='GA' AND county='Johnson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31050', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Crawford' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31051', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Dooly' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31052', 'GA', id, 0, 'Spans Bibb, Crawford' FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31054', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wilkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31055', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Telfair' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31057', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Macon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31058', 'GA', id, 1, 'Spans Taylor, Marion, Schley' FROM tax_rates
WHERE state_code='GA' AND county='Taylor' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31060', 'GA', id, 0, 'Spans Telfair, Dodge' FROM tax_rates
WHERE state_code='GA' AND county='Telfair' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31061', 'GA', id, 1, 'Spans Baldwin, Wilkinson, Hancock, Putnam, Jones' FROM tax_rates
WHERE state_code='GA' AND county='Baldwin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31062', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Baldwin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31063', 'GA', id, 0, 'Spans Macon, Dooly' FROM tax_rates
WHERE state_code='GA' AND county='Macon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31064', 'GA', id, 1, 'Spans Jasper, Newton' FROM tax_rates
WHERE state_code='GA' AND county='Jasper' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31065', 'GA', id, 1, 'Spans Laurens, Bleckley, Wilkinson' FROM tax_rates
WHERE state_code='GA' AND county='Laurens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31066', 'GA', id, 1, 'Spans Crawford, Monroe, Bibb' FROM tax_rates
WHERE state_code='GA' AND county='Crawford' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31067', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Washington' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31068', 'GA', id, 1, 'Spans Macon, Schley' FROM tax_rates
WHERE state_code='GA' AND county='Macon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31069', 'GA', id, 1, 'Spans Houston, Peach, Macon' FROM tax_rates
WHERE state_code='GA' AND county='Houston' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31070', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Dooly' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31071', 'GA', id, 1, 'Spans Pulaski, Wilcox' FROM tax_rates
WHERE state_code='GA' AND county='Pulaski' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31072', 'GA', id, 1, 'Spans Wilcox, Crisp, Dooly' FROM tax_rates
WHERE state_code='GA' AND county='Wilcox' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31075', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Laurens' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31076', 'GA', id, 0, 'Spans Taylor, Macon' FROM tax_rates
WHERE state_code='GA' AND county='Taylor' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31077', 'GA', id, 0, 'Spans Dodge, Telfair' FROM tax_rates
WHERE state_code='GA' AND county='Dodge' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31078', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Crawford' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31079', 'GA', id, 1, 'Spans Wilcox, Ben Hill' FROM tax_rates
WHERE state_code='GA' AND county='Wilcox' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31081', 'GA', id, 1, 'Spans Taylor, Macon, Schley' FROM tax_rates
WHERE state_code='GA' AND county='Taylor' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31082', 'GA', id, 1, 'Spans Washington, Baldwin' FROM tax_rates
WHERE state_code='GA' AND county='Washington' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31083', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Telfair' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31084', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wilcox' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31085', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Jasper' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31087', 'GA', id, 1, 'Spans Hancock, Baldwin, Washington' FROM tax_rates
WHERE state_code='GA' AND county='Hancock' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31088', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Houston' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31089', 'GA', id, 1, 'Spans Washington, Johnson' FROM tax_rates
WHERE state_code='GA' AND county='Washington' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31090', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wilkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31091', 'GA', id, 0, 'Spans Dooly, Pulaski, Houston' FROM tax_rates
WHERE state_code='GA' AND county='Dooly' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31092', 'GA', id, 1, 'Spans Dooly, Crisp, Wilcox' FROM tax_rates
WHERE state_code='GA' AND county='Dooly' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31093', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Houston' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31094', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Washington' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31096', 'GA', id, 1, 'Spans Johnson, Washington, Laurens' FROM tax_rates
WHERE state_code='GA' AND county='Johnson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31097', 'GA', id, 0, 'Spans Upson, Lamar, Monroe' FROM tax_rates
WHERE state_code='GA' AND county='Upson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31098', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Houston' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31201', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31204', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31206', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31207', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31210', 'GA', id, 1, 'Spans Bibb, Monroe' FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31211', 'GA', id, 1, 'Spans Jones, Bibb' FROM tax_rates
WHERE state_code='GA' AND county='Jones' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31213', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31216', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31217', 'GA', id, 1, 'Spans Jones, Bibb, Twiggs' FROM tax_rates
WHERE state_code='GA' AND county='Jones' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31220', 'GA', id, 1, 'Spans Bibb, Monroe' FROM tax_rates
WHERE state_code='GA' AND county='Bibb' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31301', 'GA', id, 0, 'Spans Liberty, Long' FROM tax_rates
WHERE state_code='GA' AND county='Liberty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31302', 'GA', id, 1, 'Spans Chatham, Effingham' FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31303', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Effingham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31305', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='McIntosh' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31307', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Effingham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31308', 'GA', id, 0, 'Spans Bryan, Bulloch' FROM tax_rates
WHERE state_code='GA' AND county='Bryan' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31309', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Liberty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31312', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Effingham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31313', 'GA', id, 0, 'Spans Liberty, Long' FROM tax_rates
WHERE state_code='GA' AND county='Liberty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31314', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Liberty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31315', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Liberty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31316', 'GA', id, 0, 'Spans Long, Liberty' FROM tax_rates
WHERE state_code='GA' AND county='Long' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31318', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Effingham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31320', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Liberty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31321', 'GA', id, 0, 'Spans Bryan, Bulloch' FROM tax_rates
WHERE state_code='GA' AND county='Bryan' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31322', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31323', 'GA', id, 1, 'Spans Liberty, McIntosh, Long' FROM tax_rates
WHERE state_code='GA' AND county='Liberty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31324', 'GA', id, 0, 'Spans Bryan, Liberty' FROM tax_rates
WHERE state_code='GA' AND county='Bryan' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31326', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Effingham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31327', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='McIntosh' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31328', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31329', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Effingham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31331', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='McIntosh' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31333', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Liberty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31401', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31404', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31405', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31406', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31407', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31408', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31409', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31410', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31411', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31415', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31419', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31421', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Chatham' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31501', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Ware' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31503', 'GA', id, 0, 'Spans Ware, Brantley, Pierce' FROM tax_rates
WHERE state_code='GA' AND county='Ware' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31510', 'GA', id, 0, 'Spans Bacon, Pierce' FROM tax_rates
WHERE state_code='GA' AND county='Bacon' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31512', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coffee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31513', 'GA', id, 0, 'Spans Appling, Jeff Davis' FROM tax_rates
WHERE state_code='GA' AND county='Appling' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31516', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Pierce' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31518', 'GA', id, 0, 'Spans Appling, Pierce, Wayne' FROM tax_rates
WHERE state_code='GA' AND county='Appling' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31519', 'GA', id, 0, 'Spans Coffee, Jeff Davis' FROM tax_rates
WHERE state_code='GA' AND county='Coffee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31520', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Glynn' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31522', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Glynn' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31523', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Glynn' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31524', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Glynn' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31525', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Glynn' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31527', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Glynn' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31532', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Jeff Davis' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31533', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coffee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31535', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coffee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31537', 'GA', id, 1, 'Spans Charlton, Camden' FROM tax_rates
WHERE state_code='GA' AND county='Charlton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31539', 'GA', id, 0, 'Spans Jeff Davis, Appling' FROM tax_rates
WHERE state_code='GA' AND county='Jeff Davis' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31542', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Brantley' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31543', 'GA', id, 1, 'Spans Brantley, Wayne, Glynn' FROM tax_rates
WHERE state_code='GA' AND county='Brantley' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31544', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Telfair' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31545', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wayne' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31546', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wayne' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31547', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Camden' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31548', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Camden' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31549', 'GA', id, 0, 'Spans Telfair, Wheeler' FROM tax_rates
WHERE state_code='GA' AND county='Telfair' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31550', 'GA', id, 0, 'Spans Ware, Clinch' FROM tax_rates
WHERE state_code='GA' AND county='Ware' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31551', 'GA', id, 0, 'Spans Pierce, Bacon' FROM tax_rates
WHERE state_code='GA' AND county='Pierce' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31552', 'GA', id, 0, 'Spans Ware, Atkinson, Coffee' FROM tax_rates
WHERE state_code='GA' AND county='Ware' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31553', 'GA', id, 0, 'Spans Brantley, Charlton' FROM tax_rates
WHERE state_code='GA' AND county='Brantley' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31554', 'GA', id, 0, 'Spans Coffee, Ware, Bacon' FROM tax_rates
WHERE state_code='GA' AND county='Coffee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31555', 'GA', id, 0, 'Spans Wayne, Appling' FROM tax_rates
WHERE state_code='GA' AND county='Wayne' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31556', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Pierce' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31557', 'GA', id, 0, 'Spans Pierce, Brantley, Appling' FROM tax_rates
WHERE state_code='GA' AND county='Pierce' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31558', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Camden' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31560', 'GA', id, 0, 'Spans Wayne, Appling' FROM tax_rates
WHERE state_code='GA' AND county='Wayne' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31561', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Glynn' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31562', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Charlton' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31563', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Appling' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31564', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Ware' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31565', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Camden' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31566', 'GA', id, 1, 'Spans Brantley, Glynn, Camden' FROM tax_rates
WHERE state_code='GA' AND county='Brantley' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31567', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Coffee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31568', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Camden' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31569', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Camden' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31599', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Wayne' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31601', 'GA', id, 0, 'Spans Lowndes, Brooks' FROM tax_rates
WHERE state_code='GA' AND county='Lowndes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31602', 'GA', id, 0, 'Spans Lowndes, Brooks' FROM tax_rates
WHERE state_code='GA' AND county='Lowndes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31605', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Lowndes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31606', 'GA', id, 0, 'Spans Lowndes, Echols' FROM tax_rates
WHERE state_code='GA' AND county='Lowndes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31620', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cook' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31622', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Berrien' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31623', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Clinch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31624', 'GA', id, 0, 'Spans Atkinson, Coffee, Ware' FROM tax_rates
WHERE state_code='GA' AND county='Atkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31625', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Brooks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31626', 'GA', id, 0, 'Spans Thomas, Brooks' FROM tax_rates
WHERE state_code='GA' AND county='Thomas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31627', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cook' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31629', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Brooks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31630', 'GA', id, 0, 'Spans Clinch, Echols' FROM tax_rates
WHERE state_code='GA' AND county='Clinch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31631', 'GA', id, 0, 'Spans Echols, Clinch, Charlton, Ware' FROM tax_rates
WHERE state_code='GA' AND county='Echols' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31632', 'GA', id, 1, 'Spans Lowndes, Cook' FROM tax_rates
WHERE state_code='GA' AND county='Lowndes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31634', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Clinch' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31635', 'GA', id, 0, 'Spans Lanier, Atkinson, Clinch, Lowndes' FROM tax_rates
WHERE state_code='GA' AND county='Lanier' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31636', 'GA', id, 0, 'Spans Echols, Lowndes' FROM tax_rates
WHERE state_code='GA' AND county='Echols' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31637', 'GA', id, 1, 'Spans Cook, Berrien, Colquitt, Tift' FROM tax_rates
WHERE state_code='GA' AND county='Cook' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31638', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Brooks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31639', 'GA', id, 1, 'Spans Berrien, Lanier' FROM tax_rates
WHERE state_code='GA' AND county='Berrien' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31641', 'GA', id, 0, 'Spans Lowndes, Lanier' FROM tax_rates
WHERE state_code='GA' AND county='Lowndes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31642', 'GA', id, 0, 'Spans Atkinson, Clinch' FROM tax_rates
WHERE state_code='GA' AND county='Atkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31643', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Brooks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31645', 'GA', id, 1, 'Spans Berrien, Lanier, Lowndes' FROM tax_rates
WHERE state_code='GA' AND county='Berrien' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31647', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Cook' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31648', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Echols' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31649', 'GA', id, 0, 'Spans Lanier, Echols' FROM tax_rates
WHERE state_code='GA' AND county='Lanier' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31650', 'GA', id, 0, 'Spans Atkinson, Coffee' FROM tax_rates
WHERE state_code='GA' AND county='Atkinson' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31698', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Lowndes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31699', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Lowndes' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31701', 'GA', id, 1, 'Spans Dougherty, Lee' FROM tax_rates
WHERE state_code='GA' AND county='Dougherty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31704', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Dougherty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31705', 'GA', id, 1, 'Spans Dougherty, Worth, Mitchell' FROM tax_rates
WHERE state_code='GA' AND county='Dougherty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31707', 'GA', id, 1, 'Spans Dougherty, Lee' FROM tax_rates
WHERE state_code='GA' AND county='Dougherty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31709', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Sumter' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31711', 'GA', id, 1, 'Spans Sumter, Macon, Schley' FROM tax_rates
WHERE state_code='GA' AND county='Sumter' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31712', 'GA', id, 1, 'Spans Crisp, Turner, Worth' FROM tax_rates
WHERE state_code='GA' AND county='Crisp' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31714', 'GA', id, 1, 'Spans Turner, Worth, Tift' FROM tax_rates
WHERE state_code='GA' AND county='Turner' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31716', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Mitchell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31719', 'GA', id, 1, 'Spans Sumter, Schley' FROM tax_rates
WHERE state_code='GA' AND county='Sumter' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31720', 'GA', id, 0, 'Spans Brooks, Thomas' FROM tax_rates
WHERE state_code='GA' AND county='Brooks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31721', 'GA', id, 1, 'Spans Dougherty, Baker, Lee, Terrell' FROM tax_rates
WHERE state_code='GA' AND county='Dougherty' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31722', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Colquitt' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31727', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Tift' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31730', 'GA', id, 1, 'Spans Mitchell, Decatur' FROM tax_rates
WHERE state_code='GA' AND county='Mitchell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31733', 'GA', id, 0, 'Spans Irwin, Tift' FROM tax_rates
WHERE state_code='GA' AND county='Irwin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31735', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Sumter' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31738', 'GA', id, 1, 'Spans Thomas, Colquitt' FROM tax_rates
WHERE state_code='GA' AND county='Thomas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31743', 'GA', id, 0, 'Spans Lee, Sumter' FROM tax_rates
WHERE state_code='GA' AND county='Lee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31744', 'GA', id, 1, 'Spans Worth, Colquitt, Mitchell' FROM tax_rates
WHERE state_code='GA' AND county='Worth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31747', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Colquitt' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31749', 'GA', id, 1, 'Spans Berrien, Irwin, Tift' FROM tax_rates
WHERE state_code='GA' AND county='Berrien' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31750', 'GA', id, 1, 'Spans Ben Hill, Irwin, Wilcox' FROM tax_rates
WHERE state_code='GA' AND county='Ben Hill' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31753', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Colquitt' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31756', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Colquitt' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31757', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Thomas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31763', 'GA', id, 1, 'Spans Lee, Dougherty, Terrell' FROM tax_rates
WHERE state_code='GA' AND county='Lee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31764', 'GA', id, 0, 'Spans Sumter, Lee' FROM tax_rates
WHERE state_code='GA' AND county='Sumter' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31765', 'GA', id, 1, 'Spans Mitchell, Thomas, Colquitt' FROM tax_rates
WHERE state_code='GA' AND county='Mitchell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31768', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Colquitt' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31771', 'GA', id, 1, 'Spans Colquitt, Worth' FROM tax_rates
WHERE state_code='GA' AND county='Colquitt' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31772', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Worth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31773', 'GA', id, 1, 'Spans Thomas, Grady, Colquitt' FROM tax_rates
WHERE state_code='GA' AND county='Thomas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31774', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Irwin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31775', 'GA', id, 1, 'Spans Colquitt, Tift, Worth' FROM tax_rates
WHERE state_code='GA' AND county='Colquitt' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31778', 'GA', id, 1, 'Spans Brooks, Thomas, Colquitt' FROM tax_rates
WHERE state_code='GA' AND county='Brooks' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31779', 'GA', id, 0, 'Spans Mitchell, Grady, Thomas' FROM tax_rates
WHERE state_code='GA' AND county='Mitchell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31780', 'GA', id, 0, 'Spans Sumter, Webster' FROM tax_rates
WHERE state_code='GA' AND county='Sumter' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31781', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Worth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31783', 'GA', id, 1, 'Spans Turner, Irwin, Ben Hill' FROM tax_rates
WHERE state_code='GA' AND county='Turner' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31784', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Mitchell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31787', 'GA', id, 0, 'Spans Lee, Sumter' FROM tax_rates
WHERE state_code='GA' AND county='Lee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31788', 'GA', id, 1, 'Spans Colquitt, Brooks' FROM tax_rates
WHERE state_code='GA' AND county='Colquitt' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31789', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Worth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31790', 'GA', id, 1, 'Spans Turner, Irwin, Tift' FROM tax_rates
WHERE state_code='GA' AND county='Turner' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31791', 'GA', id, 1, 'Spans Worth, Dougherty' FROM tax_rates
WHERE state_code='GA' AND county='Worth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31792', 'GA', id, 0, 'Spans Thomas, Grady' FROM tax_rates
WHERE state_code='GA' AND county='Thomas' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31793', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Tift' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31794', 'GA', id, 1, 'Spans Tift, Berrien' FROM tax_rates
WHERE state_code='GA' AND county='Tift' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31795', 'GA', id, 0, 'Spans Worth, Tift' FROM tax_rates
WHERE state_code='GA' AND county='Worth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31796', 'GA', id, 0, 'Spans Worth, Crisp' FROM tax_rates
WHERE state_code='GA' AND county='Worth' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31798', 'GA', id, 0, 'Spans Irwin, Coffee, Ben Hill' FROM tax_rates
WHERE state_code='GA' AND county='Irwin' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31801', 'GA', id, 1, 'Spans Marion, Talbot, Muscogee' FROM tax_rates
WHERE state_code='GA' AND county='Marion' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31803', 'GA', id, 1, 'Spans Marion, Schley, Webster' FROM tax_rates
WHERE state_code='GA' AND county='Marion' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31804', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31805', 'GA', id, 1, 'Spans Chattahoochee, Stewart' FROM tax_rates
WHERE state_code='GA' AND county='Chattahoochee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31806', 'GA', id, 1, 'Spans Schley, Macon' FROM tax_rates
WHERE state_code='GA' AND county='Schley' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31807', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31808', 'GA', id, 1, 'Spans Harris, Muscogee' FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31810', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Talbot' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31811', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31812', 'GA', id, 0, 'Spans Talbot, Taylor' FROM tax_rates
WHERE state_code='GA' AND county='Talbot' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31814', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Stewart' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31815', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Stewart' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31816', 'GA', id, 0, 'Spans Meriwether, Talbot' FROM tax_rates
WHERE state_code='GA' AND county='Meriwether' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31820', 'GA', id, 1, 'Spans Muscogee, Harris' FROM tax_rates
WHERE state_code='GA' AND county='Muscogee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31821', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Stewart' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31822', 'GA', id, 1, 'Spans Harris, Troup, Meriwether' FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31823', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31824', 'GA', id, 0, 'Spans Webster, Marion' FROM tax_rates
WHERE state_code='GA' AND county='Webster' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31825', 'GA', id, 0, 'Spans Stewart, Webster' FROM tax_rates
WHERE state_code='GA' AND county='Stewart' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31826', 'GA', id, 0, 'Spans Harris, Talbot' FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31827', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Talbot' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31829', 'GA', id, 1, 'Spans Muscogee, Harris' FROM tax_rates
WHERE state_code='GA' AND county='Muscogee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31830', 'GA', id, 0, 'Spans Meriwether, Harris' FROM tax_rates
WHERE state_code='GA' AND county='Meriwether' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31831', 'GA', id, 0, 'Spans Harris, Talbot' FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31832', 'GA', id, 0, 'Spans Webster, Randolph' FROM tax_rates
WHERE state_code='GA' AND county='Webster' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31833', 'GA', id, 1, 'Spans Harris, Troup' FROM tax_rates
WHERE state_code='GA' AND county='Harris' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31836', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Talbot' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31901', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Muscogee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31903', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Muscogee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31904', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Muscogee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31905', 'GA', id, 0, 'Spans Chattahoochee, Muscogee' FROM tax_rates
WHERE state_code='GA' AND county='Chattahoochee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31906', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Muscogee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31907', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Muscogee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '31909', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Muscogee' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '36855', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Troup' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39813', 'GA', id, 0, 'Spans Calhoun, Early, Baker' FROM tax_rates
WHERE state_code='GA' AND county='Calhoun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39815', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Decatur' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39817', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Decatur' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39819', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Decatur' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39823', 'GA', id, 1, 'Spans Early, Miller' FROM tax_rates
WHERE state_code='GA' AND county='Early' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39824', 'GA', id, 1, 'Spans Clay, Early' FROM tax_rates
WHERE state_code='GA' AND county='Clay' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39825', 'GA', id, 1, 'Spans Decatur, Seminole' FROM tax_rates
WHERE state_code='GA' AND county='Decatur' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39826', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Terrell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39827', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Grady' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39828', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Grady' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39832', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Early' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39834', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Decatur' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39836', 'GA', id, 1, 'Spans Randolph, Clay' FROM tax_rates
WHERE state_code='GA' AND county='Randolph' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39837', 'GA', id, 1, 'Spans Miller, Baker, Decatur, Early' FROM tax_rates
WHERE state_code='GA' AND county='Miller' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39840', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Randolph' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39841', 'GA', id, 1, 'Spans Early, Baker, Miller' FROM tax_rates
WHERE state_code='GA' AND county='Early' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39842', 'GA', id, 0, 'Spans Terrell, Calhoun' FROM tax_rates
WHERE state_code='GA' AND county='Terrell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39845', 'GA', id, 1, 'Spans Seminole, Miller, Early' FROM tax_rates
WHERE state_code='GA' AND county='Seminole' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39846', 'GA', id, 1, 'Spans Calhoun, Clay, Randolph' FROM tax_rates
WHERE state_code='GA' AND county='Calhoun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39851', 'GA', id, 1, 'Spans Clay, Randolph' FROM tax_rates
WHERE state_code='GA' AND county='Clay' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39854', 'GA', id, 1, 'Spans Quitman, Clay' FROM tax_rates
WHERE state_code='GA' AND county='Quitman' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39859', 'GA', id, 1, 'Spans Seminole, Miller' FROM tax_rates
WHERE state_code='GA' AND county='Seminole' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39861', 'GA', id, 0, 'Spans Early, Seminole' FROM tax_rates
WHERE state_code='GA' AND county='Early' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39862', 'GA', id, 0, 'Spans Calhoun, Baker' FROM tax_rates
WHERE state_code='GA' AND county='Calhoun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39866', 'GA', id, 0, 'Spans Calhoun, Randolph' FROM tax_rates
WHERE state_code='GA' AND county='Calhoun' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39867', 'GA', id, 1, 'Spans Quitman, Randolph, Clay, Stewart' FROM tax_rates
WHERE state_code='GA' AND county='Quitman' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39870', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Baker' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39877', 'GA', id, 0, 'Spans Terrell, Webster' FROM tax_rates
WHERE state_code='GA' AND county='Terrell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39885', 'GA', id, 0, NULL FROM tax_rates
WHERE state_code='GA' AND county='Terrell' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39886', 'GA', id, 0, 'Spans Randolph, Terrell, Calhoun' FROM tax_rates
WHERE state_code='GA' AND county='Randolph' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
INSERT INTO tax_zip_jurisdictions (zip, state_code, tax_rate_id, is_ambiguous, notes)
SELECT '39897', 'GA', id, 1, 'Spans Grady, Decatur' FROM tax_rates
WHERE state_code='GA' AND county='Grady' AND is_active=1
  AND (effective_to IS NULL) ORDER BY LENGTH(name) LIMIT 1
ON DUPLICATE KEY UPDATE tax_rate_id=VALUES(tax_rate_id), is_ambiguous=VALUES(is_ambiguous), notes=VALUES(notes);
