<?php

namespace App\Http\Controllers;

use App\Services\CustomerDebtReportService;
use App\Services\CustomerDebtSheetService;
use App\Services\DebtCollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerDebtReportController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }

    private function sourceData(Request $request, CustomerDebtSheetService $sheets, ?CustomerDebtReportService $report = null): array
    {
        if ($request->input('report_source', 'sheet') === 'system') {
            return [app(\App\Services\SystemCustomerDebtReportService::class)->rows(), ['source' => 'system', 'loaded_at' => now()->toIso8601String()]];
        }
        $context = app(CustomerDebtMonitorController::class)->debtContext($request, $sheets);

        return [($report ?? app(CustomerDebtReportService::class))->rows($context), $context['snapshot']];
    }

    public function index(Request $request, CustomerDebtSheetService $sheets, CustomerDebtReportService $report, DebtCollectionService $collections)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['report_source' => 'nullable|in:sheet,system', 'scope' => 'nullable|in:all,orders', 'from' => 'nullable|required_if:scope,orders|date_format:Y-m-d', 'to' => 'nullable|required_if:scope,orders|date_format:Y-m-d|after_or_equal:from', 'search' => 'nullable|string|max:150', 'sale' => 'nullable|string|max:100', 'balance' => 'nullable|in:debt,all,settled,credit', 'recovery' => 'nullable|in:normal,difficult,unrecoverable,all', 'status' => ['nullable', Rule::in(array_merge(array_keys(DebtCollectionService::STATUSES), ['all', 'follow_up_due']))], 'per_page' => 'nullable|in:20,30,50,100', 'page' => 'nullable|integer|min:1', 'sort' => 'nullable|in:balance,days_without_purchase,days_without_payment', 'direction' => 'nullable|in:asc,desc']);
        [$rows, $snapshot] = $this->sourceData($request, $sheets, $report);
        $reportSource = $request->input('report_source') ?: 'sheet';
        $records = $collections->records($reportSource);
        $sales = $rows->map(fn ($r) => ['key' => $r['sale_key'], 'label' => $r['sale_label']])->unique('key')->sortBy('label')->values();
        $rows = $rows->map(fn ($row) => $row + ['collection' => $collections->evaluate($row, $records)]);
        $visible = $rows->filter(function ($row) use ($data, $sheets) {
            // Use the same positive Sheet purchases as the last-purchase date / days column.
            if (($data['scope'] ?? 'all') === 'orders' && ! collect($row['entries'])->contains(fn ($e) => $e['type'] === 'sale' && $e['amount'] > 0 && $e['date'] >= $data['from'] && $e['date'] <= $data['to'])) {
                return false;
            }
            $balance = $data['balance'] ?? 'debt';
            if ($balance === 'debt' && (! $row['data_available'] || $row['balance'] <= 0)) {
                return false;
            }
            if ($balance === 'settled' && (! $row['data_available'] || $row['balance'] != 0)) {
                return false;
            }
            if ($balance === 'credit' && (! $row['data_available'] || $row['balance'] >= 0)) {
                return false;
            }
            $recovery = $data['recovery'] ?? 'normal';
            if ($recovery !== 'all' && $row['recovery']['status'] !== $recovery) {
                return false;
            }
            if (! empty($data['sale']) && $row['sale_key'] !== $data['sale']) {
                return false;
            }
            $status = $data['status'] ?? 'all';
            if ($status === 'follow_up_due' ? ! ($row['collection']['latest']['follow_up_due'] ?? false) : ($status !== 'all' && $row['collection']['status'] !== $status)) {
                return false;
            }
            $search = $sheets->normalize($data['search'] ?? '');

            return $search === '' || str_contains($sheets->normalize($row['search_text']), $search);
        });
        $summary = ['customers' => $visible->count(), 'balance' => $visible->where('balance', '>', 0)->sum('balance'), 'gifts' => $visible->where('data_available', true)->sum('gifts'), 'payments' => $visible->where('data_available', true)->sum('payments'), 'received' => $visible->sum(fn ($r) => $r['collection']['received_total'])];
        $sort = $data['sort'] ?? 'balance';
        $direction = ($data['direction'] ?? 'desc') === 'asc' ? 1 : -1;
        $visible = $visible->sort(fn ($a, $b) => ($a[$sort] === null || $b[$sort] === null ? (($a[$sort] === null ? 1 : 0) <=> ($b[$sort] === null ? 1 : 0)) : ($a[$sort] <=> $b[$sort]) * $direction) ?: strcmp($a['name'], $b['name']));
        $perPage = (int) ($data['per_page'] ?? 30);
        $page = (int) ($data['page'] ?? 1);
        $rows = new \Illuminate\Pagination\LengthAwarePaginator($visible->forPage($page, $perPage)->values(), $visible->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->except('page')]);

        return view('customer-debt.report', ['rows' => $rows, 'sales' => $sales, 'summary' => $summary, 'snapshot' => $snapshot, 'canAdjust' => $reportSource === 'sheet', 'reportSource' => $reportSource]);
    }

    private function resolveRow(Request $request, CustomerDebtSheetService $sheets): array
    {
        [$rows, $snapshot] = $this->sourceData($request, $sheets);
        $row = $request->filled('customer_id') ? $rows->firstWhere('customer_id', (int) $request->input('customer_id')) : $rows->first(fn ($r) => in_array($request->input('source_key'), $r['source_keys'], true));
        abort_unless($row, 404);
        if ($request->filled('source_key') && ! in_array($request->input('source_key'), $row['source_keys'], true)) {
            throw ValidationException::withMessages(['customer' => 'Ghép khách đã thay đổi. Vui lòng tải lại trang.']);
        }

        return [$row, $snapshot];
    }

    private function targetRules(): array
    {
        return ['report_source' => 'nullable|in:sheet,system', 'source_key' => 'nullable|required_without:customer_id|string|size:64|regex:/^[a-f0-9]{64}$/', 'customer_id' => 'nullable|required_without:source_key|integer|min:1'];
    }

    private function normalizeMoney(Request $request, string $field): void
    {
        $value = preg_replace('/\s/u', '', trim((string) $request->input($field, '')));
        if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }
        $request->merge([$field => $value === '' ? null : $value]);
    }

    public function remind(Request $request, CustomerDebtSheetService $sheets, DebtCollectionService $collections)
    {
        $this->authorizeAdmin($request);
        $this->normalizeMoney($request, 'target_amount');
        $request->merge(['note' => trim((string) $request->input('note'))]);
        $data = $request->validate($this->targetRules() + ['request_id' => 'required|uuid', 'target_amount' => 'required|integer|min:1|max:999999999999999', 'channel' => ['required', Rule::in(array_keys(DebtCollectionService::CHANNELS))], 'note' => 'required|string|min:3|max:3000', 'promised_date' => 'nullable|date_format:Y-m-d|after_or_equal:today', 'follow_up_date' => 'nullable|date_format:Y-m-d|after_or_equal:today']);
        $id = Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $collections, $data) {
            return DB::transaction(function () use ($request, $sheets, $collections, $data) {
                [$row,$snapshot] = $this->resolveRow($request, $sheets);

                return $collections->remind($row, $snapshot, $data, $request->user());
            });
        });

        return response()->json(['message' => 'Đã ghi nhận nhắc nợ.', 'id' => $id]);
    }

    public function history(Request $request, CustomerDebtSheetService $sheets, DebtCollectionService $collections)
    {
        $this->authorizeAdmin($request);
        $request->validate($this->targetRules() + ['history_page' => 'nullable|integer|min:1']);
        [$row,$snapshot] = $this->resolveRow($request, $sheets);
        $evaluation = $collections->evaluate($row, $collections->records($row['report_source']));
        $page = (int) $request->input('history_page', 1);
        $all = collect($evaluation['logs']);
        $logs = new \Illuminate\Pagination\LengthAwarePaginator($all->forPage($page, 10)->values(), $all->count(), 10, $page, ['path' => $request->url(), 'pageName' => 'history_page', 'query' => $request->except('history_page')]);

        return response()->json(['html' => view('customer-debt.report-history-content', compact('row', 'snapshot', 'evaluation', 'logs'))->render()])->header('Cache-Control', 'no-store');
    }

    public function updateRecovery(Request $request, CustomerDebtSheetService $sheets, \App\Services\CustomerDebtRecoveryService $recovery)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate($this->targetRules() + ['recovery_status' => ['required', Rule::in(array_keys(\App\Services\CustomerDebtRecoveryService::LABELS))], 'recovery_note' => 'nullable|string|max:1000', 'recovery_version' => 'required|string|size:64']);
        Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $recovery, $data) {
            [$row] = $this->resolveRow($request, $sheets);
            $recovery->save($row, $data, $request->user());
        });

        return back()->with('success', 'Đã cập nhật khả năng thu hồi.');
    }

    public function respond(Request $request, CustomerDebtSheetService $sheets, DebtCollectionService $collections)
    {
        $this->authorizeAdmin($request);
        $this->normalizeMoney($request, 'promised_amount');
        $request->merge(['note' => trim((string) $request->input('note'))]);
        $data = $request->validate($this->targetRules() + ['request_id' => 'required|uuid', 'reminder_id' => 'required|integer|min:1', 'type' => 'required|in:response,payment_verified', 'note' => 'required|string|min:3|max:3000', 'promised_amount' => 'nullable|integer|min:1|max:999999999999999', 'promised_date' => 'nullable|date_format:Y-m-d|after_or_equal:today', 'receipt_token' => 'nullable|required_if:type,payment_verified|string|size:64|regex:/^[a-f0-9]{64}$/']);
        $id = Cache::lock('customer-debt-load', 60)->block(5, function () use ($request, $sheets, $collections, $data) {
            return DB::transaction(function () use ($request, $sheets, $collections, $data) {
                [$row] = $this->resolveRow($request, $sheets);

                return $collections->respond($row, $data, $request->user());
            });
        });

        return response()->json(['message' => 'Đã lưu lịch sử theo dõi.', 'id' => $id]);
    }
}
