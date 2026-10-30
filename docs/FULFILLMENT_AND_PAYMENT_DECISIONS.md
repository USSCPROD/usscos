# Fulfillment and payment decisions

Recorded October 30, 2026 from Chip Curle's instructions in the US Specialty Coatings task.

## Current implementation scope

Update sales orders, shipping/fulfillment, and invoicing. Do not implement Square or change QuickBooks synchronization in this work.

- Support carrier shipping, customer pickup (CPU), and USSC delivery.
- One sales order may have multiple fulfillments with different methods and item quantities, including part picked up and the remainder delivered later.
- Keep outstanding quantities visible. Ready for pickup is not picked up; out for delivery is not delivered; a label is not proof of shipment.
- COD customers are billed by fulfilled portion. For outside-carrier shipping, payment is collected before shipment. USSC delivery may collect a check at handoff. Record physical check receipt separately from bank clearance, with amount, check number, collector, collection time, and the associated invoice. Do not require pre-departure payment for an approved collect-at-delivery fulfillment. Carrier shipments still require payment before release.
- Customers on terms are normally billed when the entire order is fulfilled. Exceptions require an explicit billing decision.
- Separate fulfillment completion, invoice approval, and payment completion. Prevent duplicate billing and duplicate application of payments.
- CPU and USSC delivery must not require carrier tracking or send misleading carrier tracking notifications.

## Deferred payment and accounting decision

Current process uses QuickBooks Payments, with payments applied to invoices and a customer online-payment link.

Future intended processor: Square. Desired behavior: both staff-taken and customer online payments apply to the correct USSCOS invoice, with verified completed-payment status, duplicate-event protection, and reconciliation of missed events. Do not treat authorization as completed payment.

QuickBooks retention is UNDECIDED. Chip prefers to leave QuickBooks; his boss wants records sent to QuickBooks for the accountant. Do not remove QuickBooks or build/enable a replacement synchronization without an agreed accounting plan.

Open decisions for the boss/accountant:
- Whether QuickBooks remains the official ledger.
- Individual invoices/payments versus summary accounting synchronization.
- Handling of fees, refunds, settlements, disputes, and corrections.

Preserve the option for USSCOS operations plus Square payments plus optional QuickBooks accounting. No duplicate invoices, customer emails, or collections. This note records intent, not an implemented integration.

## Implementation audit, October 30

Repository: /Users/artdeptmac/Projects/BusinessOS. This is the internal USSCOS application, separate from the SpecialtyCoatings public website.

Current code findings:
- InvoiceService::shipAndInvoiceWithin creates an invoice, deducts inventory, increments sales order line qty_shipped, queues invoice review, and changes the order status in one operation.
- Partial shipments already invoice the newly picked quantity. This must change for terms customers who wait until full fulfillment before billing.
- InvoiceService::storeWithin also moves stock. It cannot be reused unchanged to create a prepaid COD invoice, or to bill goods whose stock was already deducted during fulfillment.
- InvoiceService::approve marks review complete and invokes ShipmentNotificationService. It does not send the invoice document. InvoiceController::email is a separate invoice email action.
- Carrier labels currently depend on an invoice and the latest invoice's line quantities. This dependency must be replaced or explicitly supported when carrier fulfillment precedes a terms invoice.
- Existing documentation describes a QuickBooks Desktop/QODBC export bridge. Preserve that implementation while future accounting direction is undecided. Do not disable existing synchronization based on the deferred proposal.

Required implementation boundaries:
1. Persistent fulfillment records and item quantities per sales order, supporting all three methods and multiple fulfillments.
2. Separate preparation, dispatch/handoff, completion, billing, and payment states.
3. Quantity allocation and transactional guards against overfulfillment, duplicate inventory movement, duplicate invoicing, and concurrent double submission.
4. COD invoices before the required payment checkpoint, without moving inventory merely because an invoice was created.
5. Terms invoices from completed, unbilled fulfillments after order completion, with an authorized documented partial-billing exception.
6. Label/packing records independent of invoice timing and linked to the correct fulfillment.
7. Method-aware review and customer notifications, with invoice delivery distinguished from tracking notification.
8. Preserve old invoices, payments, inventory transactions, QuickBooks references, and existing fulfillment history. No retroactive rewriting of production history.
9. Exercise mixed-method partial orders, COD payment holds, terms final billing, repeated submissions, failed deliveries, invoice edits, and stock accounting in isolated tests before rollout.

Resolved business clarification: Chip confirmed that a USSC driver sometimes receives a check on delivery. Support collection at handoff for USSC Delivery COD. Check received does not mean cleared by the bank. The carrier pre-shipment collection rule remains unchanged.

Status: implementation and isolated regression tests completed. Authorized for an inactive server deployment on October 30. Migration 111 and workflow activation remain deferred; existing production processing continues unchanged.


## Local implementation, October 30

The implementation is deliberately disabled by default with `FULFILLMENT_V2_ENABLED=false`. Migration 111 is additive for order, fulfillment, item, event, and driver-check records and extends the stock transaction reference type. It does not rewrite historical orders or invoices. No production migration has been run.

### Staff workflow

1. Sales creates the sales order as usual, with customer payment terms and delivery address confirmed. Open Shipping & Fulfillment from the saved order.
2. Confirm COD or terms on first allocation. Allocate specific line quantities to Carrier Shipping, Customer Pickup, or USSC Delivery. A single order may have multiple allocations and methods.
3. Warehouse uses the existing pull list. A fulfillment cannot become Ready until sufficient picked quantities are recorded. Ready does not deduct stock or mark goods fulfilled.
4. Billing prepares and approves the invoice for each ready COD portion. Carrier and pickup completion require the invoice to be approved and fully paid in USSCOS. USSC Delivery may leave after invoice approval with a balance outstanding.
5. Shipping records carrier handoff and tracking, customer pickup and collector, or USSC delivery departure and final recipient. A failed delivery returns to Ready without completing quantities. Completing the handoff moves stock once and updates the outstanding order quantities.
6. A driver may record a check against the approved delivery invoice while out for delivery. The exact outstanding balance and check number are required. Physical receipt permits COD delivery completion, but does not itself post a payment or assert bank clearance.
7. Accounting confirms physical check handoff and posts the payment to that invoice once. A changed invoice balance blocks automatic posting so staff must reconcile any payment already recorded elsewhere. Bank deposit, clearance, and bounced-check handling remain separate accounting work.
8. Terms invoices normally include all completed, unbilled portions after full fulfillment. An owner or bookkeeper may enter a reason to bill completed portions early. Previously billed portions are excluded from subsequent invoices.
9. Review the invoice, including handling/freight, then approve. Use the existing invoice Email action to send it. New fulfillment invoice approval never claims to send a tracking email. Email is blocked until review approval. Charge edits invalidate approval and do not move stock again.

### Safeguards and access

- Old ship-and-invoice, direct SO invoicing, SO prepayment, and destructive order-line editing are blocked once an order is using the new fulfillment workflow.
- Existing orders with invoice, payment, or shipment history cannot be silently adopted. They retain the legacy workflow pending a separately reviewed reconciliation.
- Unbilled planned/ready allocations may be canceled, leaving their quantities outstanding and available for replanning. Canceling an allocation does not cancel the customer's order quantities.
- Invoice quantities and product identities are protected. Unpaid invoices permit price/charge review and additional unlinked handling/freight lines. They must be reapproved afterwards.
- Allocation requests have unique submission keys. Fulfillment writes serialize on the sales order. Invoice balance checks use row locks. Repeated completion and check posting do not repeat stock or payments.
- New operational screens are limited to owner, admin, bookkeeper, manager, shipping, and employee roles. Invoice creation, partial-billing exceptions, and check posting are restricted to owner/bookkeeper. Existing warehouse-only users continue to use the existing pull/pick screens. External rep/distributor and readonly roles do not receive the new operational screen.
- No Square code, QuickBooks export changes, or automated customer messages were added.

### Validation completed

- `FULFILLMENT_TEST_DB=ussc_fulfillment_test_20261030_codex composer test:fulfillment`: 17 tests passed against a disposable local MySQL database.
- Tests cover mixed pickup/delivery, terms consolidation and exceptions, COD holds, driver checks, repeated requests, invalid quantities and foreign-order lines, readiness, failed deliveries, allocation cancellation, historical-order refusal, and transaction rollback.
- The integration case uses the actual InvoiceService, StockService, PaymentRepository and controller service wiring. It verifies pre-delivery billing without stock movement, invoice freight editing without stock movement, review approval, one stock deduction at completion, one check payment application, and factual completion date.
- SQLite runs the workflow tests; the real-service integration case is MySQL-only. This repository did not have a `composer test` command. Added `composer test:fulfillment` without replacing unrelated scripts.
- PHP syntax and `git diff --check` passed. These checks do not replace browser acceptance testing or production verification.

### Production activation restrictions and remaining acceptance work

Do not enable this on the production server without a reviewed schema backup/migration plan and staff acceptance testing.

The current FedEx label action depends on an invoice. It is blocked for new managed orders so a terms order is not incorrectly invoiced merely to create a label. The new screen currently supports labels created in the carrier's own system with tracking/PRO recorded on the fulfillment. Preserving integrated FedEx label creation for managed fulfillments still requires a dedicated adaptation and sandbox test. This is a rollout limitation, not a completed label integration.

Historical in-flight orders, prepaid sales orders, and invoice corrections involving changed physical quantities require reconciliation. No historic records have been converted. Full cancellation of remaining order quantities, reassignment of invoiced fulfillment quantities, check reversals, and a bank-clearance workflow are not introduced by this implementation.

Customer pickup versus delivery tax sourcing must be reviewed with the accountant before rollout. The current invoice service retains the existing order-address tax logic; this package does not invent new tax policy or calculate multiple jurisdictions on one invoice.

Pending browser acceptance should cover the actual sales, shipping, and bookkeeper accounts, mixed-method packing documents, invoice emailing with test recipients, and the carrier label workflow. The feature remains off until those gaps are resolved or an explicitly limited pilot is approved.


## Authorized inactive deployment

Chip authorized server deployment on October 30. The deployment keeps `FULFILLMENT_V2_ENABLED=false` and does not run migration 111. New workflow routes remain unavailable and navigation is hidden. Existing dependency versions, production environment, uploads, and business records are preserved. Only code and autoload metadata change. The production backup records the prior revision and verification results.

Activation is a separate step after the remaining carrier-label, tax-policy, and staff acceptance issues above are resolved. Deployment must not be reported as the new fulfillment workflow being live.
