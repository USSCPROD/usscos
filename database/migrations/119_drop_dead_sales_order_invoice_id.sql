-- Drop sales_orders.invoice_id, which has never been used.
--
-- Declared in 021 with a foreign key to invoices, and then never written and never
-- read: every sales order the system has ever created has it NULL, including the
-- ones that are fully invoiced.
--
-- The link that carries the meaning runs the other way, invoices.sales_order_id,
-- and it has to: one order can produce several invoices. SO 15 shipped 9 units on
-- one invoice and the remaining 21 on a second, and a single column on the order
-- cannot hold both. Anything reading this column to answer whether an order has
-- been invoiced would say no for ever.
--
-- The shipping queue already derives the current invoice with a subquery over
-- invoices.sales_order_id rather than trusting this column, so nothing changes
-- behaviorally. What goes away is a NULL column sitting in the sales order table
-- under exactly the name the next person will reach for.
--
-- Verified before writing this: zero rows with a non-null value, and no read or
-- write of the column anywhere in app/ or database/.

ALTER TABLE sales_orders DROP FOREIGN KEY fk_so_invoice;

ALTER TABLE sales_orders DROP COLUMN invoice_id;
