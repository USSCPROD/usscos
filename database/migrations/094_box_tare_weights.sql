-- Empty box weights: 2 lb each, as a working figure.
--
-- Given as a round number for all four rather than measured, which is fine for what it
-- does. A package is now contents plus box rather than contents alone, and 2 lb on a 20 to
-- 37 lb parcel is close enough that no rate changes on it.
--
-- Worth knowing it is an estimate rather than a measurement, because it is the one number
-- here that nobody has weighed. If a carrier ever disputes a weight, this is the figure to
-- check first - everything else came from someone who handles the product.
--
-- NOTE no semicolons in these comments. See the note in 054.

UPDATE shipping_boxes
SET tare_lb = 2.0,
    notes   = CONCAT(COALESCE(notes, ''), ' Tare 2 lb - a working figure, not weighed.')
WHERE tare_lb IS NULL;
