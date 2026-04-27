<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderScheduleRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class OrderScheduleRunController extends Controller
{
    public function index(Request $request): View
    {
        $runs = OrderScheduleRun::with('triggeredBy:id,name')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'total_runs'       => OrderScheduleRun::count(),
            'total_evaluated'  => OrderScheduleRun::sum('evaluated'),
            'total_generated'  => OrderScheduleRun::sum('generated'),
            'total_need_review'=> OrderScheduleRun::sum('need_review'),
            'last_run'         => OrderScheduleRun::latest()->first(),
        ];

        return view('admin.order-schedule-runs.index', compact('runs', 'stats'));
    }

    public function runNow(Request $request): RedirectResponse
    {
        try {
            Artisan::call('order-schedules:evaluate-today', [
                '--triggered-by' => (string) auth()->id(),
                '--trigger-type' => 'manual',
            ]);
            return redirect()->route('admin.order-schedule-runs.index')
                ->with('success', 'Lệnh đã chạy xong. Kết quả đã được ghi vào lịch sử.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.order-schedule-runs.index')
                ->with('error', 'Lỗi khi chạy lệnh: ' . $e->getMessage());
        }
    }
}
