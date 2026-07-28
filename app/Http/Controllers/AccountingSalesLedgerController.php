<?php

namespace App\Http\Controllers;

use App\Exports\AccountingSalesLedgerExport;
use App\Models\AccountingSalesEntry;
use App\Models\AccountingSalesImportBatch;
use App\Models\Order;
use App\Models\User;
use App\Services\AccountingSalesImportService;
use App\Services\AccountingSalesLedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AccountingSalesLedgerController extends Controller
{
    public function index(Request $request)
    {
        return view('accounting.sales-ledger.index', $this->pageData($request));
    }

    public function import(Request $request, AccountingSalesImportService $service)
    {
        $validated = $request->validate([
            'text_data' => ['required', 'string', 'max:1000000'],
            'text_action' => ['required', Rule::in(['preview', 'import'])],
            'sale_mapping' => ['nullable', 'array'],
            'sale_mapping.*' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        try {
            $mapping = $validated['sale_mapping'] ?? [];
            $result = $validated['text_action'] === 'import'
                ? $service->import($validated['text_data'], $request->user(), $mapping)
                : $service->preview($validated['text_data'], $request->user(), $mapping);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['text_data' => $exception->getMessage()]);
        }

        if (($result['imported'] ?? false) === true) {
            return redirect()->route('accounting.sales-ledger.index')->with(
                'success',
                'Đã import '.count($result['rows']).' dòng và tạo '.$result['orders_created'].' đơn hoàn thành (phiên #'.$result['batch_id'].').'
            );
        }

        return view('accounting.sales-ledger.index', $this->pageData($request, $result, $validated['text_data']));
    }

    public function edit(AccountingSalesEntry $entry)
    {
        abort_unless($entry->source === AccountingSalesEntry::SOURCE_IMPORT, 403, 'Dòng sinh từ đơn hàng không được sửa trực tiếp.');
        return view('accounting.sales-ledger.edit', [
            'entry' => $entry,
            'salesUsers' => $this->salesUsers(),
        ]);
    }

    public function update(Request $request, AccountingSalesEntry $entry, AccountingSalesImportService $importService)
    {
        abort_unless($entry->source === AccountingSalesEntry::SOURCE_IMPORT, 403, 'Dòng sinh từ đơn hàng không được sửa trực tiếp.');
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'customer_code' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['required', 'string', 'max:255'],
            'sale_id' => ['required', 'integer', 'exists:users,id'],
            'unit' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'numeric'],
            'unit_weight' => ['required', 'numeric'],
            'total_quantity' => ['required', 'numeric'],
            'unit_price' => ['nullable', 'numeric'],
            'total_amount' => ['required', 'numeric'],
        ]);
        $sale = User::findOrFail($data['sale_id']);
        if ($entry->order_id) {
            // Các trường xác định nhóm Ngày + Khách + Sale là bất biến sau khi đã tạo đơn lịch sử.
            $data['entry_date'] = $entry->entry_date->toDateString();
            $data['customer_code'] = $entry->customer_code;
            $data['customer_name'] = $entry->customer_name;
            $data['sale_id'] = $entry->sale_id;
            $sale = User::findOrFail($entry->sale_id);
        }
        $data['entry_month'] = (int) date('n', strtotime($data['entry_date']));
        $data['sale_name'] = $sale->name;
        $data['updated_by'] = $request->user()->id;
        $entry->update($data);
        $importService->syncEntryOrderItem($entry->fresh());
        $this->refreshBatch($entry->import_batch_id);
        $this->refreshImportedOrder($entry->order_id);
        return redirect()->route('accounting.sales-ledger.index')->with('success', 'Đã cập nhật dòng doanh số #'.$entry->id.'.');
    }

    public function destroy(Request $request, AccountingSalesEntry $entry)
    {
        abort_unless($entry->source === AccountingSalesEntry::SOURCE_IMPORT, 403, 'Dòng sinh từ đơn hàng không được xóa trực tiếp.');
        $batchId = $entry->import_batch_id;
        $orderId = $entry->order_id;
        $entry->delete();
        $this->refreshBatch($batchId);
        $this->refreshImportedOrder($orderId);
        return back()->with('success', 'Đã xóa dòng doanh số import.');
    }

    public function destroyBatch(AccountingSalesImportBatch $batch)
    {
        $count = $batch->entries()->count();
        Order::where('accounting_sales_import_batch_id', $batch->id)->delete();
        $batch->delete();
        return back()->with('success', 'Đã xóa phiên import và '.$count.' dòng liên quan.');
    }

    public function sync(AccountingSalesLedgerService $service)
    {
        $result = $service->syncAllConfirmed();
        return back()->with('success', 'Đã đồng bộ '.$result['orders'].' đơn xác nhận thành '.$result['entries'].' dòng doanh số.');
    }

    public function repairItems(AccountingSalesImportService $service)
    {
        $result = $service->repairHistoricalOrderItems();

        return back()->with(
            'success',
            'Đã bổ sung '.$result['created'].' dòng sản phẩm còn thiếu và đồng bộ '.$result['updated'].' dòng sản phẩm đơn lịch sử.'
        );
    }

    public function export(Request $request)
    {
        $entries = $this->filteredQuery($request)->orderBy('entry_date')->orderBy('id')->get();
        return Excel::download(new AccountingSalesLedgerExport($entries), 'so-doanh-so-ke-toan-'.now()->format('Ymd-His').'.xlsx');
    }

    private function pageData(Request $request, ?array $importResult = null, string $textData = ''): array
    {
        $query = $this->filteredQuery($request);
        $summaryQuery = clone $query;
        return [
            'entries' => $query->with(['sale:id,name', 'customer:id,name', 'order:id,code'])
                ->orderByDesc('entry_date')->orderByDesc('id')->paginate(30)->withQueryString(),
            'summary' => [
                'rows' => (clone $summaryQuery)->count(),
                'amount' => (float) (clone $summaryQuery)->sum('total_amount'),
                'quantity' => (float) (clone $summaryQuery)->sum('total_quantity'),
            ],
            'salesUsers' => $this->salesUsers(),
            'batches' => AccountingSalesImportBatch::with('importer:id,name')->latest()->limit(10)->get(),
            'importResult' => $importResult,
            'textData' => $textData,
        ];
    }

    private function filteredQuery(Request $request): Builder
    {
        return AccountingSalesEntry::query()
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('entry_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('entry_date', '<=', $request->to_date))
            ->when($request->filled('sale_id'), fn ($q) => $q->where('sale_id', $request->sale_id))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = trim((string) $request->q);
                $q->where(fn ($sub) => $sub->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_code', 'like', "%{$term}%")
                    ->orWhere('sale_name', 'like', "%{$term}%"));
            });
    }

    private function salesUsers()
    {
        return User::whereHas('roles', fn ($query) => $query->whereIn(DB::raw('LOWER(name)'), [
            'sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale',
        ]))->orderBy('name')->get(['id', 'name', 'short_name', 'email']);
    }

    private function refreshBatch(?int $batchId): void
    {
        if (!$batchId) return;
        $batch = AccountingSalesImportBatch::find($batchId);
        if (!$batch) return;
        $batch->update([
            'row_count' => $batch->entries()->count(),
            'total_amount' => $batch->entries()->sum('total_amount'),
        ]);
    }

    private function refreshImportedOrder(?int $orderId): void
    {
        if (!$orderId) return;
        $order = Order::find($orderId);
        if (!$order || !$order->accounting_sales_import_batch_id) return;
        $entries = AccountingSalesEntry::where('order_id', $orderId)
            ->where('source', AccountingSalesEntry::SOURCE_IMPORT)->get();
        if ($entries->isEmpty()) {
            $order->delete();
            return;
        }
        $total = max(0, round((float) $entries->sum('total_amount'), 2));
        $paid = max((float) $order->amount_paid, (float) $order->collected_amount);
        $order->accountingReconciliation?->update([
            'total_amount' => $total,
            'recognized_revenue' => $total,
        ]);
        $order->forceFill([
            'total' => $total,
            'subtotal_amount' => $total,
            'amount_due' => max(0, $total - $paid),
        ])->save();
        if (Schema::hasTable('order_commissions') && DB::table('order_commissions')->where('order_id', $orderId)->exists()) {
            $percent = (float) ($order->commission_percent_snapshot ?? 0);
            DB::table('order_commissions')->where('order_id', $orderId)->update([
                'order_total' => $total,
                'commission_amount' => round($total * $percent / 100, 2),
                'updated_at' => now(),
            ]);
        }
    }
}
