<?php 
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPriceManagementController;
use App\Http\Controllers\ProductVariantPriceController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\CategoryController; 
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerTypeController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InventoryDocumentController;
use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\InventoryReservationController;
use App\Http\Controllers\OrderReturnController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\PermissionAddressController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CustomerPopupController;
use App\Http\Controllers\OrderAjaxController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\PostController; 
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MyCustomerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderApprovalController;
use App\Http\Controllers\ApprovalWorkflowController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\Admin\OrderScheduleRunController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\RevenueReportController;
use App\Http\Controllers\OrderMonitoringController;
use App\Http\Controllers\WarehouseDashboardController;
use App\Http\Controllers\ShipperDashboardController;
use App\Http\Controllers\CeoDashboardController;
use App\Http\Controllers\TaskManagementController;
use App\Http\Controllers\AccountingDashboardController;
use App\Http\Controllers\TruckStationController;
use App\Http\Controllers\CustomerAppointmentController;
use App\Http\Controllers\OrderScheduleController;
use App\Http\Controllers\MyDashboardController;



Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['vi', 'en'], true), 404);

    session(['locale' => $locale]);

    return back();
})->name('locale.switch');

// Auth pages
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 lần/phút chống brute-force
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});


Route::middleware(['auth', 'assigned'])->group(function () {

    // Làm mới priority cho khách đang chăm
    Route::post('/my-customer/refresh-priority', [\App\Http\Controllers\MyCustomerController::class, 'refreshPriority'])->name('my_customer.refresh_priority');

        Route::get('/thankyou', fn () => view('auth.thankyou'))->name('thankyou');

        // Public-facing pages (require login)
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/variants', [HomeController::class, 'variants'])->name('site.variants');
        Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
        Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
        Route::get('/media/browse', [MediaController::class, 'browse'])->name('media.browse');
        Route::get('/product/{product:slug}', [PageController::class, 'productDetail'])->name('pages.product_detail');

        // Customer Reminders
        Route::post('/my-customer/{customer}/reminders', [\App\Http\Controllers\CustomerReminderController::class, 'store'])->name('customer_reminders.store');
        Route::put('/my-customer/{customer}/reminders/{reminder}', [\App\Http\Controllers\CustomerReminderController::class, 'update'])->name('customer_reminders.update');
        Route::delete('/my-customer/{customer}/reminders/{reminder}', [\App\Http\Controllers\CustomerReminderController::class, 'destroy'])->name('customer_reminders.destroy');
        Route::get('/my-customer-appointments', [CustomerAppointmentController::class, 'index'])->name('pages.my_customer_appointments')->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
        Route::get('/my-customer-appointments/search-customers', [CustomerAppointmentController::class, 'searchCustomers'])->name('customer_appointments.search_customers')->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
        Route::post('/my-customer-appointments', [CustomerAppointmentController::class, 'store'])->name('customer_appointments.store')->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
        Route::put('/my-customer-appointments/{reminder}', [CustomerAppointmentController::class, 'update'])->name('customer_appointments.update')->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
        Route::delete('/my-customer-appointments/{reminder}', [CustomerAppointmentController::class, 'destroy'])->name('customer_appointments.destroy')->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
    // Báo cáo công việc cho user frontend
    Route::get('work-reports', [\App\Http\Controllers\WorkReportController::class, 'index'])
        ->name('work-reports.index');
    // Theo dõi khách hàng (sale / leader / manager)
    Route::get('customer-tracking', [\App\Http\Controllers\CustomerTrackingController::class, 'index'])
        ->name('customer-tracking.index')
        ->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
    Route::get('customer-tracking/data', [\App\Http\Controllers\CustomerTrackingController::class, 'data'])
        ->name('customer-tracking.data')
        ->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
    Route::get('customer-tracking/{customer}', [\App\Http\Controllers\CustomerTrackingController::class, 'show'])
        ->name('customer-tracking.show')
        ->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
    Route::get('customer-tracking/{customer}/data', [\App\Http\Controllers\CustomerTrackingController::class, 'customerData'])
        ->name('customer-tracking.customer-data')
        ->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
    // AJAX lấy tổng tiền đơn hàng
    Route::get('orders/ajax/total', [OrderAjaxController::class, 'total'])->name('orders.ajax.total');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-dashboard', [MyDashboardController::class, 'index'])
        ->name('pages.my_dashboard')
        ->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
    Route::get('/my-dashboard/stats', [MyDashboardController::class, 'stats'])
        ->name('pages.my_dashboard.stats')
        ->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');
    Route::post('/my-dashboard/accept-customer/{customer}', [MyDashboardController::class, 'acceptCustomer'])
        ->name('pages.my_dashboard.accept_customer')
        ->middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin');

    Route::prefix('accounting')->name('accounting.')->middleware('role:accountant,accounting,admin')->group(function () {
        Route::get('/', [AccountingDashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders', [AccountingDashboardController::class, 'orders'])->name('orders');
        Route::get('/customer-debts', [AccountingDashboardController::class, 'customerDebts'])->name('customer-debts');
        Route::get('/supplier-debts', [AccountingDashboardController::class, 'supplierDebts'])->name('supplier-debts');
        Route::get('/cashflow', [AccountingDashboardController::class, 'cashflow'])->name('cashflow');
        Route::get('/cashflow/refresh-history', [AccountingDashboardController::class, 'refreshHistory'])->name('refresh-history');
        Route::get('/cashflow/{transaction}/edit', [AccountingDashboardController::class, 'transactionEdit'])->name('transactions.edit');
        Route::get('/cashflow/{transaction}', [AccountingDashboardController::class, 'cashflowShow'])->name('cashflow.show');
        Route::get('/reconciliation', [AccountingDashboardController::class, 'reconciliation'])->name('reconciliation');
        Route::get('/inventory', [AccountingDashboardController::class, 'inventory'])->name('inventory');
        Route::get('/commissions', [AccountingDashboardController::class, 'commissions'])->name('commissions');
        Route::post('/commissions', [AccountingDashboardController::class, 'storeCommission'])->name('commissions.store');
        Route::get('/discounts', [AccountingDashboardController::class, 'discounts'])->name('discounts');
        Route::post('/discounts', [AccountingDashboardController::class, 'storeDiscount'])->name('discounts.store');
        Route::get('/daily-orders', [AccountingDashboardController::class, 'dailyOrders'])->name('daily-orders');
        Route::get('/daily-sales', [AccountingDashboardController::class, 'dailySales'])->name('daily-sales');
        Route::get('/financial-reports', [AccountingDashboardController::class, 'financialReports'])->name('financial-reports');
        Route::get('/transactions/create', [AccountingDashboardController::class, 'transactionCreate'])->name('transactions.create');
        Route::post('/transactions', [AccountingDashboardController::class, 'transactionStore'])->name('transactions.store');
        Route::put('/transactions/{transaction}', [AccountingDashboardController::class, 'transactionUpdate'])->name('transactions.update');
        Route::post('/transactions/{transaction}/approve', [AccountingDashboardController::class, 'transactionApprove'])->name('transactions.approve');
        Route::post('/transactions/{transaction}/reject', [AccountingDashboardController::class, 'transactionReject'])->name('transactions.reject');
        Route::get('/api/orders-list', [AccountingDashboardController::class, 'apiOrdersList'])->name('api.orders-list');
        Route::get('/api/customers-list', [AccountingDashboardController::class, 'apiCustomersList'])->name('api.customers-list');
        Route::get('/api/order-detail/{order}', [AccountingDashboardController::class, 'apiOrderDetail'])->name('api.order-detail');
        Route::get('/api/customer-detail/{customer}', [AccountingDashboardController::class, 'apiCustomerDetail'])->name('api.customer-detail');
        Route::post('/api/reconcile-account-balances', [AccountingDashboardController::class, 'apiReconcileAccountBalances'])->name('api.reconcile-account-balances');
        // Transaction categories
        Route::get('/transaction-categories', [\App\Http\Controllers\TransactionCategoryController::class, 'index'])->name('transaction-categories.index');
        Route::post('/transaction-categories', [\App\Http\Controllers\TransactionCategoryController::class, 'store'])->name('transaction-categories.store');
        Route::put('/transaction-categories/{transactionCategory}', [\App\Http\Controllers\TransactionCategoryController::class, 'update'])->name('transaction-categories.update');
        Route::post('/transaction-categories/{transactionCategory}/toggle-active', [\App\Http\Controllers\TransactionCategoryController::class, 'toggleActive'])->name('transaction-categories.toggle-active');

        // Accounts management
        Route::get('/accounts', [\App\Http\Controllers\AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/adjustments', [\App\Http\Controllers\AccountController::class, 'adjustmentHistory'])->name('accounts.adjustments');
        Route::get('/accounts/create', [\App\Http\Controllers\AccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [\App\Http\Controllers\AccountController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{account}/edit', [\App\Http\Controllers\AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('/accounts/{account}', [\App\Http\Controllers\AccountController::class, 'update'])->name('accounts.update');
        Route::post('/accounts/{account}/deposit', [\App\Http\Controllers\AccountController::class, 'deposit'])->name('accounts.deposit');
        Route::post('/accounts/{account}/withdraw', [\App\Http\Controllers\AccountController::class, 'withdraw'])->name('accounts.withdraw');
    });

    Route::prefix('admin/accounting')->name('admin.accounting.')->middleware('role:admin')->group(function () {
        Route::get('/cashflow', [AccountingDashboardController::class, 'cashflow'])->name('cashflow');
        Route::get('/cashflow/refresh-history', [AccountingDashboardController::class, 'refreshHistory'])->name('refresh-history');
        Route::get('/cashflow/{transaction}/edit', [AccountingDashboardController::class, 'transactionEdit'])->name('transactions.edit');
        Route::get('/cashflow/{transaction}', [AccountingDashboardController::class, 'cashflowShow'])->name('cashflow.show');
        Route::get('/financial-reports', [AccountingDashboardController::class, 'financialReports'])->name('financial-reports');
        Route::get('/transactions/create', [AccountingDashboardController::class, 'transactionCreate'])->name('transactions.create');
        Route::post('/transactions', [AccountingDashboardController::class, 'transactionStore'])->name('transactions.store');
        Route::put('/transactions/{transaction}', [AccountingDashboardController::class, 'transactionUpdate'])->name('transactions.update');
        Route::post('/transactions/{transaction}/approve', [AccountingDashboardController::class, 'transactionApprove'])->name('transactions.approve');
        Route::post('/transactions/{transaction}/reject', [AccountingDashboardController::class, 'transactionReject'])->name('transactions.reject');
        Route::get('/api/orders-list', [AccountingDashboardController::class, 'apiOrdersList'])->name('api.orders-list');
        Route::get('/api/customers-list', [AccountingDashboardController::class, 'apiCustomersList'])->name('api.customers-list');
        Route::get('/api/order-detail/{order}', [AccountingDashboardController::class, 'apiOrderDetail'])->name('api.order-detail');
        Route::get('/api/customer-detail/{customer}', [AccountingDashboardController::class, 'apiCustomerDetail'])->name('api.customer-detail');
        Route::post('/api/reconcile-account-balances', [AccountingDashboardController::class, 'apiReconcileAccountBalances'])->name('api.reconcile-account-balances');
        Route::get('/transaction-categories', [\App\Http\Controllers\TransactionCategoryController::class, 'index'])->name('transaction-categories.index');
        Route::post('/transaction-categories', [\App\Http\Controllers\TransactionCategoryController::class, 'store'])->name('transaction-categories.store');
        Route::put('/transaction-categories/{transactionCategory}', [\App\Http\Controllers\TransactionCategoryController::class, 'update'])->name('transaction-categories.update');
        Route::post('/transaction-categories/{transactionCategory}/toggle-active', [\App\Http\Controllers\TransactionCategoryController::class, 'toggleActive'])->name('transaction-categories.toggle-active');
        Route::get('/accounts', [\App\Http\Controllers\AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/adjustments', [\App\Http\Controllers\AccountController::class, 'adjustmentHistory'])->name('accounts.adjustments');
        Route::get('/accounts/create', [\App\Http\Controllers\AccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [\App\Http\Controllers\AccountController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{account}/edit', [\App\Http\Controllers\AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('/accounts/{account}', [\App\Http\Controllers\AccountController::class, 'update'])->name('accounts.update');
        Route::post('/accounts/{account}/deposit', [\App\Http\Controllers\AccountController::class, 'deposit'])->name('accounts.deposit');
        Route::post('/accounts/{account}/withdraw', [\App\Http\Controllers\AccountController::class, 'withdraw'])->name('accounts.withdraw');
    });

    // ─── Warehouse module ───────────────────────────────────────────────────
    Route::prefix('warehouse')->name('warehouse.')->middleware('role:warehouse,admin')->group(function () {
        Route::get('/',            [WarehouseDashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders',      [WarehouseDashboardController::class, 'orders'])->name('orders');
        Route::post('/orders/{order}/logistics',        [WarehouseDashboardController::class, 'updateLogistics'])->name('orders.logistics');
        Route::post('/orders/{order}/start-packing',    [WarehouseDashboardController::class, 'startPacking'])->name('orders.start-packing');
        Route::post('/orders/{order}/complete-packing', [WarehouseDashboardController::class, 'completePacking'])->name('orders.complete-packing');
        Route::post('/orders/{order}/reopen-packing',   [WarehouseDashboardController::class, 'reopenPacking'])->name('orders.reopen-packing');
        Route::post('/orders/rap-don-hang',              [WarehouseDashboardController::class, 'rapDonHang'])->name('orders.rap-don-hang');
        Route::get('/returns',     [WarehouseDashboardController::class, 'returns'])->name('returns');
        Route::post('/returns/{order}/confirm', [WarehouseDashboardController::class, 'confirmReturn'])->name('returns.confirm');
        
        // Warehouse Management Features
        Route::get('/stock-in',          [WarehouseDashboardController::class, 'stockIn'])->name('stock-in');
        Route::post('/stock-in',         [WarehouseDashboardController::class, 'storeStockIn'])->name('stock-in.store');
        Route::get('/stock-in/{document}', [WarehouseDashboardController::class, 'showDocument'])->name('stock-in.show');
        Route::get('/stock-in/{document}/edit', [WarehouseDashboardController::class, 'editStockIn'])->name('stock-in.edit');
        Route::put('/stock-in/{document}', [WarehouseDashboardController::class, 'updateStockIn'])->name('stock-in.update');
        Route::get('/stock-out',         [WarehouseDashboardController::class, 'stockOut'])->name('stock-out');
        Route::post('/stock-out',        [WarehouseDashboardController::class, 'storeStockOut'])->name('stock-out.store');
        Route::get('/stock-out/{document}', [WarehouseDashboardController::class, 'showDocument'])->name('stock-out.show');
        Route::get('/inventory',   [WarehouseDashboardController::class, 'inventory'])->name('inventory');
        Route::post('/inventory/cancel-overdue', [WarehouseDashboardController::class, 'cancelOverdueOrders'])->name('inventory.cancel-overdue');
        Route::get('/products',    [WarehouseDashboardController::class, 'products'])->name('products');
        Route::get('/reports',     [WarehouseDashboardController::class, 'reports'])->name('reports');
    });

    // ─── Shipper module ─────────────────────────────────────────────────────
    Route::prefix('shipper')->name('shipper.')->middleware('role:shipper,admin')->group(function () {
        Route::get('/',                                [ShipperDashboardController::class, 'index'])->name('dashboard');
        Route::get('/available',                       [ShipperDashboardController::class, 'available'])->name('available');
        Route::post('/available/{order}/accept',       [ShipperDashboardController::class, 'accept'])->name('accept');
        Route::get('/my-orders',                       [ShipperDashboardController::class, 'myOrders'])->name('my-orders');
        Route::get('/orders/{order}/delivered-form',   [ShipperDashboardController::class, 'deliveredForm'])->name('delivered-form');
        Route::post('/orders/{order}/mark-delivered',  [ShipperDashboardController::class, 'markDelivered'])->name('mark-delivered');
        Route::get('/orders/{order}/return-form',      [ShipperDashboardController::class, 'returnForm'])->name('return-form');
        Route::post('/orders/{order}/store-return',    [ShipperDashboardController::class, 'storeReturn'])->name('store-return');
        Route::get('/history',                         [ShipperDashboardController::class, 'history'])->name('history');
    });

    // ─── CEO module ────────────────────────────────────────────────────────
    Route::prefix('ceo')->name('ceo.')->middleware('role:ceo,admin')->group(function () {
        Route::get('/', [CeoDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/revenue', [CeoDashboardController::class, 'revenue'])->name('revenue');
        Route::get('/orders', [CeoDashboardController::class, 'orders'])->name('orders');
        Route::get('/sales', [CeoDashboardController::class, 'sales'])->name('sales');
        Route::get('/debts', [CeoDashboardController::class, 'debts'])->name('debts');
        Route::get('/warehouse', [CeoDashboardController::class, 'warehouse'])->name('warehouse');
        Route::get('/shipper', [CeoDashboardController::class, 'shipper'])->name('shipper');
        Route::get('/customers', [CeoDashboardController::class, 'customers'])->name('customers');
        Route::get('/customers-list', [CeoDashboardController::class, 'customersList'])->name('customers-list');
        Route::get('/users-list', [CeoDashboardController::class, 'usersList'])->name('users-list');
        Route::get('/alerts', [CeoDashboardController::class, 'alerts'])->name('alerts');
        Route::get('/reports', [CeoDashboardController::class, 'reports'])->name('reports');
        Route::get('/weekly-report', [CeoDashboardController::class, 'weeklyReport'])->name('weekly-report');
        Route::get('/weekly-customer-report', [CeoDashboardController::class, 'weeklyCustomerReport'])->name('weekly-customer-report');
        Route::get('/financial-reports', [CeoDashboardController::class, 'financialReports'])->name('financial-reports');
        Route::get('/daily-sales', [CeoDashboardController::class, 'dailySales'])->name('daily-sales');

        // Thu chi (cashflow) — reuse AccountingDashboardController, CEO layout via helpers
        Route::get('/cashflow', [AccountingDashboardController::class, 'cashflow'])->name('cashflow');
        Route::get('/cashflow/{transaction}/edit', [AccountingDashboardController::class, 'transactionEdit'])->name('transactions.edit');
        Route::get('/cashflow/{transaction}', [AccountingDashboardController::class, 'cashflowShow'])->name('cashflow.show');
        Route::get('/transactions/create', [AccountingDashboardController::class, 'transactionCreate'])->name('transactions.create');
        Route::post('/transactions', [AccountingDashboardController::class, 'transactionStore'])->name('transactions.store');
        Route::put('/transactions/{transaction}', [AccountingDashboardController::class, 'transactionUpdate'])->name('transactions.update');
        Route::post('/transactions/{transaction}/approve', [AccountingDashboardController::class, 'transactionApprove'])->name('transactions.approve');
        Route::post('/transactions/{transaction}/reject', [AccountingDashboardController::class, 'transactionReject'])->name('transactions.reject');

        // Task Management Routes
        Route::get('/task-management', [TaskManagementController::class, 'index'])->name('task-management.index');
        Route::post('/task-management', [TaskManagementController::class, 'store'])->name('task-management.store');
        Route::patch('/task-management/{task}', [TaskManagementController::class, 'update'])->name('task-management.update');
        Route::delete('/task-management/{task}', [TaskManagementController::class, 'destroy'])->name('task-management.destroy');
        Route::patch('/task-management/{task}/status', [TaskManagementController::class, 'updateStatus'])->name('task-management.update-status');
        Route::get('/task-management/customers', [TaskManagementController::class, 'getCustomers'])->name('task-management.customers');
        // task updte prpffile
        Route::get('/profile', [\App\Http\Controllers\CeoPageController::class, 'show'])->name('profile');
        Route::post('/profile', [\App\Http\Controllers\CeoPageController::class, 'update'])->name('profile.update');
        // Đổi mật khẩu CEO
        Route::get('/change-password', [\App\Http\Controllers\CeoChangePasswordController::class, 'show'])->name('password.change');
        Route::post('/change-password', [\App\Http\Controllers\CeoChangePasswordController::class, 'update'])->name('password.update');
    });
    Route::get('reports/revenue', [RevenueReportController::class, 'index'])
        ->name('reports.revenue')
        ->middleware('permission');
    Route::get('reports/revenue/export', [RevenueReportController::class, 'export'])
        ->name('reports.revenue.export')
        ->middleware('permission');
    Route::get('orders/monitoring', [OrderMonitoringController::class, 'index'])
        ->name('orders.monitoring')
        ->middleware('permission');
    Route::get('orders/monitoring/data', [OrderMonitoringController::class, 'data'])
        ->name('orders.monitoring.data')
        ->middleware('permission');

    // Quản lý giá sản phẩm theo ngày
    Route::get('products/price-management', [ProductPriceManagementController::class, 'index'])
        ->name('products.price-management.index')
        ->middleware('permission');
    Route::get('products/{product}/price-management', [ProductPriceManagementController::class, 'show'])
        ->name('products.price-management.show')
        ->middleware('permission');
    Route::post('products/{product}/price-management', [ProductPriceManagementController::class, 'update'])
        ->name('products.price-management.update')
        ->middleware('permission');

    // Quản lý sản phẩm
    Route::resource('products', ProductController::class)->middleware('permission');
    Route::post('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore')->middleware('permission');
    Route::get('products/{product}/quick-edit-form', [ProductController::class, 'getQuickEditForm'])->name('products.getQuickEditForm');
    // Quản trị biến thể sản phẩm
    Route::resource('product-variants', ProductVariantController::class)->only(['index', 'create', 'store', 'edit', 'update'])->middleware('permission');
    Route::post('product-variants/bulk-delete', [ProductVariantController::class, 'bulkDelete'])->name('product-variants.bulk-delete')->middleware('permission');
    Route::post('product-variants/{variant}/duplicate', [ProductVariantController::class, 'duplicate'])->name('product-variants.duplicate')->middleware('permission');

    // AJAX popup chọn khách hàng
    Route::get('customers/popup/search', [CustomerPopupController::class, 'search'])->name('customers.popup.search')->middleware('auth');
    Route::post('customers/popup/store', [CustomerPopupController::class, 'store'])->name('customers.popup.store')->middleware('auth');



    Route::get('variants/{variant}/edit-price', [ProductVariantPriceController::class, 'edit'])->name('variants.edit-price')->middleware('permission');
    Route::put('variants/{variant}/update-price', [ProductVariantPriceController::class, 'update'])->name('variants.update-price')->middleware('permission');

    // Lịch sử giá (AJAX)
    Route::get('variants/{id}/price-history', [ProductVariantPriceController::class, 'priceHistory'])->name('variants.price-history')->middleware('permission');
    // Cập nhật giá mới (AJAX)
    Route::post('variants/{id}/update-price', [ProductVariantPriceController::class, 'updatePrice'])->name('variants.update-price-ajax')->middleware('permission');

    // Popup gallery chọn ảnh cho biến thể
    Route::get('variants/image-library', [MediaController::class, 'variantImageLibrary'])->name('variants.image-library');
    
    
    Route::post('/ai/generate-description', [AIController::class, 'generateDescription'])->name('ai.generateDescription');

    Route::get('admin/notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('admin/notifications/read-all', [AdminNotificationController::class, 'markAllAsRead'])->name('admin.notifications.read_all');
    Route::post('admin/notifications/{notificationId}/read', [AdminNotificationController::class, 'markAsRead'])->name('admin.notifications.read');
    Route::get('admin/events', [AdminEventController::class, 'index'])->name('admin.events.index');

    // Đơn tự động — kiểm soát & lịch sử chạy lệnh
    Route::get('admin/order-schedule-runs', [OrderScheduleRunController::class, 'index'])->name('admin.order-schedule-runs.index')->middleware('role:admin');
    Route::post('admin/order-schedule-runs/run-now', [OrderScheduleRunController::class, 'runNow'])->name('admin.order-schedule-runs.run-now')->middleware('role:admin');
    Route::post('admin/order-schedule-runs/run-daily-rules-now', [OrderScheduleRunController::class, 'runDailyRulesNow'])->name('admin.order-schedule-runs.run-daily-rules-now')->middleware('role:admin');


    // Quản lý đơn hàng
    Route::get('orders/list-ajax', [OrderController::class, 'listAjax'])->name('orders.list-ajax');
    Route::get('orders/{order}/list-variant', [OrderController::class, 'listVariant'])->name('orders.list-variant');
    Route::get('orders/{order}/variants-list', [OrderController::class, 'variantsList'])->name('orders.variants-list');
    Route::post('orders/{order}/toggle-status', [OrderController::class, 'toggleStatus']);
    Route::get('/orders/create-new', [OrderController::class, 'createNewOrderForm'])->name('orders.create_new');
    Route::post('/orders/store-a-new', [OrderController::class, 'storeANewOrder'])->name('orders.store_a_new');
    Route::post('/orders/store-new', [OrderController::class, 'storeNewOrder'])->name('orders.store_new');
    Route::get('/orders/ajax-customer-search', [OrderController::class, 'ajaxCustomerSearch'])->name('orders.ajax_customer_search');
    Route::get('/orders/ajax-variant-search', [OrderController::class, 'ajaxVariantSearch'])->name('orders.ajax_variant_search');
    Route::post('orders/{order}/add-variant', [OrderController::class, 'addVariant']);
    Route::post('orders/{order}/remove-variant', [OrderController::class, 'removeVariant']);
    Route::post('/orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders/{order}/picking', [OrderController::class, 'picking'])->name('orders.picking');
    Route::post('/orders/{order}/complete-packing', [OrderController::class, 'completePacking'])->name('orders.complete-packing');
    Route::post('/orders/{order}/pickup', [OrderController::class, 'pickup'])->name('orders.pickup');
    Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
    Route::post('/orders/{order}/delivered', [OrderController::class, 'markDelivered'])->name('orders.delivered');
    Route::post('/orders/{order}/delivery-time', [OrderController::class, 'updateDeliveryTime'])->name('orders.update-delivery-time');
    Route::post('/orders/{order}/complete-payment', [OrderController::class, 'completePayment'])->name('orders.complete-payment');
    Route::post('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
    Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/approve', [OrderApprovalController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/reject', [OrderApprovalController::class, 'reject'])->name('orders.reject');
    Route::resource('orders', OrderController::class)->middleware('permission');

    Route::get('approval-workflows', [ApprovalWorkflowController::class, 'index'])->name('approval-workflows.index');
    Route::get('approval-workflows/create', [ApprovalWorkflowController::class, 'create'])->name('approval-workflows.create');
    Route::post('approval-workflows', [ApprovalWorkflowController::class, 'store'])->name('approval-workflows.store');
    Route::get('approval-workflows/{approvalWorkflow}/edit', [ApprovalWorkflowController::class, 'edit'])->name('approval-workflows.edit');
    Route::put('approval-workflows/{approvalWorkflow}', [ApprovalWorkflowController::class, 'update'])->name('approval-workflows.update');

    // Giao việc (Task Assignment)
    Route::post('task-assignments/{taskAssignment}/approve',          [\App\Http\Controllers\TaskAssignmentController::class, 'approve'])->name('task-assignments.approve');
    Route::post('task-assignments/{taskAssignment}/reject',           [\App\Http\Controllers\TaskAssignmentController::class, 'reject'])->name('task-assignments.reject');
    Route::post('task-assignments/{taskAssignment}/cancel',           [\App\Http\Controllers\TaskAssignmentController::class, 'cancel'])->name('task-assignments.cancel');
    Route::post('task-assignments/{taskAssignment}/assignee-update',  [\App\Http\Controllers\TaskAssignmentController::class, 'assigneeUpdate'])->name('task-assignments.assignee-update');
    Route::post('task-assignments/{taskAssignment}/complete-content', [\App\Http\Controllers\TaskAssignmentController::class, 'completeWithContent'])->name('task-assignments.complete-with-content');
    Route::get('task-assignments/{taskAssignment}/complete', [\App\Http\Controllers\TaskAssignmentController::class, 'completeForm'])->name('task-assignments.complete-form');
    Route::post('task-assignments/{taskAssignment}/verify-completion', [\App\Http\Controllers\TaskAssignmentController::class, 'verifyCompletion'])->name('task-assignments.verify-completion');
    Route::post('task-assignments/{taskAssignment}/reject-completion', [\App\Http\Controllers\TaskAssignmentController::class, 'rejectCompletion'])->name('task-assignments.reject-completion');
    Route::get('task-assignments/assigned/to-me',                     [\App\Http\Controllers\TaskAssignmentController::class, 'assignedToMe'])->name('task-assignments.assigned-to-me');
    Route::get('task-assignments/assigned/by-me',                     [\App\Http\Controllers\TaskAssignmentController::class, 'assignedByMe'])->name('task-assignments.assigned-by-me');
    Route::get('task-assignments/in-progress',                        [\App\Http\Controllers\TaskAssignmentController::class, 'inProgress'])->name('task-assignments.in-progress');
    Route::get('task-assignments/awaiting-verification',              [\App\Http\Controllers\TaskAssignmentController::class, 'awaitingVerification'])->name('task-assignments.awaiting-verification');
    Route::get('task-assignments/verify-list',                        [\App\Http\Controllers\TaskAssignmentController::class, 'verifyList'])->name('task-assignments.verify');
    Route::get('task-assignments/tracking',                           [\App\Http\Controllers\TaskAssignmentController::class, 'verifyList'])->name('task-assignments.tracking');
    Route::get('task-assignments/history',                            [\App\Http\Controllers\TaskAssignmentController::class, 'history'])->name('task-assignments.history');
    Route::resource('task-assignments', \App\Http\Controllers\TaskAssignmentController::class);

    // My Tasks - cho Sale/Leader/Manager users
    Route::get('/my-tasks', [\App\Http\Controllers\TaskAssignmentController::class, 'assignedToMe'])->name('my-tasks');

    // User-facing task aliases
    Route::get('/tasks', [\App\Http\Controllers\TaskAssignmentController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [\App\Http\Controllers\TaskAssignmentController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [\App\Http\Controllers\TaskAssignmentController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/my-tasks', [\App\Http\Controllers\TaskAssignmentController::class, 'assignedToMe'])->name('tasks.my-tasks');
    Route::get('/tasks/assigned', [\App\Http\Controllers\TaskAssignmentController::class, 'assignedByMe'])->name('tasks.assigned');
    Route::get('/tasks/in-progress', [\App\Http\Controllers\TaskAssignmentController::class, 'inProgress'])->name('tasks.in-progress');
    Route::get('/tasks/awaiting-verification', [\App\Http\Controllers\TaskAssignmentController::class, 'awaitingVerification'])->name('tasks.awaiting-verification');
    Route::get('/tasks/verify', [\App\Http\Controllers\TaskAssignmentController::class, 'verifyList'])->name('tasks.verify');
    Route::get('/tasks/rejected', [\App\Http\Controllers\TaskAssignmentController::class, 'history'])->name('tasks.rejected');
    Route::get('/tasks/{taskAssignment}', [\App\Http\Controllers\TaskAssignmentController::class, 'show'])->name('tasks.show');
    Route::post('/tasks/{taskAssignment}/complete', [\App\Http\Controllers\TaskAssignmentController::class, 'completeWithContent'])->name('tasks.complete');
    Route::get('/tasks/{taskAssignment}/complete', [\App\Http\Controllers\TaskAssignmentController::class, 'completeForm'])->name('tasks.complete-form');
    Route::post('/tasks/{taskAssignment}/verify', [\App\Http\Controllers\TaskAssignmentController::class, 'verifyCompletion'])->name('tasks.verify-completion');
    Route::post('/tasks/{taskAssignment}/reject', [\App\Http\Controllers\TaskAssignmentController::class, 'rejectCompletion'])->name('tasks.reject-completion');

    // Phân quyền giao việc (Admin only)
    Route::post('task-delegate-configs/{taskDelegateConfig}/toggle', [\App\Http\Controllers\TaskDelegateConfigController::class, 'toggle'])->name('task-delegate-configs.toggle');
    Route::post('task-delegate-configs/destroy-assigner',            [\App\Http\Controllers\TaskDelegateConfigController::class, 'destroyAssigner'])->name('task-delegate-configs.destroy-assigner');
    Route::resource('task-delegate-configs', \App\Http\Controllers\TaskDelegateConfigController::class)->only(['index','create','store','destroy']);

    // Quản lý danh mục
    Route::post('categories/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('categories.bulk-delete')->middleware('permission');
    Route::resource('categories', CategoryController::class)->middleware('permission');

    // Quản lý vai trò
    Route::resource('roles', RoleController::class)->middleware('permission'); 

    // Quản lý quyền
    // Route::resource('permissions', PermissionController::class);//->middleware('permission');

    Route::post('permissions/sync-routes', [PermissionController::class, 'syncFromRoutes'])
        ->name('permissions.sync-routes')
        ->middleware('permission:permissions.index');
    Route::resource('permissions', PermissionController::class)->middleware('permission');

    // Quản lý người dùng
    Route::get('users/bulk-assign-team', [UserController::class, 'bulkAssignTeamForm'])->name('users.bulk-assign-team.form')->middleware('permission');
    Route::post('users/bulk-assign-team', [UserController::class, 'bulkAssignTeam'])->name('users.bulk-assign-team')->middleware('permission');
    Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete')->middleware('permission');
    Route::resource('users', UserController::class)->middleware('permission');
    Route::resource('teams', TeamController::class)->middleware('permission');

    // Ví dụ: khi sau này bạn thêm Customer
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export')->middleware('permission');
    Route::get('customers/import', [CustomerController::class, 'importForm'])->name('customers.import.form')->middleware('permission');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import')->middleware('permission');
    Route::post('customers/{customer}/assign-sale', [CustomerController::class, 'assignSale'])->name('customers.assign-sale')->middleware('permission');
    Route::post('customers/{customer}/payments', [CustomerController::class, 'storePayment'])->name('customers.payments.store')->middleware('permission');
    Route::get('customers/{customer}/report', [CustomerController::class, 'report'])->name('customers.report')->middleware('permission');
    Route::resource('customers', CustomerController::class)->middleware('permission');
    Route::resource('truck-stations', TruckStationController::class)
        ->except('show')
        ->middleware('role:admin,sale,leader,leader_sale,sale_manager');

    // Xóa nhiều khách hàng
    Route::post('customers/bulk-assign-sale', [CustomerController::class, 'bulkAssignSale'])->name('customers.bulkAssignSale')->middleware('permission');
    Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customers.bulkDelete')->middleware('permission');
    Route::post('customers/bulk-mark-employee', [CustomerController::class, 'bulkMarkEmployee'])->name('customers.bulkMarkEmployee')->middleware('permission');
    Route::post('customers/bulk-unmark-employee', [CustomerController::class, 'bulkUnmarkEmployee'])->name('customers.bulkUnmarkEmployee')->middleware('permission');
    Route::resource('provinces', ProvinceController::class)->middleware('permission');
    Route::post('provinces/{province}/wards', [ProvinceController::class, 'storeWard'])->name('provinces.wards.store')->middleware('permission');
    Route::get('provinces/{province}/wards', [ProvinceController::class, 'indexWards'])->name('provinces.wards.index')->middleware('permission');
    Route::put('provinces/{province}/wards/{ward}', [ProvinceController::class, 'updateWard'])->name('provinces.wards.update')->middleware('permission');
    Route::delete('provinces/{province}/wards/{ward}', [ProvinceController::class, 'destroyWard'])->name('provinces.wards.destroy')->middleware('permission');
    Route::resource('companies', \App\Http\Controllers\CompanyController::class)->middleware('permission');
    Route::get('companies/export', [\App\Http\Controllers\CompanyController::class, 'export'])->name('companies.export')->middleware('permission');
    Route::get('companies/import', [\App\Http\Controllers\CompanyController::class, 'importForm'])->name('companies.import.form')->middleware('permission');
    Route::post('companies/import', [\App\Http\Controllers\CompanyController::class, 'import'])->name('companies.import')->middleware('permission');
    
    Route::resource('customertype', CustomerTypeController::class)->middleware('permission');
    Route::resource('warehouses', WarehouseController::class)->middleware('permission');
    Route::resource('inventories', InventoryController::class)->middleware('permission');
    Route::resource('inventory-movements', InventoryMovementController::class)->middleware('permission');
    Route::resource('inventory-documents', InventoryDocumentController::class)->middleware('permission');
    Route::resource('inventory-adjustments', InventoryAdjustmentController::class)->middleware('permission');
    Route::resource('inventory-reservations', InventoryReservationController::class)->middleware('permission');
    Route::get('/my-orders/{order}/returns/create', [OrderReturnController::class, 'createForMyOrder'])->name('site.order-returns.create');
    Route::post('/my-orders/{order}/returns', [OrderReturnController::class, 'storeForMyOrder'])->name('site.order-returns.store');
    Route::post('order-returns/{orderReturn}/ship-confirm', [OrderReturnController::class, 'shipConfirm'])->name('order-returns.ship-confirm')->middleware('permission');
    Route::post('order-returns/{orderReturn}/warehouse-confirm', [OrderReturnController::class, 'warehouseConfirm'])->name('order-returns.warehouse-confirm')->middleware('permission');
    Route::post('order-returns/sync-warehouse-receipts', [OrderReturnController::class, 'syncWarehouseReceipts'])->name('order-returns.sync-warehouse-receipts')->middleware('permission');
    Route::resource('order-returns', OrderReturnController::class)->middleware('permission');
    
    // Route list toàn bộ địa chỉ (không cần customerId)
    Route::get('customers/list/addresses', [CustomerAddressController::class, 'list'])
    ->name('customers.addresses.list')->middleware('permission');
    //->middleware('permission:addresses.view'); // nếu bạn dùng middleware permission

    //Route::resource('customeraddress', CustomerAddressController::class)->middleware('permission');
    Route::resource('customers.addresses', CustomerAddressController::class)->middleware('permission');
    
    //Route::resource('media', MediaController::class);
    Route::resource('media', MediaController::class)->parameters([
        'media' => 'media'
    ]);

    // Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');

    Route::get('/media/library/popup', [MediaController::class, 'popup'])->name('media.library.popup');
    Route::post('/media/popup/store', [MediaController::class, 'popupStore'])->name('media.popup.store');

    Route::get('/media/gallery/popup', [MediaController::class, 'popupGallery'])->name('media.gallery.popup');
    Route::post('/media/gallery/store', [MediaController::class, 'storeGallery'])->name('media.gallery.store');

    
    // Route::get('{type}/{id}/media', [MediaController::class, 'index'])->name('media.index');
    // Route::post('{type}/{id}/media', [MediaController::class, 'store'])->name('media.store');
    // Route::get('media/{media}', [MediaController::class, 'show'])->name('media.show');
    // Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');


    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // Compatibility: allow direct GET /logout from address bar/legacy links.
    Route::get('/logout', [AuthController::class, 'logout']);

    Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::get('settings/reset-data', [SettingController::class, 'resetDataIndex'])->name('settings.reset-data.index')->middleware('role:admin');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/reset-data', [SettingController::class, 'resetData'])->name('settings.reset-data')->middleware('role:admin');
        Route::post('settings/deploy', [SettingController::class, 'deploy'])->name('settings.deploy')->middleware('role:admin');
        Route::post('settings/push', [SettingController::class, 'push'])->name('settings.push')->middleware('role:admin');
        Route::post('settings/artisan', [SettingController::class, 'artisan'])->name('settings.artisan')->middleware('role:admin');
        Route::resource('posts', PostController::class);
        Route::resource('post-categories', PostCategoryController::class);
        Route::resource('pages', PageController::class);
        Route::resource('brands', \App\Http\Controllers\BrandController::class)->middleware('permission');
        Route::resource('suppliers', \App\Http\Controllers\Admin\SupplierController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission');

        // Truck management
        Route::get('truck-brands/{truckBrand}/routes', [\App\Http\Controllers\Admin\TruckBrandController::class, 'routes'])->name('truck-brands.routes');
        Route::resource('truck-brands', \App\Http\Controllers\Admin\TruckBrandController::class);
        Route::resource('truck-stations', \App\Http\Controllers\Admin\TruckStationAdminController::class);
        Route::get('truck-routes/stations/search', [\App\Http\Controllers\Admin\TruckRouteController::class, 'searchStations'])->name('truck-routes.stations.search');
        Route::post('truck-routes/{truckRoute}/stops', [\App\Http\Controllers\Admin\TruckRouteController::class, 'storeStop'])->name('truck-routes.stops.store');
        Route::put('truck-routes/{truckRoute}/stops/{stop}', [\App\Http\Controllers\Admin\TruckRouteController::class, 'updateStop'])->name('truck-routes.stops.update');
        Route::delete('truck-routes/{truckRoute}/stops/{stop}', [\App\Http\Controllers\Admin\TruckRouteController::class, 'destroyStop'])->name('truck-routes.stops.destroy');
        Route::resource('truck-routes', \App\Http\Controllers\Admin\TruckRouteController::class);
    });

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // My Customer Page (sale / leader / manager only)
    Route::middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin')->group(function () {
    Route::get('/my-customer', [PageController::class, 'myCustomer'])->name('pages.my_customer');
    Route::get('/my-customer/ajax', [PageController::class, 'myCustomerAjax'])->name('pages.my_customer.ajax');
    Route::get('/my-customer/create', [PageController::class, 'myCustomerCreate'])->name('my_customer.create');
    Route::get('/my-customer/check-duplicate', [PageController::class, 'myCustomerCheckDuplicate'])->name('my_customer.check_duplicate');
    Route::post('/my-customer', [PageController::class, 'myCustomerStore'])->name('my_customer.store');
    Route::get('/my-customer/{customer}/edit', [PageController::class, 'myCustomerEdit'])->name('my_customer.edit');
    Route::put('/my-customer/{customer}', [PageController::class, 'myCustomerUpdate'])->name('my_customer.update');
    Route::delete('/my-customer/{customer}', [PageController::class, 'myCustomerDestroy'])->name('my_customer.destroy');
    Route::post('/my-customer/{customerId}/restore', [PageController::class, 'myCustomerRestore'])->name('my_customer.restore');
    Route::delete('/my-customer/{customerId}/force-delete', [PageController::class, 'myCustomerForceDelete'])->name('my_customer.force_delete');
    Route::post('/my-customer/bulk-delete', [PageController::class, 'myCustomerBulkDelete'])->name('my_customer.bulk_delete');
    Route::post('/my-customer/{customer}/takeover', [PageController::class, 'myCustomerTakeover'])->name('my_customer.takeover');
    Route::get('/my-customer/import', [PageController::class, 'myCustomerImportForm'])->name('my_customer.import_form');
    Route::post('/my-customer/import', [PageController::class, 'myCustomerImport'])->name('my_customer.import');
    Route::get('/my-customer/schedules', [OrderScheduleController::class, 'index'])->name('my_customer.schedules.index');
    Route::get('/my-customer/schedules/create', [OrderScheduleController::class, 'create'])->name('my_customer.schedules.create');
    Route::post('/my-customer/schedules', [OrderScheduleController::class, 'store'])->name('my_customer.schedules.store');
    Route::get('/my-customer/{customer}/schedules/create', [OrderScheduleController::class, 'createForCustomer'])->name('my_customer.schedules.create_for_customer');
    Route::get('/my-customer/schedules/{schedule}', [OrderScheduleController::class, 'show'])->name('my_customer.schedules.show');
    Route::get('/my-customer/schedules/{schedule}/edit', [OrderScheduleController::class, 'edit'])->name('my_customer.schedules.edit');
    Route::put('/my-customer/schedules/{schedule}', [OrderScheduleController::class, 'update'])->name('my_customer.schedules.update');
    Route::delete('/my-customer/schedules/{schedule}', [OrderScheduleController::class, 'destroy'])->name('my_customer.schedules.destroy');
    Route::post('/my-customer/schedules/{schedule}/generate', [OrderScheduleController::class, 'generateFromReview'])->name('my_customer.schedules.generate');
    Route::post('/my-customer/schedules/{schedule}/toggle-active', [OrderScheduleController::class, 'toggleActive'])->name('my_customer.schedules.toggle_active');
    Route::post('/my-customer/schedules/evaluate-today', [OrderScheduleController::class, 'evaluateToday'])->name('my_customer.schedules.evaluate_today');
    Route::get('/my-customer/daily-schedules/{dailySchedule}/edit', [OrderScheduleController::class, 'editDaily'])->name('my_customer.daily_schedules.edit');
    Route::put('/my-customer/daily-schedules/{dailySchedule}', [OrderScheduleController::class, 'updateDaily'])->name('my_customer.daily_schedules.update');
    Route::delete('/my-customer/daily-schedules/{dailySchedule}', [OrderScheduleController::class, 'destroyDaily'])->name('my_customer.daily_schedules.destroy');
    Route::get('/my-customer/{customer}', [PageController::class, 'myCustomerShow'])->name('my_customer.show');
    Route::post('/my-customer/{customer}/payments', [PageController::class, 'myCustomerStorePayment'])->name('my_customer.payments.store');
    Route::get('/my-customer/{customer}/order', [PageController::class, 'myCustomerOrderCreate'])->name('my_customer.order.create');
    Route::post('/my-customer/{customer}/order', [PageController::class, 'myCustomerOrderStore'])->name('my_customer.order.store');
    Route::get('/my-customer/{customer}/orders-quick-view', [PageController::class, 'myCustomerOrdersQuickView'])->name('my_customer.orders_quick_view');
    }); // end my-customer role group


    // My Truck Stations (sale / leader / manager only)
    Route::middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin')->group(function () {
    Route::get('/my-truck-stations', [PageController::class, 'myTruckStations'])->name('pages.my_truck_stations');
    Route::get('/my-truck-stations/list', [PageController::class, 'myTruckStationsAjax'])->name('pages.my_truck_stations.ajax');
    Route::get('/my-truck-stations/regions', [PageController::class, 'myTruckStationsRegions'])->name('pages.my_truck_stations.regions');
    Route::post('/my-truck-stations', [PageController::class, 'myTruckStationsStore'])->name('pages.my_truck_stations.store');
    Route::put('/my-truck-stations/{truckStation}', [PageController::class, 'myTruckStationsUpdate'])->name('pages.my_truck_stations.update');
    }); // end my-truck-stations role group

    // API: Truck Routes for customer create
    Route::get('/api/truck-routes', [PageController::class, 'apiTruckRoutes'])->name('api.truck_routes');

    // Address data AJAX endpoints
    Route::get('/api/provinces', [PageController::class, 'getProvinces'])->name('api.provinces');
    Route::get('/api/districts', [PageController::class, 'getDistricts'])->name('api.districts');
    Route::get('/api/wards', [PageController::class, 'getWards'])->name('api.wards');

    // Leader - duyệt đơn của sale trong team
    Route::get('/my-tearm-orders', [PageController::class, 'myTearmOrders'])->name('pages.my_tearm_orders');
    Route::get('/my-team-orders', [PageController::class, 'myTearmOrders'])->name('pages.my_team_orders');
    Route::post('/my-tearm-orders/auto-approve', [PageController::class, 'myTearmOrdersAutoApprove'])->name('pages.my_tearm_orders.auto_approve');

    // Site approve/reject (dùng cho my-team-orders, không qua permission middleware)
    Route::post('/site/orders/{order}/approve', [\App\Http\Controllers\OrderApprovalController::class, 'approve'])->name('site.orders.approve');
    Route::post('/site/orders/{order}/reject', [\App\Http\Controllers\OrderApprovalController::class, 'reject'])->name('site.orders.reject');

    // Manager - duyệt đơn của tất cả sale/leader
    Route::get('/all-tearm-orders', [PageController::class, 'allTearmOrders'])->name('pages.all_tearm_orders');
    Route::get('/all-team-orders', [PageController::class, 'allTearmOrders'])->name('pages.all_team_orders');
    Route::post('/all-tearm-orders/auto-approve', [PageController::class, 'allTearmOrdersAutoApprove'])->name('pages.all_tearm_orders.auto_approve');
    Route::get('/team-orders/{order}', [PageController::class, 'teamOrderDetail'])->name('pages.team_order_detail');
    Route::get('/team-orders/{order}/customer-orders', [PageController::class, 'teamOrderCustomerOrders'])->name('pages.team_order_customer_orders');
});


//  Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
//  Route::resource('products', ProductController::class)->middleware('auth');
/*
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/manager/dashboard', [ManagerController::class, 'index'])->name('manager.dashboard');
    Route::get('/staff/dashboard', [StaffController::class, 'index'])->name('staff.dashboard');
});
*/
 


/*
Route::resource('products', ProductController::class);
Route::resource('categories', CategoryController::class);
*/

// Quản lý giao dịch
Route::resource('transactions', TransactionController::class)->only(['index','create','store'])->middleware('permission');
Route::post('expense-types', [TransactionController::class, 'storeExpenseType'])->name('expense-types.store')->middleware('permission');

// Static Pages
Route::get('/gioi-thieu', [PageController::class, 'about'])->name('pages.about');
Route::get('/lien-he', [PageController::class, 'contact'])->name('pages.contact');
Route::post('/lien-he', [PageController::class, 'storeContact'])->name('pages.contact.store');
Route::get('/san-pham/{category:slug?}', [PageController::class, 'productsByCategory'])->name('pages.products_by_category');
Route::get('/danh-sach-san-pham/{category:slug?}', [PageController::class, 'productList'])->name('pages.product_list');
//Route::get('/product/{product:slug}', [PageController::class, 'productDetail'])->name('pages.product_detail');
Route::get('/variant/{variant:slug}', [PageController::class, 'variantDetail'])->name('pages.variant_detail');

Route::get('/my-profile', [PageController::class, 'myDashboard'])->name('pages.my_profile')->middleware('auth');
Route::post('/my-profile', [PageController::class, 'updateProfile'])->name('pages.update_profile')->middleware('auth');

// My Orders routes (sale / leader / manager only)
Route::middleware(['auth', 'role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin'])->group(function () {
    Route::get('/my-orders', [PageController::class, 'myOrders'])->name('pages.my_orders');
    Route::get('/my-orders/monitoring', [PageController::class, 'myOrdersMonitoring'])
        ->name('pages.my_orders.monitoring')
        ->middleware('permission:orders.monitoring');
    Route::get('/my-orders/daily-prices', [PageController::class, 'dailyProductPrices'])->name('pages.my_orders.daily_prices');
    Route::get('/my-orders/daily-inventories', [PageController::class, 'dailyInventories'])->name('pages.my_orders.daily_inventories');
    Route::get('/my-orders/customers/ajax', [PageController::class, 'myOrderCustomersAjax'])->name('site.orders.customers.ajax');
    // AJAX: Danh sách biến thể cho đơn hàng (my-orders/{order}/edit)
    Route::get('/my-orders/variants/ajax', [App\Http\Controllers\OrderAjaxController::class, 'variantsAjax'])->name('site.orders.variants.ajax');
    Route::get('/my-orders/{order}', [PageController::class, 'myOrderDetail'])->name('site.orders.show');
    Route::post('/my-orders/{order}/cancel', [OrderController::class, 'cancel'])->name('site.orders.cancel');
    Route::get('/my-orders/{order}/edit', [PageController::class, 'myOrderEdit'])->name('site.orders.edit');
    Route::put('/my-orders/{order}', [PageController::class, 'myOrderUpdate'])->name('site.orders.update');
    Route::get('/my-orders/{order}/adjustments/create', [\App\Http\Controllers\OrderAdjustmentController::class, 'create'])->name('site.order-adjustments.create');
    Route::post('/my-orders/{order}/adjustments', [\App\Http\Controllers\OrderAdjustmentController::class, 'store'])->name('site.order-adjustments.store');
    Route::get('/my-orders/{id}/copy', [PageController::class, 'copyOrder'])->name('site.orders.copy');
    Route::post('/my-orders/{order}/confirm-copy', [PageController::class, 'confirmCopyOrder'])->name('site.orders.confirm-copy');
}); // end my-orders role group

Route::middleware(['auth'])->group(function () {
    Route::get('/my-order-adjustments/{orderAdjustment}', [\App\Http\Controllers\OrderAdjustmentController::class, 'show'])->name('site.order-adjustments.show');
    Route::post('/my-order-adjustments/{orderAdjustment}/approve', [\App\Http\Controllers\OrderAdjustmentController::class, 'approve'])->name('site.order-adjustments.approve');
    Route::post('/my-order-adjustments/{orderAdjustment}/reject', [\App\Http\Controllers\OrderAdjustmentController::class, 'reject'])->name('site.order-adjustments.reject');
    Route::post('/my-order-adjustments/{orderAdjustment}/warehouse-confirm', [\App\Http\Controllers\OrderAdjustmentController::class, 'warehouseConfirm'])->name('site.order-adjustments.warehouse-confirm');
});

Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');

//Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');

// Posts
// Cart Routes
Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::get('/cart/checkout', [CartController::class, 'checkout'])->middleware('auth')->name('cart.checkout');
Route::post('/checkout/update-discount', [CartController::class, 'updateDiscount']);
Route::get('/cart/customers/search', [CartController::class, 'searchCustomers'])->middleware('auth')->name('cart.customers.search');
Route::post('/orders/store-from-cart', [OrderController::class, 'storeFromCart'])->middleware('auth')->name('orders.store_from_cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/remove/{id}', [CartController::class, 'remove']);
Route::patch('/cart/update/{id}', [CartController::class, 'updateQuantity'])->name('cart.update');

// Blog Routes
Route::get('/tin-tuc', [PostController::class, 'list'])->name('posts.list');
Route::get('/tin-tuc/chuyen-muc/{category:slug}', [PostController::class, 'category'])->name('posts.category');
Route::get('/tin-tuc/{post:slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/test-variant', function () {
    try {
        $variant = \App\Models\ProductVariant::factory()->create();
        return response()->json($variant);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');