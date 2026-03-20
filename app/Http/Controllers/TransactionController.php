<?php
namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Transaction;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Transaction::class);

        $today = Carbon::today();
        $period = $request->input('period', 'day');

        if (!in_array($period, ['day', 'week', 'month', 'range'], true)) {
            $period = 'day';
        }

        $dayInput = $request->input('day', $today->toDateString());
        $weekInput = $request->input('week', $today->format('o-\\WW'));
        $monthInput = $request->input('month', $today->format('Y-m'));
        $fromDateInput = $request->input('from_date', $today->copy()->subDays(6)->toDateString());
        $toDateInput = $request->input('to_date', $today->toDateString());

        [$startDate, $endDate] = $this->resolvePeriodDates($period, $dayInput, $weekInput, $monthInput, $fromDateInput, $toDateInput);

        $baseQuery = Transaction::query()
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        if ($request->filled('type')) {
            $baseQuery->where('type', $request->input('type'));
        }

        $incomeTypes = ['payment', 'extra_income'];
        $expenseTypes = ['refund', 'fee', 'extra_expense'];

        $totalIncome = (float) (clone $baseQuery)->whereIn('type', $incomeTypes)->sum('amount');
        $totalExpense = (float) (clone $baseQuery)->whereIn('type', $expenseTypes)->sum('amount');
        $netAmount = $totalIncome - $totalExpense;
        $totalTransactions = (clone $baseQuery)->count();

        $summaryByType = (clone $baseQuery)
            ->select('type', DB::raw('COUNT(*) as total_rows'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->orderBy('type')
            ->get();

        $summaryByDay = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw("SUM(CASE WHEN type IN ('payment', 'extra_income') THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type IN ('refund', 'fee', 'extra_expense') THEN amount ELSE 0 END) as expense")
            ->selectRaw('COUNT(*) as total_rows')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $transactions = (clone $baseQuery)
            ->with(['order', 'customer'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('transactions.index', [
            'transactions' => $transactions,
            'period' => $period,
            'dayInput' => $dayInput,
            'weekInput' => $weekInput,
            'monthInput' => $monthInput,
            'fromDateInput' => $fromDateInput,
            'toDateInput' => $toDateInput,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netAmount' => $netAmount,
            'totalTransactions' => $totalTransactions,
            'summaryByType' => $summaryByType,
            'summaryByDay' => $summaryByDay,
        ]);
    }

    private function resolvePeriodDates(
        string $period,
        string $dayInput,
        string $weekInput,
        string $monthInput,
        string $fromDateInput,
        string $toDateInput
    ): array {
        $today = Carbon::today();

        if ($period === 'week') {
            if (preg_match('/^(\d{4})-W(\d{2})$/', $weekInput, $matches)) {
                $year = (int) $matches[1];
                $week = (int) $matches[2];
                $start = Carbon::now()->setISODate($year, $week)->startOfWeek();
                $end = $start->copy()->endOfWeek();
                return [$start, $end];
            }

            return [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()];
        }

        if ($period === 'month') {
            try {
                $month = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
                return [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];
            } catch (\Throwable $e) {
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
            }
        }

        if ($period === 'range') {
            try {
                $fromDate = Carbon::parse($fromDateInput)->startOfDay();
            } catch (\Throwable $e) {
                $fromDate = $today->copy()->subDays(6)->startOfDay();
            }

            try {
                $toDate = Carbon::parse($toDateInput)->endOfDay();
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

    public function create(Request $request)
    {
        $this->authorize('create', Transaction::class);
        $orders = Order::all();
        $customers = Customer::all();
        return view('transactions.create', compact('orders', 'customers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Transaction::class);
        $data = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'customer_id' => 'nullable|exists:customers,id',
            'amount' => 'required|numeric',
            'type' => 'required|string',
            'method' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:255',
        ]);
        $transaction = Transaction::create($data);

        if ($transaction->order_id) {
            $order = $transaction->order;
            $totalPaid = $order->transactions()->where('type', 'payment')->sum('amount') - $order->transactions()->where('type', 'refund')->sum('amount');
            $order->amount_paid = $totalPaid;

            if ($totalPaid >= $order->total) {
                $order->payment_status = 'paid';
            } elseif ($totalPaid > 0) {
                $order->payment_status = 'partially_paid';
            } else {
                $order->payment_status = 'unpaid';
            }

            if ($order->status === Order::STATUS_ORDER_PLACED) {
                $order->status = Order::STATUS_ORDER_CONFIRMED;
            }
            
            $order->save();
        }

        return redirect()->route('transactions.index')->with('success', __('transactions.messages.created'));
    }
}