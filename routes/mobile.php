<?php

use App\Http\Controllers\Mobile\SaleMobileController;
use App\Http\Controllers\Mobile\WarehouseMobileController;
use App\Http\Controllers\Mobile\ShipperMobileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'mobile.role.redirect'])->prefix('m')->name('mobile.')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        if ($user?->hasRole('warehouse') || $user?->hasRole('admin')) {
            return redirect()->route('mobile.warehouse.home');
        }

        if ($user?->hasRole('shipper') || $user?->hasRole('ship')) {
            return redirect()->route('mobile.shipper.home');
        }

        return redirect()->route('mobile.sale.home');
    })->name('home');

    Route::middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin')->group(function () {
        Route::get('/sale', [SaleMobileController::class, 'index'])->name('sale.home');

        Route::prefix('/api/sale')->name('api.sale.')->group(function () {
            Route::get('/customers', [SaleMobileController::class, 'customers'])->name('customers');
            Route::get('/orders', [SaleMobileController::class, 'orders'])->name('orders');
            Route::get('/metrics', [SaleMobileController::class, 'metrics'])->name('metrics');
        });
    });

    Route::middleware('role:warehouse,admin')->group(function () {
        Route::get('/warehouse', [WarehouseMobileController::class, 'index'])->name('warehouse.home');

        Route::prefix('/api/warehouse')->name('api.warehouse.')->group(function () {
            Route::get('/orders', [WarehouseMobileController::class, 'orders'])->name('orders');
            Route::get('/orders/{order}', [WarehouseMobileController::class, 'orderDetail'])->name('orders.detail');
            Route::post('/orders/{order}/start-packing', [WarehouseMobileController::class, 'startPacking'])->name('orders.start-packing');
        });
    });

    Route::middleware('role:shipper,ship,admin')->group(function () {
        Route::get('/shipper', [ShipperMobileController::class, 'index'])->name('shipper.home');

        Route::prefix('/api/shipper')->name('api.shipper.')->group(function () {
            Route::get('/orders/today', [ShipperMobileController::class, 'todayOrders'])->name('today');
            Route::get('/orders/{order}', [ShipperMobileController::class, 'orderDetail'])->name('orders.detail');
            Route::post('/orders/{order}/complete', [ShipperMobileController::class, 'completeOrder'])->name('orders.complete');
            Route::post('/orders/{order}/failed', [ShipperMobileController::class, 'failOrder'])->name('orders.failed');
        });
    });
});
