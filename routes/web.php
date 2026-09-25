<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AdminController;
use App\Controllers\AccountingController;
use App\Controllers\QbSyncController;
use App\Controllers\CategoryController;
use App\Controllers\TaskController;
use App\Controllers\DocumentController;
use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\InvoiceController;
use App\Controllers\LeadController;
use App\Controllers\OpportunityController;
use App\Controllers\QuoteController;
use App\Controllers\PaymentController;
use App\Controllers\ProductController;
use App\Controllers\JobBinderController;
use App\Controllers\ProfileController;
use App\Controllers\InventoryController;
use App\Controllers\AdjustmentController;
use App\Controllers\TransferController;
use App\Controllers\ReturnController;
use App\Controllers\CountController;
use App\Controllers\PurchaseOrderController;
use App\Controllers\SalesOrderController;
use App\Controllers\WebhookController;
use App\Controllers\ShippingController;
use App\Controllers\ReceivingController;
use App\Controllers\VendorController;

// -------------------------------------------------------------------------
// Guest routes (unauthenticated only)
// -------------------------------------------------------------------------

Router::group(['middleware' => ['guest', 'csrf']], function () {
    Router::get('/login',              [AuthController::class, 'showLogin'])->name('login');
    Router::post('/login',             [AuthController::class, 'login'])->name('login.post');
    Router::get('/forgot-password',    [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Router::post('/forgot-password',   [AuthController::class, 'sendResetLink'])->name('password.forgot.post');
    Router::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Router::post('/reset-password',    [AuthController::class, 'resetPassword'])->name('password.reset.post');
});

// -------------------------------------------------------------------------
// Authenticated routes
// -------------------------------------------------------------------------

Router::group(['middleware' => ['auth', 'internal', 'csrf']], function () {

    // Root redirect
    Router::get('/', [DashboardController::class, 'index'])->name('home');

    // Dashboard
    Router::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Leads
    Router::get('/leads',                  [LeadController::class, 'index'])->name('leads');
    Router::get('/leads/create',           [LeadController::class, 'create'])->name('leads.create');
    Router::post('/leads',                 [LeadController::class, 'store'])->name('leads.store');
    Router::get('/leads/{id}',             [LeadController::class, 'show'])->name('leads.show');
    Router::get('/leads/{id}/edit',        [LeadController::class, 'edit'])->name('leads.edit');
    Router::post('/leads/{id}/edit',       [LeadController::class, 'update'])->name('leads.update');
    Router::post('/leads/{id}/convert',    [LeadController::class, 'convert'])->name('leads.convert');

    // Pipeline / Opportunities
    // Quotes
    Router::get('/quotes',                    [QuoteController::class, 'index'])->name('quotes');
    Router::get('/quotes/create',             [QuoteController::class, 'create'])->name('quotes.create');
    Router::post('/quotes',                   [QuoteController::class, 'store'])->name('quotes.store');
    Router::get('/quotes/{id}',               [QuoteController::class, 'show'])->name('quotes.show');
    Router::get('/quotes/{id}/edit',          [QuoteController::class, 'edit'])->name('quotes.edit');
    Router::post('/quotes/{id}/edit',         [QuoteController::class, 'update'])->name('quotes.update');
    Router::post('/quotes/{id}/status',          [QuoteController::class, 'updateStatus'])->name('quotes.status');
    Router::post('/quotes/{id}/link-opportunity',[QuoteController::class, 'linkOpportunity'])->name('quotes.link_opp');
    Router::post('/quotes/{id}/convert',      [QuoteController::class, 'convertToSO'])->name('quotes.convert');
    Router::post('/quotes/{id}/email',        [QuoteController::class, 'email'])->name('quotes.email');

    Router::get('/pipeline',               [OpportunityController::class, 'index'])->name('pipeline');
    Router::get('/opportunities',          [OpportunityController::class, 'index'])->name('opportunities.index');
    Router::get('/opportunities/create',   [OpportunityController::class, 'create'])->name('opportunities.create');
    Router::post('/opportunities',         [OpportunityController::class, 'store'])->name('opportunities.store');
    Router::get('/opportunities/{id}',           [OpportunityController::class, 'show'])->name('opportunities.show');
    Router::get('/opportunities/{id}/edit',      [OpportunityController::class, 'edit'])->name('opportunities.edit');
    Router::post('/opportunities/{id}/edit',     [OpportunityController::class, 'update'])->name('opportunities.update');
    Router::post('/opportunities/{id}/stage',    [OpportunityController::class, 'updateStage'])->name('opportunities.stage');
    Router::get('/customers/{id}/opportunities', [OpportunityController::class, 'byCustomer'])->name('customers.opportunities');

    // Tasks
    Router::get('/tasks',                [TaskController::class, 'index'])->name('tasks');
    Router::get('/tasks/create',         [TaskController::class, 'create'])->name('tasks.create');
    Router::post('/tasks',               [TaskController::class, 'store'])->name('tasks.store');
    Router::get('/tasks/{id}/edit',      [TaskController::class, 'edit'])->name('tasks.edit');
    Router::post('/tasks/{id}/edit',     [TaskController::class, 'update'])->name('tasks.update');
    Router::post('/tasks/{id}/status',   [TaskController::class, 'updateStatus'])->name('tasks.status');

    // Customers
    // Payments
    Router::get('/payments/{id}/edit',      [PaymentController::class,  'edit'])->name('payments.edit');
    Router::post('/payments/{id}/edit',     [PaymentController::class,  'update'])->name('payments.update');

    // Customer payments
    Router::get('/customers/{id}/payment',  [PaymentController::class,  'create'])->name('customers.payment');
    Router::post('/customers/{id}/payment', [PaymentController::class,  'store'])->name('customers.payment.store');

    Router::get('/customers',                [CustomerController::class, 'index'])->name('customers');
    Router::get('/customers/create',         [CustomerController::class, 'create'])->name('customers.create');
    Router::post('/customers',               [CustomerController::class, 'store'])->name('customers.store');
    Router::get('/customers/autocomplete',   [CustomerController::class, 'autocomplete'])->name('customers.autocomplete');
    Router::get('/customers/{id}/json',      [CustomerController::class, 'apiShow'])->name('customers.json');
    Router::get('/customers/{id}',           [CustomerController::class, 'show'])->name('customers.show');
    Router::get('/customers/{id}/edit',      [CustomerController::class, 'edit'])->name('customers.edit');
    Router::post('/customers/{id}/edit',     [CustomerController::class, 'update'])->name('customers.update');
    Router::post('/customers/{id}/note',     [CustomerController::class, 'storeNote'])->name('customers.note');

    // Products
    // Own account — every signed-in user, whatever their role
    Router::get('/settings/profile',           [ProfileController::class, 'show'])->name('profile');
    Router::post('/settings/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Accounting
    Router::get('/accounting',                [AccountingController::class, 'index'])->name('accounting');
    Router::get('/accounting/accounts',       [AccountingController::class, 'accounts'])->name('accounting.accounts');
    Router::get('/accounting/ar-aging',       [AccountingController::class, 'arAging'])->name('accounting.ar_aging');
    Router::get('/accounting/tax',            [AccountingController::class, 'tax'])->name('accounting.tax');
    Router::get('/accounting/reps',           [AccountingController::class, 'reps'])->name('accounting.reps');
    Router::get('/accounting/employees',      [AccountingController::class, 'employees'])->name('accounting.employees');

    // Categories — literal routes must precede /categories/{id}
    Router::get('/categories',                [CategoryController::class, 'index'])->name('categories');
    Router::get('/categories/create',         [CategoryController::class, 'create'])->name('categories.create');
    Router::post('/categories',               [CategoryController::class, 'store'])->name('categories.store');
    Router::post('/categories/bulk-assign',   [CategoryController::class, 'bulkAssign'])->name('categories.bulk_assign');
    Router::get('/categories/{id}/edit',      [CategoryController::class, 'edit'])->name('categories.edit');
    Router::post('/categories/{id}/edit',     [CategoryController::class, 'update'])->name('categories.update');
    Router::post('/categories/{id}/delete',   [CategoryController::class, 'destroy'])->name('categories.delete');

    Router::get('/products',                  [ProductController::class, 'index'])->name('products');
    Router::get('/products/autocomplete',     [ProductController::class, 'autocomplete'])->name('products.autocomplete');
    Router::get('/products/autocomplete-all', [ProductController::class, 'autocompleteAll'])->name('products.autocomplete_all');
    Router::get('/products/{id}',             [ProductController::class, 'show'])->name('products.show');
    Router::get('/products/{id}/edit',        [ProductController::class, 'edit'])->name('products.edit');
    Router::post('/products/{id}/edit',       [ProductController::class, 'update'])->name('products.update');

    // Product images
    Router::post('/products/{id}/images',                       [ProductController::class, 'uploadImage'])->name('products.images.upload');
    Router::post('/products/{id}/images/{imageId}/delete',      [ProductController::class, 'deleteImage'])->name('products.images.delete');
    Router::post('/products/{id}/images/{imageId}/primary',     [ProductController::class, 'setPrimaryImage'])->name('products.images.primary');

    // Product documents (SDS, TDS, flyers, catalogs...)
    Router::post('/products/{id}/documents',                    [ProductController::class, 'uploadDocument'])->name('products.documents.upload');
    Router::post('/products/{id}/documents/{docId}/delete',     [ProductController::class, 'deleteDocument'])->name('products.documents.delete');

    // Raw Materials
    Router::get('/raw-materials',                [ProductController::class, 'rawMaterials'])->name('raw_materials');
    Router::get('/raw-materials/create',         [ProductController::class, 'rawMaterialsCreate'])->name('raw_materials.create');
    Router::post('/raw-materials',               [ProductController::class, 'rawMaterialsStore'])->name('raw_materials.store');
    Router::get('/raw-materials/{id}/edit',      [ProductController::class, 'rawMaterialsEdit'])->name('raw_materials.edit');
    Router::post('/raw-materials/{id}/edit',     [ProductController::class, 'rawMaterialsUpdate'])->name('raw_materials.update');

    // Shipping
    // Digital Job Binder — artwork, hung off the sales order
    Router::post('/sales-orders/{id}/documents',                      [JobBinderController::class, 'storeDocument'])->name('binder.document.store');
    Router::post('/sales-orders/{id}/documents/{documentId}/remove',  [JobBinderController::class, 'removeDocument'])->name('binder.document.remove');
    Router::post('/sales-orders/{id}/artwork',                        [JobBinderController::class, 'storeArtwork'])->name('binder.artwork.store');
    Router::post('/sales-orders/{id}/artwork/{artworkId}/revision',   [JobBinderController::class, 'storeRevision'])->name('binder.artwork.revision');
    Router::post('/sales-orders/{id}/artwork/{artworkId}/remove',     [JobBinderController::class, 'removeArtwork'])->name('binder.artwork.remove');
    Router::post('/sales-orders/{id}/revision/{revisionId}/decision', [JobBinderController::class, 'decide'])->name('binder.artwork.decision');

    Router::get('/shipping', [ShippingController::class, 'index'])->name('shipping');
    Router::get('/shipping/{id}/pick',       [ShippingController::class, 'pick'])->name('shipping.pick');
    Router::post('/shipping/{id}/pick/scan', [ShippingController::class, 'scan'])->name('shipping.scan');
    Router::post('/shipping/{id}/pick/line', [ShippingController::class, 'setLine'])->name('shipping.pick.line');
    Router::get('/shipping/{id}/pack',        [ShippingController::class, 'pack'])->name('shipping.pack');
    Router::post('/shipping/{id}/pack/scan',  [ShippingController::class, 'packScan'])->name('shipping.pack.scan');
    Router::post('/shipping/{id}/pack/line',  [ShippingController::class, 'packSetLine'])->name('shipping.pack.line');
    Router::post('/shipping/{id}/pack/mismatch',[ShippingController::class, 'packMismatch'])->name('shipping.pack.mismatch');
    Router::post('/shipping/{id}/short',     [ShippingController::class, 'short'])->name('shipping.short');

    // Receiving — where stock first enters USSCOS
    Router::get('/receiving',         [ReceivingController::class, 'index'])->name('receiving');
    Router::post('/receiving/lookup', [ReceivingController::class, 'lookup'])->name('receiving.lookup');
    Router::post('/receiving',        [ReceivingController::class, 'store'])->name('receiving.store');

    // Sales Orders
    Router::get('/sales-orders',                 [SalesOrderController::class, 'index'])->name('sales_orders');
    Router::get('/sales-orders/create',          [SalesOrderController::class, 'create'])->name('sales_orders.create');
    Router::post('/sales-orders',                [SalesOrderController::class, 'store'])->name('sales_orders.store');
    Router::get('/sales-orders/{id}',            [SalesOrderController::class, 'show'])->name('sales_orders.show');
    Router::get('/sales-orders/{id}/edit',       [SalesOrderController::class, 'edit'])->name('sales_orders.edit');
    Router::post('/sales-orders/{id}/edit',      [SalesOrderController::class, 'update'])->name('sales_orders.update');
    Router::post('/sales-orders/{id}/delete',    [SalesOrderController::class, 'destroy'])->name('sales_orders.delete');
    Router::get('/sales-orders/{id}/print',      [SalesOrderController::class, 'printView'])->name('sales_orders.print');
    Router::get('/sales-orders/{id}/packing-slip', [SalesOrderController::class, 'packingSlip'])->name('sales_orders.packing_slip');
    Router::post('/sales-orders/{id}/email',     [SalesOrderController::class, 'email'])->name('sales_orders.email');
    Router::post('/sales-orders/{id}/payment',   [SalesOrderController::class, 'collectPayment'])->name('sales_orders.payment');
    Router::post('/sales-orders/{id}/ship',      [SalesOrderController::class, 'ship'])->name('sales_orders.ship');

    // Invoices
    Router::get('/invoices',             [InvoiceController::class, 'index'])->name('invoices');
    Router::get('/invoices/create',      [InvoiceController::class, 'create'])->name('invoices.create');
    Router::post('/invoices',            [InvoiceController::class, 'store'])->name('invoices.store');
    Router::get('/invoices/{id}',        [InvoiceController::class, 'show'])->name('invoices.show');
    Router::get('/invoices/{id}/edit',    [InvoiceController::class,  'edit'])->name('invoices.edit');
    Router::post('/invoices/{id}/edit',   [InvoiceController::class,  'update'])->name('invoices.update');
    Router::get('/invoices/{id}/print',        [InvoiceController::class, 'printView'])->name('invoices.print');
    Router::get('/invoices/{id}/packing-slip', [InvoiceController::class, 'packingSlip'])->name('invoices.packing_slip');
    Router::post('/invoices/{id}/email',       [InvoiceController::class, 'email'])->name('invoices.email');

    // Vendors
    Router::get('/vendors',                [VendorController::class, 'index'])->name('vendors');
    Router::get('/vendors/create',         [VendorController::class, 'create'])->name('vendors.create');
    Router::post('/vendors',               [VendorController::class, 'store'])->name('vendors.store');
    Router::get('/vendors/{id}/edit',      [VendorController::class, 'edit'])->name('vendors.edit');
    Router::post('/vendors/{id}/edit',     [VendorController::class, 'update'])->name('vendors.update');

    // Purchase Orders
    Router::get('/purchasing',                       [PurchaseOrderController::class, 'index'])->name('purchasing');
    Router::get('/purchasing/create',                [PurchaseOrderController::class, 'create'])->name('purchasing.create');
    Router::post('/purchasing',                      [PurchaseOrderController::class, 'store'])->name('purchasing.store');
    Router::get('/purchasing/variances',             [PurchaseOrderController::class, 'variances'])->name('purchasing.variances');
    Router::get('/purchasing/{id}',                  [PurchaseOrderController::class, 'show'])->name('purchasing.show');
    Router::get('/purchasing/{id}/edit',             [PurchaseOrderController::class, 'edit'])->name('purchasing.edit');
    Router::post('/purchasing/{id}/edit',            [PurchaseOrderController::class, 'update'])->name('purchasing.update');
    Router::post('/purchasing/variance/{lineId}',    [PurchaseOrderController::class, 'resolveVariance'])->name('purchasing.variance.resolve');
    Router::post('/purchasing/{id}/receive',         [PurchaseOrderController::class, 'receive'])->name('purchasing.receive');
    Router::post('/purchasing/{id}/status',          [PurchaseOrderController::class, 'updateStatus'])->name('purchasing.status');
    Router::get('/purchasing/{id}/print',            [PurchaseOrderController::class, 'printView'])->name('purchasing.print');
    Router::post('/purchasing/{id}/email',           [PurchaseOrderController::class, 'email'])->name('purchasing.email');

    // Inventory
    Router::get('/inventory',                    [InventoryController::class, 'index'])->name('inventory');
    // Literal paths before /inventory/{id}, or the id route swallows them.
    Router::get('/inventory/adjustments',        [AdjustmentController::class, 'index'])->name('inventory.adjustments');
    Router::post('/inventory/adjustments',       [AdjustmentController::class, 'store'])->name('inventory.adjustments.store');
    Router::post('/inventory/adjustments/lookup',[AdjustmentController::class, 'lookup'])->name('inventory.adjustments.lookup');
    Router::get('/inventory/transfers',           [TransferController::class, 'index'])->name('inventory.transfers');
    Router::post('/inventory/transfers',          [TransferController::class, 'store'])->name('inventory.transfers.store');
    Router::get('/inventory/transfers/{id}',      [TransferController::class, 'show'])->name('inventory.transfers.show');
    Router::post('/inventory/transfers/{id}/scan',[TransferController::class, 'scan'])->name('inventory.transfers.scan');
    Router::post('/inventory/transfers/{id}/send',[TransferController::class, 'send'])->name('inventory.transfers.send');
    Router::post('/inventory/transfers/{id}/close',[TransferController::class, 'close'])->name('inventory.transfers.close');
    Router::post('/inventory/transfers/{id}/cancel',[TransferController::class, 'cancel'])->name('inventory.transfers.cancel');
    Router::post('/inventory/transfers/{id}/line/{lineId}/remove',[TransferController::class, 'removeLine'])->name('inventory.transfers.line.remove');
    Router::get('/inventory/returns',             [ReturnController::class, 'index'])->name('inventory.returns');
    Router::post('/inventory/returns',            [ReturnController::class, 'store'])->name('inventory.returns.store');
    Router::get('/inventory/returns/{id}',        [ReturnController::class, 'show'])->name('inventory.returns.show');
    Router::post('/inventory/returns/{id}/line',  [ReturnController::class, 'addLine'])->name('inventory.returns.line');
    Router::post('/inventory/returns/{id}/receive',[ReturnController::class, 'receive'])->name('inventory.returns.receive');
    Router::post('/inventory/returns/{id}/credited',[ReturnController::class, 'creditIssued'])->name('inventory.returns.credited');
    Router::post('/inventory/returns/{id}/cancel',[ReturnController::class, 'cancel'])->name('inventory.returns.cancel');
    Router::post('/inventory/returns/{id}/line/{lineId}/remove',[ReturnController::class, 'removeLine'])->name('inventory.returns.line.remove');
    Router::get('/inventory/counts',              [CountController::class, 'index'])->name('inventory.counts');
    Router::post('/inventory/counts',             [CountController::class, 'store'])->name('inventory.counts.store');
    Router::get('/inventory/counts/{id}',         [CountController::class, 'show'])->name('inventory.counts.show');
    Router::post('/inventory/counts/{id}/record', [CountController::class, 'record'])->name('inventory.counts.record');
    Router::post('/inventory/counts/{id}/submit', [CountController::class, 'submit'])->name('inventory.counts.submit');
    Router::post('/inventory/counts/{id}/reopen', [CountController::class, 'reopen'])->name('inventory.counts.reopen');
    Router::post('/inventory/counts/{id}/apply',  [CountController::class, 'apply'])->name('inventory.counts.apply');
    Router::post('/inventory/counts/{id}/cancel', [CountController::class, 'cancel'])->name('inventory.counts.cancel');
    Router::get('/inventory/{id}',               [InventoryController::class, 'show'])->name('inventory.show');
    Router::post('/inventory/{id}/adjust',       [InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Documents
    Router::get('/documents',                        [DocumentController::class, 'index'])->name('documents');
    Router::post('/documents',                       [DocumentController::class, 'store'])->name('documents.store');
    Router::get('/documents/{id}/download',          [DocumentController::class, 'download'])->name('documents.download');
    Router::get('/documents/{id}/edit',              [DocumentController::class, 'edit'])->name('documents.edit');
    Router::post('/documents/{id}/edit',             [DocumentController::class, 'update'])->name('documents.update');
    Router::post('/documents/{id}/delete',           [DocumentController::class, 'destroy'])->name('documents.delete');

    // Admin
    Router::get('/admin',                                    [AdminController::class, 'index'])->name('admin');
    Router::get('/admin/sales-reps',                         [AdminController::class, 'salesReps'])->name('admin.sales_reps');
    Router::get('/admin/sales-reps/create',                  [AdminController::class, 'salesRepsCreate'])->name('admin.sales_reps.create');
    Router::post('/admin/sales-reps',                        [AdminController::class, 'salesRepsStore'])->name('admin.sales_reps.store');
    Router::get('/admin/sales-reps/{id}/edit',               [AdminController::class, 'salesRepsEdit'])->name('admin.sales_reps.edit');
    Router::post('/admin/sales-reps/{id}/edit',              [AdminController::class, 'salesRepsUpdate'])->name('admin.sales_reps.update');

    Router::get('/admin/locations',                          [AdminController::class, 'locations'])->name('admin.locations');
    Router::get('/admin/locations/create',                   [AdminController::class, 'locationsCreate'])->name('admin.locations.create');
    Router::post('/admin/locations',                         [AdminController::class, 'locationsStore'])->name('admin.locations.store');
    Router::get('/admin/locations/{id}/edit',                [AdminController::class, 'locationsEdit'])->name('admin.locations.edit');
    Router::post('/admin/locations/{id}/edit',               [AdminController::class, 'locationsUpdate'])->name('admin.locations.update');

    Router::get('/admin/ship-via',                           [AdminController::class, 'shipVia'])->name('admin.ship_via');
    Router::get('/admin/ship-via/create',                    [AdminController::class, 'shipViaCreate'])->name('admin.ship_via.create');
    Router::post('/admin/ship-via',                          [AdminController::class, 'shipViaStore'])->name('admin.ship_via.store');
    Router::get('/admin/ship-via/{id}/edit',                 [AdminController::class, 'shipViaEdit'])->name('admin.ship_via.edit');
    Router::post('/admin/ship-via/{id}/edit',                [AdminController::class, 'shipViaUpdate'])->name('admin.ship_via.update');

    Router::get('/admin/payment-terms',                      [AdminController::class, 'paymentTerms'])->name('admin.payment_terms');
    Router::get('/admin/payment-terms/create',               [AdminController::class, 'paymentTermsCreate'])->name('admin.payment_terms.create');
    Router::post('/admin/payment-terms',                     [AdminController::class, 'paymentTermsStore'])->name('admin.payment_terms.store');
    Router::get('/admin/payment-terms/{id}/edit',            [AdminController::class, 'paymentTermsEdit'])->name('admin.payment_terms.edit');
    Router::post('/admin/payment-terms/{id}/edit',           [AdminController::class, 'paymentTermsUpdate'])->name('admin.payment_terms.update');

    Router::get('/admin/tax-rates',                          [AdminController::class, 'taxRates'])->name('admin.tax_rates');
    Router::get('/admin/tax-rates/create',                   [AdminController::class, 'taxRatesCreate'])->name('admin.tax_rates.create');
    Router::post('/admin/tax-rates',                         [AdminController::class, 'taxRatesStore'])->name('admin.tax_rates.store');
    Router::get('/admin/tax-rates/{id}/edit',                [AdminController::class, 'taxRatesEdit'])->name('admin.tax_rates.edit');
    Router::post('/admin/tax-rates/{id}/edit',               [AdminController::class, 'taxRatesUpdate'])->name('admin.tax_rates.update');

    Router::get('/admin/customer-messages',                  [AdminController::class, 'customerMessages'])->name('admin.customer_messages');
    Router::get('/admin/customer-messages/create',           [AdminController::class, 'customerMessagesCreate'])->name('admin.customer_messages.create');
    Router::post('/admin/customer-messages',                 [AdminController::class, 'customerMessagesStore'])->name('admin.customer_messages.store');
    Router::get('/admin/customer-messages/{id}/edit',        [AdminController::class, 'customerMessagesEdit'])->name('admin.customer_messages.edit');
    Router::post('/admin/customer-messages/{id}/edit',       [AdminController::class, 'customerMessagesUpdate'])->name('admin.customer_messages.update');

    Router::get('/admin/users',                              [AdminController::class, 'users'])->name('admin.users');
    Router::get('/admin/users/create',                       [AdminController::class, 'usersCreate'])->name('admin.users.create');
    Router::post('/admin/users',                             [AdminController::class, 'usersStore'])->name('admin.users.store');
    Router::get('/admin/users/{id}/edit',                    [AdminController::class, 'usersEdit'])->name('admin.users.edit');
    Router::post('/admin/users/{id}/edit',                   [AdminController::class, 'usersUpdate'])->name('admin.users.update');

    Router::get('/admin/departments',                        [AdminController::class, 'departments'])->name('admin.departments');
    Router::get('/admin/departments/create',                 [AdminController::class, 'departmentsCreate'])->name('admin.departments.create');
    Router::post('/admin/departments',                       [AdminController::class, 'departmentsStore'])->name('admin.departments.store');
    Router::get('/admin/departments/{id}/edit',              [AdminController::class, 'departmentsEdit'])->name('admin.departments.edit');
    Router::post('/admin/departments/{id}/edit',             [AdminController::class, 'departmentsUpdate'])->name('admin.departments.update');

    Router::get('/admin/customer-types',                     [AdminController::class, 'customerTypes'])->name('admin.customer_types');
    Router::get('/admin/customer-types/create',              [AdminController::class, 'customerTypesCreate'])->name('admin.customer_types.create');
    Router::post('/admin/customer-types',                    [AdminController::class, 'customerTypesStore'])->name('admin.customer_types.store');
    Router::get('/admin/customer-types/{id}/edit',           [AdminController::class, 'customerTypesEdit'])->name('admin.customer_types.edit');
    Router::post('/admin/customer-types/{id}/edit',          [AdminController::class, 'customerTypesUpdate'])->name('admin.customer_types.update');

    Router::get('/admin/lead-routing',                       [AdminController::class, 'leadRouting'])->name('admin.lead_routing');
    Router::post('/admin/lead-routing',                      [AdminController::class, 'leadRoutingUpdate'])->name('admin.lead_routing.update');
    Router::get('/admin/lead-routing/reset-paint',           [AdminController::class, 'leadRoutingResetPaint'])->name('admin.lead_routing.reset_paint');

    // Shared deactivate/delete for every admin lookup list. {entity} is a slug validated
    // against AdminRepository::ENTITIES — it never reaches a query. Declared after the
    // specific /admin/* routes above so those win.
    Router::post('/admin/{entity}/{id}/toggle-active',        [AdminController::class, 'toggleActive'])->name('admin.toggle_active');
    Router::post('/admin/{entity}/{id}/delete',               [AdminController::class, 'destroy'])->name('admin.destroy');

    Router::get('/admin/company',                            [AdminController::class, 'company'])->name('admin.company');
    Router::post('/admin/company',                           [AdminController::class, 'companyUpdate'])->name('admin.company.update');

    // Logout
    Router::post('/logout', [AuthController::class, 'logout'])->name('logout');

});

// Public webhook — website lead intake (no CSRF, no auth)
Router::post('/webhook/lead', [WebhookController::class, 'lead'])->name('webhook.lead');

// ---------------------------------------------------------------------------
// QuickBooks bridge API — outside the session auth group because it is called
// unattended by the Windows machine running QuickBooks. Guarded by an API key
// (X-API-Key header, set as API_KEY in .env). See docs/QUICKBOOKS_SYNC.md.
// ---------------------------------------------------------------------------
Router::group(['middleware' => ['apikey']], function () {
    Router::get('/api/qb/status',   [QbSyncController::class, 'status'])->name('api.qb.status');
    Router::get('/api/qb/pending',  [QbSyncController::class, 'pending'])->name('api.qb.pending');
    Router::post('/api/qb/ack',     [QbSyncController::class, 'ack'])->name('api.qb.ack');
});
