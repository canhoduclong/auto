<?php

use App\Http\Controllers\Api\Mobile\AppVersionController;
use App\Http\Controllers\Api\Mobile\AuthApiController;
use App\Http\Controllers\Api\Mobile\NotificationApiController;
use App\Http\Controllers\Api\Mobile\RoleScreenApiController;
use App\Http\Controllers\Api\Mobile\ShipperApiController;
use App\Http\Controllers\Api\Mobile\SaleApiController;
use App\Http\Controllers\Api\Mobile\WarehouseApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/auth/login', [AuthApiController::class, 'login']);
        Route::post('/auth/google', [AuthApiController::class, 'googleLogin']);
    });
    Route::get('/app-version', [AppVersionController::class, 'show']);

    Route::middleware(['mobile.api', 'mobile.api.log', 'throttle:120,1'])->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me']);
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::post('/auth/refresh', [AuthApiController::class, 'refresh']);
        Route::post('/auth/switch-role', [AuthApiController::class, 'switchRole']);
        Route::get('/auth/sessions', [AuthApiController::class, 'sessions']);
        Route::delete('/auth/sessions/{sessionId}', [AuthApiController::class, 'revokeSession']);
        Route::get('/notifications', [NotificationApiController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllAsRead']);
        Route::post('/notifications/{notificationId}/read', [NotificationApiController::class, 'markAsRead']);
        Route::get('/screens/{layout}/{key}', [RoleScreenApiController::class, 'show']);

        Route::prefix('shipper')->group(function () {
            Route::get('/dashboard', [ShipperApiController::class, 'dashboard']);
            Route::get('/delivery-schedules', [ShipperApiController::class, 'deliverySchedules']);
            Route::post('/delivery-schedules/confirm', [ShipperApiController::class, 'confirmDeliverySchedule']);
            Route::post('/delivery-schedules/reject', [ShipperApiController::class, 'rejectDeliverySchedule']);
            Route::get('/available-orders', [ShipperApiController::class, 'availableOrders']);
            Route::get('/accepted-orders', [ShipperApiController::class, 'acceptedOrders']);
            Route::post('/orders/{order}/accept', [ShipperApiController::class, 'acceptOrder']);
            Route::get('/warehouses', [ShipperApiController::class, 'warehouses']);
            Route::post('/orders/{order}/return', [ShipperApiController::class, 'returnOrder']);
            Route::post('/assignments/{order}/assign/{shipper}', [ShipperApiController::class, 'assignOrder']);
            Route::post('/assignments/{order}/unassign', [ShipperApiController::class, 'unassignOrder']);
            Route::post('/assignments/create-schedules', [ShipperApiController::class, 'createDeliverySchedules']);
            Route::get('/my-orders', [ShipperApiController::class, 'myOrders']);
            Route::get('/history', [ShipperApiController::class, 'history']);
            Route::post('/orders/{order}/status', [ShipperApiController::class, 'updateStatus']);
            Route::post('/orders/{order}/complete-delivery', [ShipperApiController::class, 'completeDelivery']);
            Route::post('/orders/{order}/upload-proof', [ShipperApiController::class, 'uploadProof']);
            Route::post('/location', [ShipperApiController::class, 'updateLocation']);
            Route::get('/notifications', [ShipperApiController::class, 'notifications']);
        });

        Route::prefix('warehouse')->group(function () {
            Route::get('/dashboard', [WarehouseApiController::class, 'dashboard']);
            Route::get('/orders', [WarehouseApiController::class, 'orders']);
            Route::post('/orders/{order}/start-packing', [WarehouseApiController::class, 'startPacking']);
            Route::post('/orders/{order}/undo-start-packing', [WarehouseApiController::class, 'undoStartPacking']);
            Route::post('/orders/{order}/complete-packing', [WarehouseApiController::class, 'completePacking']);
            Route::post('/orders/{order}/logistics', [WarehouseApiController::class, 'updateLogistics']);
            Route::post('/orders/{order}/request-adjustment', [WarehouseApiController::class, 'requestAdjustment']);
            Route::get('/inventory', [WarehouseApiController::class, 'inventory']);
            Route::get('/products', [WarehouseApiController::class, 'products']);
            Route::get('/returns', [WarehouseApiController::class, 'returns']);
            Route::post('/returns/{orderReturn}/receive', [WarehouseApiController::class, 'receiveReturn']);
            Route::get('/tasks', [WarehouseApiController::class, 'tasks']);
            Route::get('/scan-lookup', [WarehouseApiController::class, 'scanLookup']);
            Route::get('/notifications', [WarehouseApiController::class, 'notifications']);
        });

        Route::prefix('sale')->group(function () {
            Route::get('/customers', [SaleApiController::class, 'customers']);
            Route::get('/customers/form-options', [SaleApiController::class, 'customerFormOptions']);
            Route::get('/customers/check-duplicate', [SaleApiController::class, 'checkCustomerDuplicate']);
            Route::post('/customers', [SaleApiController::class, 'storeCustomer']);
            Route::get('/customers/{customerId}', [SaleApiController::class, 'customer']);
            Route::put('/customers/{customer}', [SaleApiController::class, 'updateCustomer']);
            Route::delete('/customers/{customer}', [SaleApiController::class, 'deleteCustomer']);
            Route::post('/customers/{customerId}/restore', [SaleApiController::class, 'restoreCustomer']);
            Route::get('/products', [SaleApiController::class, 'products']);
            Route::post('/customers/{customer}/orders', [SaleApiController::class, 'storeOrder']);
            Route::get('/orders', [SaleApiController::class, 'orders']);
            Route::get('/orders/{order}', [SaleApiController::class, 'order']);
            Route::put('/orders/{order}', [SaleApiController::class, 'updateOrder']);
            Route::post('/orders/{orderId}/copy', [SaleApiController::class, 'copyOrder']);
            Route::post('/orders/{order}/confirm-copy', [SaleApiController::class, 'confirmCopy']);
            Route::post('/orders/{order}/cancel', [SaleApiController::class, 'cancelOrder']);
            Route::post('/orders/{order}/trash', [SaleApiController::class, 'trashOrder']);
            Route::get('/approvals/{scope}', [SaleApiController::class, 'approvals']);
            Route::post('/approvals/{order}/approve', [SaleApiController::class, 'approve']);
            Route::post('/approvals/{order}/reject', [SaleApiController::class, 'reject']);
        });
    });
});
