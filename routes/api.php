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
use App\Http\Controllers\AdminTestController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\RevenueReportController;
use App\Http\Controllers\OrderMonitoringController;
use App\Http\Controllers\WarehouseDashboardController;
use App\Http\Controllers\ShipperDashboardController;


// API: trả về danh sách biến thể của sản phẩm, kèm giá mới nhất từ ProductPriceLog
use App\Models\Product;
use App\Models\ProductPriceLog;
Route::get('product/{product}/variants', function($productId) {
    $product = Product::with(['variants' => function($q) {
        $q->select('id', 'product_id', 'name', 'sku');
    }])->findOrFail($productId);
    $variants = $product->variants->map(function($variant) {
        $latestPrice = ProductPriceLog::where('product_variant_id', $variant->id)
            ->orderByDesc('applied_at')
            ->orderByDesc('id')
            ->first();
        return [
            'id' => $variant->id,
            'name' => $variant->name,
            'sku' => $variant->sku,
            'latest_price' => $latestPrice ? $latestPrice->price : null,
            'latest_price_date' => $latestPrice ? $latestPrice->applied_at : null,
        ];
    });
    return response()->json($variants);
});


?>