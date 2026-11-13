<?php

namespace App\Http\Controllers;

use App\Services\CustomerDebtReportService;
use App\Services\CustomerDebtSheetService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerDebtNegotiationController extends Controller
{
    public function index(Request $request, CustomerDebtSheetService $sheets, CustomerDebtReportService $report)
    {
        abort_unless($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']), 403);
        $data = $request->validate(['search' => 'nullable|string|max:150', 'sale' => 'nullable|string|max:100', 'terms' => 'nullable|in:all,configured,default', 'sort' => 'nullable|in:name,max_debt,allowed_orders,due_days', 'direction' => 'nullable|in:asc,desc', 'per_page' => 'nullable|in:20,30,50,100', 'page' => 'nullable|integer|min:1']);
        $context = app(CustomerDebtMonitorController::class)->debtContext($request, $sheets);
        $rows = $report->rows($context)->map(function ($row) use ($context) {
            $row['policy'] = $context['policyService']->effective($row, $context['policies']);
            $row['configured'] = ! empty($row['policy']['updated_at']);
            // Monetary ceiling; Bill count and days remain independent warning conditions.
            $row['max_debt'] = (int) $row['policy']['credit_limit'];

            return $row;
        });
        $sales = $rows->map(fn ($row) => ['key' => $row['sale_key'], 'label' => $row['sale_label']])->unique('key')->sortBy('label')->values();
        $search = $sheets->normalize($data['search'] ?? '');
        $rows = $rows->filter(fn ($row) => ($search === '' || str_contains($sheets->normalize($row['search_text']), $search))
            && (empty($data['sale']) || $row['sale_key'] === $data['sale'])
            && (($data['terms'] ?? 'all') === 'all' || $row['configured'] === ($data['terms'] === 'configured')));
        $summary = ['customers' => $rows->count(), 'configured' => $rows->where('configured', true)->count(), 'max_debt' => $rows->sum('max_debt'), 'balance' => $rows->where('balance', '>', 0)->sum('balance')];
        $sort = $data['sort'] ?? 'max_debt';
        $direction = ($data['direction'] ?? 'desc') === 'asc' ? 1 : -1;
        $rows = $rows->sort(function ($a, $b) use ($sort, $direction) {
            $aValue = in_array($sort, ['allowed_orders', 'due_days'], true) ? $a['policy'][$sort] : $a[$sort];
            $bValue = in_array($sort, ['allowed_orders', 'due_days'], true) ? $b['policy'][$sort] : $b[$sort];

            return ($aValue <=> $bValue) * $direction ?: strcmp($a['name'], $b['name']);
        });
        $perPage = (int) ($data['per_page'] ?? 30);
        $page = (int) ($data['page'] ?? 1);
        $rows = new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->except('page')]);

        return view('customer-debt.negotiation', ['rows' => $rows, 'sales' => $sales, 'summary' => $summary]);
    }
}
