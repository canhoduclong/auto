<?php

namespace App\Http\Controllers;

use App\Services\CustomerDebtSheetService;
use App\Services\SupplierSheetDebtService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SupplierSheetDebtController extends Controller
{
    public function index(Request $request, SupplierSheetDebtService $service, CustomerDebtSheetService $sheets)
    {
        [$from, $to] = $this->period($request);
        $filters = $request->validate([
            'search' => 'nullable|string|max:150',
            'per_page' => 'nullable|in:25,50,100',
            'sort' => 'nullable|in:name,count,amount,paid,balance,unverified,period_amount,period_paid,period_balance',
            'direction' => 'nullable|in:asc,desc',
        ]);
        $sort = $filters['sort'] ?? 'balance';
        $direction = $filters['direction'] ?? ($sort === 'name' ? 'asc' : 'desc');
        $search = $sheets->normalize($filters['search'] ?? '');
        $pins = $service->pinnedSuppliers();
        $snapshot = $service->snapshot();
        $suppliers = collect($snapshot['rows'] ?? [])->groupBy('supplier_key')
            ->filter(fn ($rows) => $search === '' || str_contains($sheets->normalize($rows->first()['supplier']), $search))
            ->map(function ($rows, $key) use ($from, $to, $pins) {
                $period = $this->totals($this->inPeriod($rows, $from, $to));

                return $this->totals($rows) + [
                    'key' => $key, 'name' => $rows->first()['supplier'], 'pinned' => isset($pins[$key]),
                    'period_count' => $period['count'], 'period_amount' => $period['amount'],
                    'period_paid' => $period['paid'], 'period_balance' => $period['balance'],
                ];
            })
            ->filter(fn ($supplier) => $supplier['period_count'] > 0)
            ->sortBy([['pinned', 'desc'], [$sort, $direction], ['name', 'asc'], ['key', 'asc']])->values();

        return view('supplier-debt.index', ['suppliers' => $this->paginate($suppliers, $request, 'page', (int) ($filters['per_page'] ?? 50)), 'loadedAt' => $snapshot['loaded_at'] ?? null, 'from' => $from, 'to' => $to, 'sort' => $sort, 'direction' => $direction]);
    }

    public function updatePin(Request $request, string $supplier, SupplierSheetDebtService $service)
    {
        $data = $request->validate(['pinned' => 'required|boolean']);
        abort_unless(collect($service->snapshot()['rows'] ?? [])->contains('supplier_key', $supplier), 404);

        $service->setPinned($supplier, (bool) $data['pinned']);

        return redirect()->route('customer-debt.suppliers.index', $request->only(['range', 'from', 'to', 'search', 'per_page', 'sort', 'direction']), 303);
    }

    public function show(Request $request, string $supplier, SupplierSheetDebtService $service)
    {
        [$from, $to] = $this->period($request);
        $rows = collect($service->snapshot()['rows'] ?? [])->where('supplier_key', $supplier);
        abort_if($rows->isEmpty(), 404);
        $periodRows = $this->inPeriod($rows, $from, $to)->sortBy([['date', 'desc'], ['row', 'desc']])->values();
        $allTotals = $this->totals($rows);
        $periodTotals = $this->totals($periodRows);
        $items = $periodRows
            ->groupBy(fn ($row) => serialize([$row['item'], $row['variant'], $row['price']]))
            ->map(function ($group) {
                return [
                    'item' => $group->first()['item'], 'variant' => $group->first()['variant'], 'price' => $group->first()['price'],
                    'quantity' => $group->whereNotNull('quantity')->isEmpty() ? null : $group->sum('quantity'),
                    'weight' => $group->whereNotNull('weight')->isEmpty() ? null : $group->sum('weight'), 'amount' => $group->sum('amount'),
                ];
            })->sortBy([['item', 'asc'], ['variant', 'asc'], ['price', 'asc']])->values();

        // The sheet confirms paid purchases without recording the date of payment.
        $payments = $rows->where('payment_status', 'paid')->sortBy([['date', 'desc'], ['row', 'desc']])->values();

        return view('supplier-debt.show', [
            'name' => $rows->first()['supplier'], 'from' => $from, 'to' => $to, 'allTotals' => $allTotals, 'periodTotals' => $periodTotals,
            'purchases' => $this->paginate($periodRows, $request, 'purchase_page'),
            'payments' => $this->paginate($payments, $request, 'payment_page'),
            'items' => $this->paginate($items, $request, 'item_page'),
            'filtered' => $from !== null || $to !== null,
        ]);
    }

    public function refresh(Request $request, SupplierSheetDebtService $service, CustomerDebtSheetService $sheets)
    {
        try {
            $snapshot = $service->refresh($sheets);
        } catch (\RuntimeException $error) {
            report($error);

            return back()->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            report($error);

            return back()->with('error', 'Không cập nhật được tab nhatkymuahang. Vui lòng thử lại.');
        }

        return back()->with('success', 'Đã cập nhật '.count($snapshot['rows']).' dòng mua hàng từ Google Sheet.');
    }

    private function period(Request $request): array
    {
        $filters = $request->validate(['range' => 'nullable|in:all,week,custom', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        if (($filters['range'] ?? '') === 'week') {
            $today = today();

            return [$today->copy()->subDays(6)->toDateString(), $today->toDateString()];
        }
        if (($filters['range'] ?? '') === 'all') {
            return [null, null];
        }

        return [$filters['from'] ?? null, $filters['to'] ?? null];
    }

    private function inPeriod(Collection $rows, ?string $from, ?string $to): Collection
    {
        return $rows->filter(fn ($row) => ($from === null || $row['date'] >= $from) && ($to === null || $row['date'] <= $to));
    }

    private function totals(Collection $rows): array
    {
        $total = $rows->sum('amount');
        $paid = $rows->where('payment_status', 'paid')->sum('amount');

        return ['count' => $rows->count(), 'amount' => $total, 'paid' => $paid, 'balance' => $total - $paid, 'unverified' => $rows->where('payment_status', 'unknown')->sum('amount')];
    }

    private function paginate(Collection $rows, Request $request, string $pageName = 'page', int $perPage = 25): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return (new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, [
            'path' => $request->url(), 'pageName' => $pageName,
        ]))->appends($request->except($pageName));
    }
}
