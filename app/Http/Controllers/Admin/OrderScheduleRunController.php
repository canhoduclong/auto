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
        $commandNames = [
            'order-drafts:process-automation',
            'order-schedules:process-daily-rules',
            'order-schedules:evaluate-today',
        ];

        $activeCommand = (string) $request->input('command', 'all');
        if ($activeCommand !== 'all' && !in_array($activeCommand, $commandNames, true)) {
            $activeCommand = 'all';
        }

        $runs = OrderScheduleRun::with('triggeredBy:id,name')
            ->when($activeCommand !== 'all', fn ($q) => $q->where('command_name', $activeCommand))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'total_runs'       => OrderScheduleRun::count(),
            'total_evaluated'  => OrderScheduleRun::sum('evaluated'),
            'total_generated'  => OrderScheduleRun::sum('generated'),
            'total_need_review'=> OrderScheduleRun::sum('need_review'),
            'last_run'         => OrderScheduleRun::latest()->first(),
            'by_command' => collect($commandNames)->mapWithKeys(function ($commandName) {
                $query = OrderScheduleRun::where('command_name', $commandName);

                return [$commandName => [
                    'total_runs' => (clone $query)->count(),
                    'total_evaluated' => (clone $query)->sum('evaluated'),
                    'total_generated' => (clone $query)->sum('generated'),
                    'total_need_review' => (clone $query)->sum('need_review'),
                    'last_run' => (clone $query)->latest()->first(),
                ]];
            })->all(),
        ];

        return view('admin.order-schedule-runs.index', compact('runs', 'stats', 'activeCommand'));
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

    public function runDailyRulesNow(Request $request): RedirectResponse
    {
        try {
            Artisan::call('order-schedules:process-daily-rules', [
                '--triggered-by' => (string) auth()->id(),
                '--trigger-type' => 'manual',
            ]);

            return redirect()->route('admin.order-schedule-runs.index', ['command' => 'order-schedules:process-daily-rules'])
                ->with('success', 'Đã chạy lệnh xử lý lên đơn mỗi ngày. Kết quả đã được ghi vào lịch sử.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.order-schedule-runs.index', ['command' => 'order-schedules:process-daily-rules'])
                ->with('error', 'Lỗi khi chạy lệnh lên đơn mỗi ngày: ' . $e->getMessage());
        }
    }
}
