-- Migration: 046_seed_chart_of_accounts
-- Description: Seeds the chart of accounts from §1 of USSCos_Accounting_Module_Spec.md.
--
-- The spec's own six-way account_type (asset/liability/equity/revenue/cogs/expense) is
-- mapped onto the existing 15-value QuickBooks-derived enum on chart_of_accounts, which
-- is more granular and already in place. `account_subtype` carries the spec's wording so
-- nothing is lost in translation.
--
-- normal_balance matters and is set deliberately per account, not by type:
--   contra-asset accounts (1150 Allowance, 1450 Accumulated Depreciation) are CREDIT
--   contra-revenue accounts (4200 Returns, 4300 Discounts) are DEBIT
--   3300 Dividends Declared is DEBIT — it reduces equity
-- Getting these backwards is the classic way a seeded COA produces sign-flipped reports.
--
-- Re-runnable: matches on account_code, which is UNIQUE.

INSERT INTO chart_of_accounts
    (account_code, name, account_type, account_subtype, normal_balance, is_bank_account, description)
VALUES
-- ============================== ASSETS 1000–1499
('1000','Operating Checking','bank','Bank','debit',1,'Primary operating account'),
('1010','Reserve / Savings','bank','Bank','debit',1,'Tax reserve, rainy-day fund'),
('1090','Undeposited Funds','other_current_asset','Current Asset','debit',0,'Checks/cash received but not yet deposited — never skip this step or bank rec breaks'),
('1100','Accounts Receivable','accounts_receivable','Current Asset','debit',0,'Outstanding customer invoices'),
('1150','Allowance for Doubtful Accounts','other_current_asset','Contra-Asset','credit',0,'Reserve against uncollectible AR'),
('1200','Raw Materials Inventory','other_current_asset','Current Asset','debit',0,'Resins, pigments, solvents, packaging on hand'),
('1210','Work-in-Process Inventory','other_current_asset','Current Asset','debit',0,'Paint currently in production/mixing/batching'),
('1220','Finished Goods Inventory','other_current_asset','Current Asset','debit',0,'Completed, ready-to-ship product'),
('1300','Prepaid Expenses','other_current_asset','Current Asset','debit',0,'Insurance, annual licenses paid upfront'),
('1400','Manufacturing Equipment','fixed_asset','Fixed Asset','debit',0,'Mixers, filling lines, lab equipment'),
('1410','Vehicles','fixed_asset','Fixed Asset','debit',0,'Delivery trucks'),
('1420','Office/Warehouse Equipment','fixed_asset','Fixed Asset','debit',0,'Forklifts, computers, furniture'),
('1450','Accumulated Depreciation','fixed_asset','Contra-Asset','credit',0,'Offsets fixed asset accounts'),
('1500','Payment Processor Clearing','other_current_asset','Current Asset','debit',0,'Funds held by the processor before bank deposit'),

-- ============================== LIABILITIES 2000–2499
('2000','Accounts Payable','accounts_payable','Current Liability','credit',0,'Bills owed to vendors'),
('2100','Business Credit Card','credit_card','Current Liability','credit',0,'Company card balance'),
('2200','Sales Tax Payable','other_current_liability','Current Liability','credit',0,'Tax collected, owed to states — jurisdiction detail lives in the sales_tax_collected sub-ledger, not per-state accounts'),
('2300','Payroll Liabilities','other_current_liability','Current Liability','credit',0,'Net pay owed, employee withholdings'),
('2310','Payroll Taxes Payable','other_current_liability','Current Liability','credit',0,'Employer + withheld payroll taxes owed'),
('2400','Accrued Expenses','other_current_liability','Current Liability','credit',0,'Incurred, not yet billed'),
('2500','Customer Deposits / Unearned Revenue','other_current_liability','Current Liability','credit',0,'Online orders paid before shipment'),
('2600','Notes Payable','long_term_liability','Long-Term Liability','credit',0,'Equipment loans, term debt'),

-- ============================== EQUITY 3000–3499 (C-corp)
('3000','Common Stock','equity','Equity','credit',0,'Par value of issued shares'),
('3100','Additional Paid-In Capital','equity','Equity','credit',0,'Amount paid above par'),
('3200','Retained Earnings','equity','Equity','credit',0,'Accumulated profits/losses'),
('3300','Dividends Declared','equity','Equity','debit',0,'Distributions to shareholders — NOT an expense'),

-- ============================== REVENUE 4000–4499
('4000','Paint Sales — Wholesale/Distributor','income','Income','credit',0,'Revenue from sales-order/invoice customers'),
('4100','Paint Sales — Online/Direct','income','Income','credit',0,'Revenue from the online store'),
('4200','Sales Returns & Allowances','income','Contra-Revenue','debit',0,'Returned/damaged goods, price adjustments'),
('4300','Sales Discounts','income','Contra-Revenue','debit',0,'Early-pay or volume discounts taken'),
('4900','Other Income','other_income','Income','credit',0,'Interest, misc non-operating income'),

-- ============================== COGS 5000–5599
('5000','COGS — Raw Materials','cost_of_goods_sold','COGS','debit',0,'Material cost of units shipped'),
('5100','COGS — Direct Labor','cost_of_goods_sold','COGS','debit',0,'Production labor cost of units shipped'),
('5200','COGS — Manufacturing Overhead','cost_of_goods_sold','COGS','debit',0,'Applied overhead on units shipped'),
('5300','Freight In','cost_of_goods_sold','COGS','debit',0,'Inbound shipping on raw material purchases'),
('5500','Inventory Shrinkage / Write-offs','cost_of_goods_sold','COGS','debit',0,'Spoilage, damaged batches, obsolete inventory'),

-- ============================== OPERATING EXPENSES 6000–6999
('6000','Advertising & Marketing','expense','Expense','debit',0,'Ad spend, marketing tools'),
('6100','Software & Subscriptions','expense','Expense','debit',0,'SaaS tools, hosting, infrastructure'),
('6150','Merchant / Payment Processing Fees','expense','Expense','debit',0,'Processor fees — posted gross, never netted against revenue'),
('6200','Office Supplies','expense','Expense','debit',0,'General supplies'),
('6300','Professional Services','expense','Expense','debit',0,'Legal, accounting, audit'),
('6400','Insurance','expense','Expense','debit',0,'General and product liability — its own line, material for a paint manufacturer'),
('6500','Salaries & Wages — Admin/Sales','expense','Expense','debit',0,'Non-production payroll'),
('6505','Direct Labor — Production','expense','Expense','debit',0,'Production-floor payroll before any portion is capitalised into WIP'),
('6510','Payroll Taxes — Employer','expense','Expense','debit',0,'Employer-side payroll tax'),
('6600','Rent — Facility/Warehouse','expense','Expense','debit',0,'Warehouse/plant lease'),
('6700','Utilities','expense','Expense','debit',0,'Electric, gas, water — non-production-allocated portion'),
('6800','Repairs & Maintenance','expense','Expense','debit',0,'Non-capitalised equipment upkeep'),
('6900','Bank Fees','expense','Expense','debit',0,'Account fees, wire fees'),
('6950','Depreciation Expense','expense','Expense','debit',0,'Non-production asset depreciation'),
('6960','Bad Debt Expense','expense','Expense','debit',0,'Written-off receivables')

ON DUPLICATE KEY UPDATE
    name            = VALUES(name),
    account_type    = VALUES(account_type),
    account_subtype = VALUES(account_subtype),
    normal_balance  = VALUES(normal_balance),
    is_bank_account = VALUES(is_bank_account),
    description     = VALUES(description);
