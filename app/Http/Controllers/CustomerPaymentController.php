<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CustomerDebtSheetService;
use App\Services\CustomerPaymentReportService;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    private function authorizeFinance(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'account', 'accountant', 'accounting', 'director', 'ceo']), 403);
    }

    public function index(Request $request, CustomerDebtSheetService $sheets, CustomerPaymentReportService $report)
    {
        $this->authorizeFinance($request);
        $data = $request->validate(['batch' => 'nullable|uuid', 'change' => 'nullable|in:all,new,updated,removed', 'sale' => 'nullable|string|max:100', 'search' => 'nullable|string|max:150', 'customer_id' => 'nullable|integer|min:1', 'customer_source_key' => 'nullable|string|size:64|regex:/^[a-f0-9]{64}$/', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d', 'tab' => 'nullable|in:all,Dongtienhoatdong,TKCNMBBDUE', 'recent' => 'nullable|in:all,today', 'unidentified' => 'nullable|in:0,1', 'page' => 'nullable|integer|min:1', 'per_page' => 'nullable|in:20,30,50,100']);
        if (! empty($data['from']) && ! empty($data['to'])) {
            $request->validate(['to' => 'after_or_equal:from']);
        }
        $context = app(CustomerDebtMonitorController::class)->debtContext($request, $sheets);
        $snapshot = $context['snapshot'];
        $batch = ! empty($data['batch']) ? $sheets->paymentBatch($data['batch'], $snapshot) : null;
        $rows = $report->rows($context, $batch);
        $sales = $rows->map(fn ($r) => ['key' => $r['sale_key'], 'label' => $r['sale_label']])->unique('key')->sortBy('label')->values();
        $visible = $rows->filter(function ($row) use ($data, $batch, $sheets) {
            if ($batch && ($data['change'] ?? 'all') !== 'all' && $row['change_kind'] !== $data['change']) {
                return false;
            }
            if (! empty($data['sale']) && $row['sale_key'] !== $data['sale']) {
                return false;
            }
            if (! empty($data['customer_id']) && (int) $row['customer_id'] !== (int) $data['customer_id']) {
                return false;
            }
            if (! empty($data['customer_source_key']) && $row['source_key'] !== $data['customer_source_key']) {
                return false;
            }
            if (($data['tab'] ?? 'all') !== 'all' && $row['source_sheet'] !== $data['tab']) {
                return false;
            }
            if (($data['recent'] ?? 'all') === 'today' && ! $row['is_recent']) {
                return false;
            }
            if (($data['unidentified'] ?? '0') === '1' && ! $row['unidentified']) {
                return false;
            }
            if (! empty($data['from']) && (! $row['date'] || $row['date'] < $data['from'])) {
                return false;
            }
            if (! empty($data['to']) && (! $row['date'] || $row['date'] > $data['to'])) {
                return false;
            }
            $search = $sheets->normalize($data['search'] ?? '');

            return $search === '' || str_contains($row['search_text'], $search);
        });
        $active = $visible->where('removed', false);
        $summary = ['count' => $visible->count(), 'amount' => $active->sum('amount'), 'unidentified' => $active->where('unidentified', true)->sum('amount'), 'unidentified_count' => $active->where('unidentified', true)->count(), 'today' => $active->where('date', now()->toDateString())->sum('amount')];
        $visible = $visible->sort(fn ($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? '') ?: strcmp($a['source_sheet'], $b['source_sheet']) ?: ($b['row'] <=> $a['row']));
        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 30);
        $rows = new \Illuminate\Pagination\LengthAwarePaginator($visible->forPage($page, $perPage)->values(), $visible->count(), $perPage, $page, ['path' => $request->url(), 'query' => $request->except('page')]);
        $customerFilter = ! empty($data['customer_id']) ? ($context['customers']->firstWhere('id', (int) $data['customer_id'])?->name ?? '#'.$data['customer_id']) : (! empty($data['customer_source_key']) ? ($context['groups'][$data['customer_source_key']]['name'] ?? 'Khách nguồn đã chọn') : null);

        return view('customer-debt.payments', ['rows' => $rows, 'sales' => $sales, 'summary' => $summary, 'snapshot' => $snapshot, 'batch' => $batch, 'latest' => $snapshot['payment_syncs'][0] ?? null, 'customerFilter' => $customerFilter]);
    }

    public function refresh(Request $request, CustomerDebtSheetService $sheets)
    {
        $this->authorizeFinance($request);
        try {
            $tabs = $sheets->fetch();
            $summary = $sheets->save($tabs[0], $tabs[1], $request->user()->id, 'google', $tabs[2] ?? null);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Không cập nhật được Google Sheet. Bản dữ liệu trước được giữ lại; kiểm tra quyền đọc và cấu trúc các tab nguồn.');
        }

        return redirect()->route('customer-debt.payments', ['batch' => $summary['id'], 'change' => 'new'])->with('success', "Đã cập nhật: {$summary['new_count']} khoản mới, {$summary['updated_count']} khoản thay đổi, {$summary['removed_count']} khoản bị bỏ khỏi nguồn.");
    }

    public function updates(Request $request, CustomerDebtSheetService $sheets)
    {
        $this->authorizeFinance($request);
        $data = $request->validate(['page' => 'nullable|integer|min:1']);
        $all = collect($sheets->snapshot()['payment_syncs'] ?? []);
        $page = (int) ($data['page'] ?? 1);
        $ids = $all->pluck('loaded_by')->filter()->unique();
        $users = User::query()->whereIn('id', $ids)->pluck('name', 'id');
        $runs = new \Illuminate\Pagination\LengthAwarePaginator($all->forPage($page, 15)->values(), $all->count(), 15, $page, ['path' => $request->url()]);

        return response()->json(['html' => view('customer-debt.payment-updates', compact('runs', 'users'))->render()])->header('Cache-Control', 'no-store');
    }
}
