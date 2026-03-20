<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RevenueReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'day');
        if (!in_array($type, ['day', 'month', 'range'], true)) {
            $type = 'day';
        }

        $today = Carbon::today();
        $dayInput = $request->input('day', $today->toDateString());
        $monthInput = $request->input('month', $today->format('Y-m'));
        $fromInput = $request->input('from_date', $today->copy()->subDays(6)->toDateString());
        $toInput = $request->input('to_date', $today->toDateString());

        [$startDate, $endDate] = $this->resolveDateRange($type, $dayInput, $monthInput, $fromInput, $toInput);

        $baseQuery = Transaction::query()
            ->whereIn('type', ['payment', 'refund'])
            ->whereBetween('created_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ]);

        $income = (float) (clone $baseQuery)->where('type', 'payment')->sum('amount');
        $refund = (float) (clone $baseQuery)->where('type', 'refund')->sum('amount');
        $netRevenue = $income - $refund;

        $dailySummary = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw("SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as refund")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $transactions = (clone $baseQuery)
            ->with(['order', 'customer'])
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('reports.revenue', [
            'type' => $type,
            'dayInput' => $dayInput,
            'monthInput' => $monthInput,
            'fromInput' => $fromInput,
            'toInput' => $toInput,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'income' => $income,
            'refund' => $refund,
            'netRevenue' => $netRevenue,
            'dailySummary' => $dailySummary,
            'transactions' => $transactions,
        ]);
    }

    private function resolveDateRange(string $type, string $dayInput, string $monthInput, string $fromInput, string $toInput): array
    {
        $today = Carbon::today();

        if ($type === 'month') {
            try {
                $month = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
            } catch (\Throwable $e) {
                $month = $today->copy()->startOfMonth();
            }

            return [$month->copy()->startOfDay(), $month->copy()->endOfMonth()->endOfDay()];
        }

        if ($type === 'range') {
            try {
                $fromDate = Carbon::parse($fromInput)->startOfDay();
            } catch (\Throwable $e) {
                $fromDate = $today->copy()->subDays(6)->startOfDay();
            }

            try {
                $toDate = Carbon::parse($toInput)->endOfDay();
            } catch (\Throwable $e) {
                $toDate = $today->copy()->endOfDay();
            }

            if ($fromDate->greaterThan($toDate)) {
                [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
            }

            return [$fromDate, $toDate];
        }

        try {
            $day = Carbon::parse($dayInput);
        } catch (\Throwable $e) {
            $day = $today;
        }

        return [$day->copy()->startOfDay(), $day->copy()->endOfDay()];
    }
}
