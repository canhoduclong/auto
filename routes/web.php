<?php 
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
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
use App\Http\Controllers\PostController; 
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MyCustomerController;
use App\Http\Controllers\CartController;



Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/variants', [HomeController::class, 'variants'])->name('site.variants');

// Auth pages
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 lần/phút chống brute-force
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware(['auth'])->group(function () {
    // AJAX lấy tổng tiền đơn hàng
    Route::get('orders/ajax/total', [OrderAjaxController::class, 'total'])->name('orders.ajax.total');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Quản lý sản phẩm
    Route::resource('products', ProductController::class)->middleware('permission');
    Route::get('products/{product}/quick-edit-form', [ProductController::class, 'getQuickEditForm'])->name('products.getQuickEditForm');
    // Quản trị biến thể sản phẩm
    Route::resource('product-variants', ProductVariantController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::post('product-variants/bulk-delete', [ProductVariantController::class, 'bulkDelete'])->name('product-variants.bulk-delete');
    Route::post('product-variants/{variant}/duplicate', [ProductVariantController::class, 'duplicate'])->name('product-variants.duplicate')->middleware('permission');

    // AJAX popup chọn khách hàng
    Route::get('customers/popup/search', [CustomerPopupController::class, 'search'])->name('customers.popup.search')->middleware('auth');
    Route::post('customers/popup/store', [CustomerPopupController::class, 'store'])->name('customers.popup.store')->middleware('auth');



    Route::get('variants/{variant}/edit-price', [ProductVariantPriceController::class, 'edit'])->name('variants.edit-price');
    Route::put('variants/{variant}/update-price', [ProductVariantPriceController::class, 'update'])->name('variants.update-price');

    // Lịch sử giá (AJAX)
    Route::get('variants/{id}/price-history', [ProductVariantPriceController::class, 'priceHistory'])->name('variants.price-history');
    // Cập nhật giá mới (AJAX)
    Route::post('variants/{id}/update-price', [ProductVariantPriceController::class, 'updatePrice'])->name('variants.update-price-ajax');

    // Popup gallery chọn ảnh cho biến thể
    Route::get('variants/image-library', [MediaController::class, 'variantImageLibrary'])->name('variants.image-library');
    
    
    Route::post('/ai/generate-description', [AIController::class, 'generateDescription'])->name('ai.generateDescription');


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
    Route::resource('orders', OrderController::class)->middleware('permission');

    // Quản lý danh mục
    Route::resource('categories', CategoryController::class)->middleware('permission');

    // Quản lý vai trò
    Route::resource('roles', RoleController::class)->middleware('permission'); 

    // Quản lý quyền
    // Route::resource('permissions', PermissionController::class);//->middleware('permission');

    Route::resource('permissions', PermissionController::class)->middleware('permission');

    // Quản lý người dùng
    Route::resource('users', UserController::class)->middleware('permission');

    // Ví dụ: khi sau này bạn thêm Customer
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export')->middleware('permission');
    Route::get('customers/import', [CustomerController::class, 'importForm'])->name('customers.import.form')->middleware('permission');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import')->middleware('permission');
    Route::resource('customers', CustomerController::class)->middleware('permission');

    // Xóa nhiều khách hàng
    Route::post('customers/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customers.bulkDelete')->middleware('permission');
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
    Route::resource('order-returns', OrderReturnController::class)->middleware('permission');
    
    // Route list toàn bộ địa chỉ (không cần customerId)
    Route::get('customers/list/addresses', [CustomerAddressController::class, 'list'])
    ->name('customers.addresses.list')->middleware('permission');
    //->middleware('permission:addresses.view'); // nếu bạn dùng middleware permission

    //Route::resource('customeraddress', CustomerAddressController::class)->middleware('permission');
    Route::resource('customers.addresses', CustomerAddressController::class)->middleware('permission');
    
    // Media 
    //Route::resource('media', MediaController::class);
    Route::resource('media', MediaController::class)->parameters([
        'media' => 'media'
    ]);
    
    Route::get('/media/library/popup', [MediaController::class, 'popup'])->name('media.library.popup');
    Route::post('/media/popup/store', [MediaController::class, 'popupStore'])->name('media.popup.store');

    Route::get('/media/gallery/popup', [MediaController::class, 'popupGallery'])->name('media.gallery.popup');
    Route::post('/media/gallery/store', [MediaController::class, 'storeGallery'])->name('media.gallery.store');

    

    // Route::get('{type}/{id}/media', [MediaController::class, 'index'])->name('media.index');
    // Route::post('{type}/{id}/media', [MediaController::class, 'store'])->name('media.store');
    // Route::get('media/{media}', [MediaController::class, 'show'])->name('media.show');
    // Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');


    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout'); 

    Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::resource('posts', PostController::class);
        Route::resource('post-categories', PostCategoryController::class);
        Route::resource('pages', PageController::class);
        Route::resource('brands', \App\Http\Controllers\BrandController::class);
    });

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // My Customer Page
    Route::get('/my-customer', [PageController::class, 'myCustomer'])->name('pages.my_customer');
    Route::get('/my-customer/create', [PageController::class, 'myCustomerCreate'])->name('my_customer.create');
    Route::post('/my-customer', [PageController::class, 'myCustomerStore'])->name('my_customer.store');
    Route::get('/my-customer/{customer}/edit', [PageController::class, 'myCustomerEdit'])->name('my_customer.edit');
    Route::put('/my-customer/{customer}', [PageController::class, 'myCustomerUpdate'])->name('my_customer.update');
    Route::delete('/my-customer/{customer}', [PageController::class, 'myCustomerDestroy'])->name('my_customer.destroy');
    Route::post('/my-customer/bulk-delete', [PageController::class, 'myCustomerBulkDelete'])->name('my_customer.bulk_delete');
    Route::get('/my-customer/import', [PageController::class, 'myCustomerImportForm'])->name('my_customer.import_form');
    Route::post('/my-customer/import', [PageController::class, 'myCustomerImport'])->name('my_customer.import');
    Route::get('/my-customer/{customer}', [PageController::class, 'myCustomerShow'])->name('my_customer.show');
    Route::get('/my-customer/{customer}/order', [PageController::class, 'myCustomerOrderCreate'])->name('my_customer.order.create');
    Route::post('/my-customer/{customer}/order', [PageController::class, 'myCustomerOrderStore'])->name('my_customer.order.store');
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

// Static Pages
Route::get('/gioi-thieu', [PageController::class, 'about'])->name('pages.about');
Route::get('/lien-he', [PageController::class, 'contact'])->name('pages.contact');
Route::post('/lien-he', [PageController::class, 'storeContact'])->name('pages.contact.store');
Route::get('/san-pham/{category:slug?}', [PageController::class, 'productsByCategory'])->name('pages.products_by_category');
Route::get('/danh-sach-san-pham/{category:slug?}', [PageController::class, 'productList'])->name('pages.product_list');
Route::get('/product/{product:slug}', [PageController::class, 'productDetail'])->name('pages.product_detail');
Route::get('/variant/{variant:slug}', [PageController::class, 'variantDetail'])->name('pages.variant_detail');
Route::get('/my-profile', [PageController::class, 'myDashboard'])->name('pages.my_dashboard');
Route::post('/my-profile', [PageController::class, 'updateProfile'])->name('pages.update_profile');
Route::get('/my-orders', [PageController::class, 'myOrders'])->name('pages.my_orders');
Route::get('/my-orders/{order}', [PageController::class, 'myOrderDetail'])->name('site.orders.show');

Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');

//Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');

// Posts
// Cart Routes
Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::get('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::post('/orders/store-from-cart', [OrderController::class, 'storeFromCart'])->name('orders.store_from_cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
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