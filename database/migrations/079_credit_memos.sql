-- Credit memos, and making every report that already exists handle them correctly.
--
-- invoices.invoice_type has had a credit_memo value since the table was created and
-- NOTHING has ever filtered on it. There are 42 queries against invoices and roughly
-- fifteen of them sum money. Raising a credit today would have been counted as revenue by
-- Sales by Rep, Sales by Employee, customer lifetime value, the A/R aging, the dashboard
-- and the tax report - every one of them, silently.
--
-- THE SIGN LIVES IN THE DATA. A credit memo stores NEGATIVE line totals and negative
-- header totals. Every existing SUM() is then correct without being touched, and so is
-- every query written in future by somebody who has never heard of this decision. The
-- alternative - editing fifteen queries to special-case a type - fixes today and leaves a
-- trap for tomorrow, because the sixteenth query will be written by someone who does not
-- know.
--
-- Quantities and unit prices stay POSITIVE on the lines. A credit for two pails is two
-- pails at their price, which is how it reads on paper. Only the line total and the header
-- carry the sign.
--
-- balance_due goes negative too, which is correct: it is money owed TO the customer, and it
-- nets against what they owe us in exactly the way A/R should.
--
-- NOTE no semicolons in these comments. See the note in 054.

-- What raised the credit, so a return and its credit memo can find each other.
ALTER TABLE stock_returns
    ADD COLUMN credit_invoice_id INT UNSIGNED NULL
        COMMENT 'The credit memo raised for this return, once there is one'
        AFTER credit_status,
    ADD CONSTRAINT fk_return_credit_invoice FOREIGN KEY (credit_invoice_id)
        REFERENCES invoices (id) ON DELETE SET NULL;


-- Which invoice a credit memo is against, so it can be read in context and applied.
ALTER TABLE invoices
    ADD COLUMN credits_invoice_id INT UNSIGNED NULL
        COMMENT 'For a credit memo: the invoice it credits'
        AFTER invoice_type,
    ADD CONSTRAINT fk_invoice_credits FOREIGN KEY (credits_invoice_id)
        REFERENCES invoices (id) ON DELETE SET NULL,
    ADD INDEX idx_invoice_type (invoice_type);
