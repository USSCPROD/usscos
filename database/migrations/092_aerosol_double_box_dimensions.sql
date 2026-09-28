-- The aerosol double box: 18 x 12 x 10.
--
-- Which completes every box USSC actually ships in. Worth noting that it is 2,160 cubic
-- inches against the single box's 990 - more than twice, as an outer box should be, rather
-- than the tidy doubling a guess would have produced. That is why it was left blank.
--
-- ALL FOUR BOXES ARE DENSE ENOUGH THAT ACTUAL WEIGHT GOVERNS. Dimensional weight is
-- roughly length x width x height / 139, so:
--
--   AERO-1    990 cu in ->  7 lb dimensional against 18 lb actual
--   AERO-2  2,160 cu in -> 16 lb dimensional against 36 lb actual
--   FATCAN  1,188 cu in ->  9 lb dimensional against 26 lb actual
--   ROBO      756 cu in ->  5 lb dimensional against 29 lb or more actual
--
-- Paint is heavy for its size, so no package here will ever be rated on volume. The
-- dimensions still matter for the label being right, but they will not inflate a rate.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE shipping_boxes
SET length_in = 18, width_in = 12, height_in = 10,
    notes = 'Holds two 18 oz cases. Dimensions confirmed 28 Sep 2026 - tare weight still needed'
WHERE code = 'AERO-2';
