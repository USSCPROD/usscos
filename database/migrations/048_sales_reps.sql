-- Migration: 048_sales_reps
-- Description: Sales reps as their own entity, populated from QuickBooks.
--
-- WHY NOT USERS: the QuickBooks Rep list contains 34 names and not one of them is a
-- USSCOS user. Most are outside or former reps with no reason to have a login, and four
-- entries aren't people at all — HOUSE ACCOUNT, WEBSITE ORDER, No sales rep, TURF TANK.
-- QuickBooks itself keeps Rep as a separate list from users, and so do we.
--
-- The existing `rep_id` columns on quotes/sales_orders/invoices/customers point at
-- `users` and stay as they are — those are for internal assignment. This adds a parallel
-- `sales_rep_id` for "who gets credit for the sale", which is what commission is based on.
--
-- `user_id` links a rep to a login where the same person has one. Currently none do.

CREATE TABLE IF NOT EXISTS sales_reps (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    quickbooks_name VARCHAR(150) NOT NULL UNIQUE,   -- exact string from the QB Rep list
    rep_type        ENUM('person','house','website','partner','none') NOT NULL DEFAULT 'person',
    user_id         INT UNSIGNED NULL,              -- login, where this rep has one
    commission_rate DECIMAL(6,3) NULL,              -- percent; for the commission work later
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    notes           VARCHAR(255) NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_sales_reps_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_sales_reps_type   (rep_type),
    INDEX idx_sales_reps_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE invoices
    ADD COLUMN sales_rep_id INT UNSIGNED NULL AFTER rep_id,
    ADD CONSTRAINT fk_invoices_sales_rep FOREIGN KEY (sales_rep_id)
        REFERENCES sales_reps (id) ON DELETE SET NULL,
    ADD INDEX idx_invoices_sales_rep (sales_rep_id);

ALTER TABLE customers
    ADD COLUMN sales_rep_id INT UNSIGNED NULL AFTER rep_id,
    ADD CONSTRAINT fk_customers_sales_rep FOREIGN KEY (sales_rep_id)
        REFERENCES sales_reps (id) ON DELETE SET NULL,
    ADD INDEX idx_customers_sales_rep (sales_rep_id);

-- Seed the list. rep_type separates real people from the placeholder entries so
-- reporting can exclude them — "No sales rep" carries 68% of invoices and would
-- otherwise dominate every per-rep figure.
INSERT INTO sales_reps (name, quickbooks_name, rep_type, is_active, notes) VALUES
    ('No sales rep',        'No sales rep',        'none',    1, 'QuickBooks placeholder — exclude from rep reporting'),
    ('House Account',       'HOUSE ACCOUNT',       'house',   1, 'Not an individual rep'),
    ('Website Order',       'WEBSITE ORDER',       'website', 1, 'Online orders — no rep credit'),
    ('Turf Tank',           'TURF TANK',           'partner', 1, 'Partner/distributor, not an individual'),

    ('Larry Fitz',          'Larry Fitz',          'person',  1, NULL),
    ('Chris Danis',         'CHRIS DANIS',         'person',  1, NULL),
    ('Jack Curlings',       'JACK CURLINGS',       'person',  1, NULL),
    ('Derreck Cole',        'Derreck Cole',        'person',  1, NULL),
    ('Hormuz P Irani',      'HORMUZ P IRANI',      'person',  1, NULL),
    ('Bryan McKinney',      'BRYAN MCKKINEY.',     'person',  1, 'QB spelling: BRYAN MCKKINEY.'),
    ('Keith Richardson',    'KEITH RICHARDSON',    'person',  1, NULL),
    ('William Leavell',     'WILLIAM LEAVELL',     'person',  1, NULL),
    ('John Boss',           'JOHN BOSS 1',         'person',  1, 'QB name has a trailing 1'),
    ('Paul Lundberg',       'PAUL LUNDBERG',       'person',  1, NULL),
    ('William Harrison',    'William harrison',    'person',  1, NULL),
    ('Steve Willard',       'STEVE WILLARD',       'person',  1, NULL),
    ('David Webster',       'DAVID WEBSTER',       'person',  1, NULL),
    ('Joe Valenti',         'Joe Valenti.',        'person',  1, NULL),
    ('John Myers',          'JOHN MYERS',          'person',  1, NULL),
    ('Merle Schreckengost', 'MERLE SCHRECKENGOST', 'person',  1, NULL),
    ('Sam Hardin',          'Sam Hardin',          'person',  1, NULL),
    ('Steve Shapiro',       'Steve Shapiro',       'person',  1, NULL),
    ('Wes Lee',             'Wes Lee',             'person',  1, NULL),
    ('Chris Terebesi',      'CHRIS TEREBESI',      'person',  1, NULL),
    ('Willie Dawson',       'Willie Dawson',       'person',  1, NULL),
    ('Don Flippo',          'DON FLIPPO',          'person',  1, NULL),
    ('Gene Weeks',          'GENE WEEKS',          'person',  1, NULL),
    ('Phil Schmitt',        'PHIL SCHMITT.',       'person',  1, NULL),
    ("Tom O'Kelly",         "TOM O'KELLY",         'person',  1, NULL),
    ('Bill Kennedy',        'BILL KENNEDY',        'person',  1, NULL),
    ('Chris Cox',           'CHRIS COX',           'person',  1, NULL),
    ('Crystal Thompson',    'Crystal Thompson',    'person',  1, NULL),
    ('Herman Irani',        'Herman Irani',        'person',  1, 'Possibly the same person as HORMUZ P IRANI — confirm'),
    ('Crystal / Sam',       'Crystal/Sam',         'person',  1, 'Two people share this QB rep code — split if commission needs it')
ON DUPLICATE KEY UPDATE
    name     = VALUES(name),
    rep_type = VALUES(rep_type),
    notes    = VALUES(notes);
