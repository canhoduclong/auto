<?php

namespace App\Http\Controllers;

use App\Exports\OrderFinancialBreakdownExport;
use App\Models\Order;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

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

    public function export(Request $request)
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

        $orders = Order::query()
            ->with(['customer:id,name', 'user:id,name'])
            ->whereBetween('created_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->latest()
            ->get();

        $hasSubtotalAmount = Schema::hasColumn('orders', 'subtotal_amount');
        $hasItemDiscountTotal = Schema::hasColumn('orders', 'item_discount_total');
        $hasExtraDiscountTotal = Schema::hasColumn('orders', 'extra_discount_total');
        $hasOrderDiscount = Schema::hasColumn('orders', 'order_discount');
        $hasTotalDiscount = Schema::hasColumn('orders', 'total_discount');

        $rows = $orders->map(function (Order $order) use (
            $hasSubtotalAmount,
            $hasItemDiscountTotal,
            $hasExtraDiscountTotal,
            $hasOrderDiscount,
            $hasTotalDiscount
        ) {
            $subtotalAmount = $hasSubtotalAmount ? (float) ($order->subtotal_amount ?? 0) : (float) ($order->subtotal ?? 0);
            $itemDiscountTotal = $hasItemDiscountTotal ? (float) ($order->item_discount_total ?? 0) : 0.0;

            $extraDiscountTotal = 0.0;
            if ($hasExtraDiscountTotal) {
                $extraDiscountTotal = (float) ($order->extra_discount_total ?? 0);
            } elseif ($hasOrderDiscount) {
                $extraDiscountTotal = (float) ($order->order_discount ?? 0);
            }

            $combinedDiscount = $itemDiscountTotal + $extraDiscountTotal;
            if ($combinedDiscount <= 0 && $hasTotalDiscount) {
                $combinedDiscount = (float) ($order->total_discount ?? 0);
            }

            $statusLabel = is_string($order->status) && $order->status !== ''
                ? $order->status
                : ((string) ($order->status->value ?? $order->status ?? '-'));
            $paymentStatusLabel = is_string($order->payment_status) && $order->payment_status !== ''
                ? $order->payment_status
                : ((string) ($order->payment_status->value ?? $order->payment_status ?? '-'));
            $deliveryStatusLabel = is_string($order->delivery_status) && $order->delivery_status !== ''
                ? $order->delivery_status
                : ((string) ($order->delivery_status->value ?? $order->delivery_status ?? '-'));

            return [
                $order->code ?: ('#' . $order->id),
                optional($order->created_at)->format('d/m/Y H:i'),
                $order->customer?->name ?? '-',
                $order->user?->name ?? '-',
                $subtotalAmount,
                $itemDiscountTotal,
                $extraDiscountTotal,
                $combinedDiscount,
                (float) ($order->total ?? 0),
                $statusLabel,
                $paymentStatusLabel,
                $deliveryStatusLabel,
            ];
        });

        $filename = sprintf(
            'bao-cao-tai-chinh-don-hang-%s-den-%s.xlsx',
            $startDate->format('Ymd'),
            $endDate->format('Ymd')
        );

        return Excel::download(new OrderFinancialBreakdownExport($rows), $filename);
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
