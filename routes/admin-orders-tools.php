<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/admin/orders/sync-daily-sequence', function () {
        $date = request('date') ?? now()->toDateString();
        \App\Http\Controllers\OrderController::syncDailySequenceAndStockSufficiency($date);
        return Redirect::back()->with('success', 'Đã đồng bộ lại số thứ tự ưu tiên cho đơn ngày ' . $date);
    })->name('admin.orders.sync_daily_sequence');
});
