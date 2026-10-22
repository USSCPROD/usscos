-- An opening date for accounts receivable.
--
-- THE PROBLEM. All 34,729 invoices in USSCOS came from QuickBooks. Their PAYMENTS did
-- not - 2024 and 2025 have none at all, 2026 has five. So every invoice looks unpaid,
-- and the dashboard has been reporting 1.63 million dollars overdue across 1,056
-- invoices. None of it is real. Most of it was settled years ago, in QuickBooks.
--
-- WHY THIS MATTERS MORE THAN A WRONG NUMBER. It is the largest, reddest thing on the
-- dashboard, and it is false. People learn quickly that the dashboard exaggerates, and
-- then they stop reading the warnings that are true - the stalled QuickBooks sync, the
-- tax rates going stale, the stock that has gone negative. One loud lie devalues every
-- honest signal next to it.
--
-- THE FIX is the ordinary accounting one: a cutover date. Invoices dated before it are
-- history whose payment record lives in QuickBooks, and USSCOS does not pretend to know
-- whether they were paid. Invoices on or after it are ours, and their A/R is real.
--
-- Left EMPTY on purpose. Setting it is a decision about when USSCOS starts owning
-- receivables, and nobody should discover that a date was picked for them. While it is
-- empty the dashboard explains the situation instead of reporting a figure it cannot
-- stand behind.
--
-- NOTE no semicolons in these comments. See the note in 054.

INSERT INTO settings (setting_key, setting_value, label, description)
VALUES (
    'ar_opening_date',
    '',
    'A/R Opening Date',
    'Invoices dated before this are historical - their payments live in QuickBooks, so USSCOS excludes them from A/R aging and overdue totals. Leave blank until USSCOS starts owning receivables.'
)
ON DUPLICATE KEY UPDATE
    label       = VALUES(label),
    description = VALUES(description);
