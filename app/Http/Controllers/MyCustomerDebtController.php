<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\CustomerDebtPriorityService;
use App\Services\CustomerDebtSheetService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MyCustomerDebtController extends Controller
{
    public function index(Request $request, CustomerDebtSheetService $sheets, CustomerDebtMonitorController $monitor)
    {
        abort_unless($request->user() && ($request->user()->isAdmin() || $request->user()->isSalesFlowRole()), 403);
        if (! $request->filled('from') && ! $request->filled('to') && $request->input('range') !== 'all') {
            $yesterday = now()->subDay()->toDateString();
            $request->merge(['from' => $yesterday, 'to' => $yesterday]);
        }
        $filters = $request->validate([
            'from' => 'nullable|required_with:to|date_format:Y-m-d',
            'to' => 'nullable|required_with:from|date_format:Y-m-d|after_or_equal:from',
            'range' => 'nullable|in:all', 'search' => 'nullable|string|max:150', 'scope' => 'nullable|in:all,orders',
            'balance' => 'nullable|in:debt,all,settled,credit', 'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|in:20,30,50,100',
            'recovery' => 'nullable|in:all,normal,difficult,unrecoverable',
            'sort' => 'nullable|in:balance,sales,payments,period_sales,period_payments,period_increase,days_without_payment,last_purchase_date',
            'direction' => 'nullable|in:asc,desc',
        ]);
        $request->attributes->set('own_customer_debt', true);
        // The shared ledger has its own sort options; the sale table sorts independently below.
        $sort = $filters['sort'] ?? 'balance';
        $request->query->remove('sort');
        $request->merge(['tab' => 'all', 'window' => $request->input('window', 'all'), 'recovery' => $filters['recovery'] ?? 'all']);
        try {
            $result = $monitor->index($request, $sheets);
        } finally {
            if (isset($filters['sort'])) {
                $request->merge(['sort' => $sort]);
            }
        }
        if (! $result instanceof View || $request->input('presentation') === 'customer') {
            return $result;
        }
        $data = $result->getData();
        $priorities = app(CustomerDebtPriorityService::class);
        $priorityRecords = $priorities->personalRecords($request->user());
        $rows = collect($data['groups'])->unique(fn ($g) => $g['customer_id'] ? 'customer:'.$g['customer_id'] : $g['key'])
            ->map(function ($group) use ($data, $priorities, $priorityRecords) {
                $row = $group['customer_id'] ? $data['customerDebts'][$group['customer_id']] : $group;
                $row['key'] = $group['key'];
                $row['priority'] = $priorities->effective($row, $priorityRecords);
                $row['phone'] = $group['system_customer']?->phone ?: ($group['phone'] ?? '');
                $row['search_text'] = $row['name'].' '.$row['code'].' '.$row['phone'].' '.($group['system_customer']?->company_name ?? '').' '.$group['name'];
                $row['days_without_payment'] = $row['last_payment_date'] ? max(0, (int) CarbonImmutable::parse($row['last_payment_date'])->diffInDays(CarbonImmutable::today())) : null;

                return $row;
            })->values();
        $customerOptions = $rows->map(fn ($r) => ['key' => $r['key'], 'name' => $r['name'], 'code' => $r['code']])->sortBy('name')->values();
        $periodFrom = $filters['from'] ?? min(now()->toDateString(), $rows->flatMap(fn ($r) => $r['entries'])->min('date') ?? now()->toDateString());
        $periodTo = $filters['to'] ?? now()->toDateString();
        $rows = $rows->map(function ($row) use ($periodFrom, $periodTo) {
            $entries = collect($row['entries'])->filter(fn ($e) => $e['date'] >= $periodFrom && $e['date'] <= $periodTo);
            $row['period_sales'] = $entries->where('type', 'sale')->sum('amount');
            $row['period_payments'] = $entries->where('type', 'payment')->sum('amount');
            $row['period_gifts'] = $entries->where('type', 'sale')->sum('gift');
            $row['period_adjustments'] = $entries->where('type', 'adjustment')->sum('amount');
            $row['period_increase'] = $row['period_sales'] - $row['period_gifts'] - $row['period_payments'] + $row['period_adjustments'];

            return $row;
        });
        $visible = $rows->filter(function ($row) use ($request, $filters, $sheets, $periodFrom, $periodTo) {
            $balance = $filters['balance'] ?? 'debt';
            if (($balance === 'debt' && $row['balance'] <= 0) || ($balance === 'settled' && $row['balance'] != 0) || ($balance === 'credit' && $row['balance'] >= 0)) {
                return false;
            }
            if (($filters['scope'] ?? 'all') === 'orders' && ! collect($row['entries'])->contains(fn ($e) => $e['type'] === 'sale' && $e['amount'] > 0 && $e['date'] >= $periodFrom && $e['date'] <= $periodTo)) {
                return false;
            }
            if (($filters['recovery'] ?? 'all') !== 'all' && $row['recovery']['status'] !== $filters['recovery']) {
                return false;
            }
            if ($request->filled('customer') && $row['key'] !== $request->input('customer')) {
                return false;
            }
            $search = $sheets->normalize($filters['search'] ?? '');

            return $search === '' || str_contains($sheets->normalize($row['search_text']), $search);
        });
        $summary = ['customers' => $visible->count(), 'balance' => $visible->where('balance', '>', 0)->sum('balance'), 'balance_net' => $visible->sum('balance')];
        foreach (['sales', 'payments', 'period_sales', 'period_payments', 'period_gifts', 'period_adjustments', 'period_increase'] as $field) {
            $summary[$field] = $visible->sum($field);
        }
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 1 : -1;
        $visible = $visible->sort(fn ($a, $b) => ($b['priority']['stars'] <=> $a['priority']['stars']) ?: ($a[$sort] === null || $b[$sort] === null ? (($a[$sort] === null ? 1 : 0) <=> ($b[$sort] === null ? 1 : 0)) : ($a[$sort] <=> $b[$sort]) * $direction) ?: strcmp($a['name'], $b['name']));
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 30);
        $debts = new LengthAwarePaginator($visible->forPage($page, $perPage)->values(), $visible->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->except('page')]);
        $settings = Setting::whereIn('key', ['logo', 'brand_name', 'slogan', 'HOTLINE', 'address', 'email', 'footer_logo', 'hotline', 'tax_number'])->get()->keyBy('key');

        return view('site.my_customer_debt', array_replace($data, compact('debts', 'summary', 'customerOptions', 'periodFrom', 'periodTo', 'settings')));
    }

    public function updatePriority(Request $request, CustomerDebtSheetService $sheets, CustomerDebtMonitorController $monitor, CustomerDebtPriorityService $priorities)
    {
        abort_unless($request->user() && ($request->user()->isAdmin() || $request->user()->isSalesFlowRole()), 403);
        $data = $request->validate([
            'customer_id' => 'required|integer|min:1',
            'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'stars' => 'required|integer|min:0|max:5',
            'priority_version' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
        ]);
        $request->attributes->set('own_customer_debt', true);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $monitor, $priorities, $data) {
            DB::transaction(function () use ($request, $sheets, $monitor, $priorities, $data) {
                $context = $monitor->debtContext($request, $sheets);
                $group = $context['groups'][$data['source_key']] ?? null;
                abort_unless($group && (int) $group['customer_id'] === (int) $data['customer_id'], 404);
                $row = $context['customerDebts'][$group['customer_id']];
                $row['key'] = $group['key'];
                $priorities->savePersonal($row, (int) $data['stars'], $data['priority_version'], $request->user());
            });
        });

        return $request->expectsJson() ? response()->json(['message' => 'Đã lưu mức ghim theo dõi.']) : redirect()->route('pages.my_customer_debt');
    }
}
