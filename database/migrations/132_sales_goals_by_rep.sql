-- Key sales goals on the rep, not on a user login.
--
-- WHY. sales_goals (migration 045) hangs off users.id. Attribution does not: it lives on
-- customers.sales_rep_id and invoices.sales_rep_id, which point at sales_reps. Most reps
-- are outside or former reps with no login at all — 3 of 30 active reps have one — so the
-- table as built could hold a goal for one rep in ten, and the dashboard comparing MTD
-- against a goal had nothing to compare against for everybody else.
--
-- Safe to restructure rather than add a second column: the table has never held a row.
-- Verified empty on the server before this was written, and a goal for a rep who cannot
-- log in is the normal case here rather than the exception, so carrying both keys would
-- just mean two ways to be wrong.
--
-- period_start is the first day of the period, and the unique key stops the same period
-- being targeted twice for one rep. A quarter is stored as its first month, a year as
-- 1 January: period_type says how far it runs.

ALTER TABLE sales_goals DROP FOREIGN KEY fk_goals_user;
ALTER TABLE sales_goals DROP INDEX uq_goal_period;
ALTER TABLE sales_goals DROP COLUMN user_id;

ALTER TABLE sales_goals
    ADD COLUMN sales_rep_id INT UNSIGNED NOT NULL AFTER id;

ALTER TABLE sales_goals
    ADD UNIQUE KEY uq_goal_period (sales_rep_id, period_type, period_start);

-- CASCADE: a goal is a target for a rep, not history worth keeping once the rep is gone.
-- Retiring a rep is done by clearing is_active, which leaves goals and their history alone.
ALTER TABLE sales_goals
    ADD CONSTRAINT fk_goals_rep FOREIGN KEY (sales_rep_id)
        REFERENCES sales_reps (id) ON DELETE CASCADE;
