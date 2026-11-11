-- A starting list of LTL carriers, so the dropdown is useful on day one.
--
-- Names and SCACs only. tracking_url_template is deliberately left empty: a lookup URL
-- that is wrong sends somebody to a page that says the PRO does not exist, which reads
-- as a lost pallet. Fill each one in from the carrier's own site when you first use
-- them, under Admin > Freight Carriers.
--
-- Prune it. These are the common southeastern LTL carriers, not a statement about who
-- Keubix actually book — Chip should delete the ones USSC never sees.

INSERT INTO freight_carriers (name, scac, sort_order, is_active)
SELECT * FROM (
    SELECT 'AAA Cooper Transportation'   AS name, 'AACT' AS scac, 10 AS sort_order, 1 AS is_active UNION ALL
    SELECT 'ABF Freight',                       'ABFS', 20, 1 UNION ALL
    SELECT 'Averitt Express',                   'AVRT', 30, 1 UNION ALL
    SELECT 'Estes Express Lines',               'EXLA', 40, 1 UNION ALL
    SELECT 'Old Dominion Freight Line',         'ODFL', 50, 1 UNION ALL
    SELECT 'R+L Carriers',                      'RLCA', 60, 1 UNION ALL
    SELECT 'Saia LTL Freight',                  'SAIA', 70, 1 UNION ALL
    SELECT 'Southeastern Freight Lines',        'SEFL', 80, 1 UNION ALL
    SELECT 'TForce Freight',                    'UPGF', 90, 1 UNION ALL
    SELECT 'XPO',                               'CNWY', 100, 1
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM freight_carriers fc WHERE fc.name = seed.name);
