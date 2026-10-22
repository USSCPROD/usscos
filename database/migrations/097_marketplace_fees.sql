-- What Amazon keeps.
--
-- USSC ships these orders and invoices them at the price the buyer paid, but that is not
-- the money that arrives. Amazon deducts its referral fee and deposits the rest, and the
-- accountant books the net. Nothing in USSCOS has ever recorded the gap.
--
-- WHY IT MATTERS. Amazon is 9,694 invoices and 2.4 million dollars - 28 percent of every
-- invoice here - and DSW alone is 1.1 million of it. With no fee recorded, USSCOS reports
-- the gross as though USSC received it, so margin on the single biggest selling product
-- is overstated by whatever Amazon's cut is. Any pricing decision made on those numbers
-- is made on a figure that was never collected.
--
-- WHERE THE NUMBER COMES FROM. Amazon's settlement report, not a percentage. A referral
-- rate varies by category and changes, and an estimate would quietly become a fact that
-- nobody could trace. fee_source records which it was, so an estimate can never be
-- mistaken for a settled figure.
--
-- NOT A LINE ITEM. The fee is a cost USSC pays Amazon, not something the buyer was
-- charged. Putting it on the invoice would change the invoice total and misstate the
-- sale. It sits beside the invoice instead.
--
-- NOTE no semicolons in these comments. See the note in 054.

ALTER TABLE sales_orders
    ADD COLUMN marketplace_fee DECIMAL(14,2) NOT NULL DEFAULT 0.00
        COMMENT 'What the marketplace keeps - a cost to USSC, never billed to the buyer'
        AFTER marketplace_tax_collected,
    ADD COLUMN marketplace_fee_source ENUM('none','manual','settlement') NOT NULL DEFAULT 'none'
        COMMENT 'settlement = taken from Amazon own report. manual = typed by a person'
        AFTER marketplace_fee;

ALTER TABLE invoices
    ADD COLUMN marketplace_fee DECIMAL(14,2) NOT NULL DEFAULT 0.00
        COMMENT 'What the marketplace keeps - a cost to USSC, never billed to the buyer'
        AFTER marketplace_tax_collected,
    ADD COLUMN marketplace_fee_source ENUM('none','manual','settlement') NOT NULL DEFAULT 'none'
        COMMENT 'settlement = taken from Amazon own report. manual = typed by a person'
        AFTER marketplace_fee;


-- An expense account for it, so the figure has somewhere to belong when the accounting
-- side is wired up. Merchant / Payment Processing Fees already exists and covers card
-- processing - a marketplace commission is a different animal and worth seeing apart
-- from it, because the two answer different questions.
INSERT INTO chart_of_accounts (account_code, name, account_type, account_subtype, normal_balance, description, is_active)
SELECT '6155', 'Marketplace Fees', 'expense', 'Selling', 'debit',
       'Amazon referral and related marketplace fees. A cost of selling, kept apart from card processing fees.', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM chart_of_accounts WHERE account_code = '6155');
