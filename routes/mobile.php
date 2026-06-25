<?php

use App\Http\Controllers\Mobile\SaleMobileController;
use App\Http\Controllers\Mobile\WarehouseMobileController;
use App\Http\Controllers\Mobile\ShipperMobileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('m')->name('mobile.')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        if ($user?->hasRole('package')) {
            return redirect()->route('mobile.package.home');
        }

        if ($user?->hasRole('warehouse') || $user?->hasRole('admin')) {
            return redirect()->route('mobile.warehouse.home');
        }

        if ($user?->hasRole('shipper') || $user?->hasRole('ship') || $user?->hasRole('manager_shipper')) {
            return redirect()->route('mobile.shipper.home');
        }

        if ($user?->hasRole('account') || $user?->hasRole('accountant') || $user?->hasRole('accounting')) {
            return redirect()->route('mobile.accounting.home');
        }

        if ($user?->hasRole('ceo')) {
            return redirect()->route('mobile.ceo.home');
        }

        return redirect()->route('mobile.sale.home');
    })->name('home');

    Route::middleware('role:account,accountant,accounting,admin')->group(function () {
        Route::get('/accounting', fn () => view('mobile.accounting.index'))->name('accounting.home');
    });

    Route::middleware('role:ceo,admin')->group(function () {
        Route::get('/ceo', fn () => view('mobile.ceo.index'))->name('ceo.home');
    });

    Route::middleware('role:sale,leader,leader_sale,sale_manager,manager,manager_sale,admin')->group(function () {
        Route::get('/sale', [SaleMobileController::class, 'index'])->name('sale.home');

        Route::prefix('/api/sale')->name('api.sale.')->group(function () {
            Route::get('/customers', [SaleMobileController::class, 'customers'])->name('customers');
            Route::get('/products', [SaleMobileController::class, 'products'])->name('products');
            Route::post('/products/{variant}/preference', [SaleMobileController::class, 'updateProductPreference'])->name('products.preference');
            Route::get('/orders', [SaleMobileController::class, 'orders'])->name('orders');
            Route::post('/orders', [SaleMobileController::class, 'storeOrder'])->name('orders.store');
            Route::get('/metrics', [SaleMobileController::class, 'metrics'])->name('metrics');
        });
    });

    Route::middleware('role:warehouse,package,admin')->group(function () {
        Route::get('/warehouse', [WarehouseMobileController::class, 'index'])->name('warehouse.home');
        Route::get('/package', [WarehouseMobileController::class, 'index'])->name('package.home');

        Route::prefix('/api/warehouse')->name('api.warehouse.')->group(function () {
            Route::get('/orders', [WarehouseMobileController::class, 'orders'])->name('orders');
            Route::get('/orders/{order}', [WarehouseMobileController::class, 'orderDetail'])->name('orders.detail');
            Route::post('/orders/{order}/start-packing', [WarehouseMobileController::class, 'startPacking'])->name('orders.start-packing');
        });
    });

    Route::middleware('role:shipper,ship,manager_shipper,admin')->group(function () {
        Route::get('/shipper', [ShipperMobileController::class, 'index'])->name('shipper.home');

        Route::prefix('/api/shipper')->name('api.shipper.')->group(function () {
            Route::get('/orders/today', [ShipperMobileController::class, 'todayOrders'])->name('today');
            Route::get('/orders/{order}', [ShipperMobileController::class, 'orderDetail'])->name('orders.detail');
            Route::post('/orders/{order}/complete', [ShipperMobileController::class, 'completeOrder'])->name('orders.complete');
            Route::post('/orders/{order}/failed', [ShipperMobileController::class, 'failOrder'])->name('orders.failed');
        });
    });
});
