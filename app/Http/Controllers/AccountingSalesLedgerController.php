<?php

namespace App\Http\Controllers;

use App\Exports\AccountingSalesLedgerExport;
use App\Models\AccountingSalesEntry;
use App\Models\AccountingSalesImportBatch;
use App\Models\AccountingReconciliation;
use App\Models\InventoryDocument;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
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
            'workflow_date' => ['required', 'date'],
            'stock_in_document_id' => ['nullable', 'integer', 'exists:inventory_documents,id'],
            'text_data' => ['required', 'string', 'max:1000000'],
            'text_action' => ['required', Rule::in(['preview', 'import'])],
            'sale_mapping' => ['nullable', 'array'],
            'sale_mapping.*' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        [$sourceWarehouse, $targetWarehouse] = $this->workflowWarehouses();
        $stockInDocument = null;
        if (! empty($validated['stock_in_document_id'])) {
            $stockInDocument = InventoryDocument::query()
                ->whereKey($validated['stock_in_document_id'])
                ->where('type', 'import')
                ->when($sourceWarehouse, fn ($query) => $query->where('warehouse_id', $sourceWarehouse->id))
                ->whereDate('document_date', $validated['workflow_date'])
                ->first();
        }
        if (! empty($validated['stock_in_document_id']) && ! $stockInDocument) {
            return back()->withInput()->withErrors([
                'stock_in_document_id' => 'Phiếu nhập phải thuộc Kho Long An và đúng ngày thực hiện.',
            ]);
        }
        try {
            $mapping = $validated['sale_mapping'] ?? [];
            $result = $validated['text_action'] === 'import'
                ? $service->import(
                    $validated['text_data'],
                    $request->user(),
                    $mapping,
                    $validated['workflow_date'],
                    $stockInDocument?->id,
                    $sourceWarehouse?->id,
                    $targetWarehouse?->id
                )
                : $service->preview(
                    $validated['text_data'],
                    $request->user(),
                    $mapping,
                    $validated['workflow_date'],
                    $stockInDocument?->id
                );
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['text_data' => $exception->getMessage()]);
        }

        if (($result['imported'] ?? false) === true) {
            return redirect()->route('accounting.sales-ledger.index')->with(
                'success',
                'Đã import '.count($result['rows']).' dòng, tạo '.$result['orders_created'].' đơn hoàn tất và tự động xác nhận doanh số/hoa hồng (phiên #'.$result['batch_id'].'). Shipper có thể bổ sung điều phối và phí ship sau.'
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
        $hasOperations = $batch->orders()
            ->whereHas('histories', fn ($query) => $query->where('action', '!=', 'create_order'))
            ->exists() || $batch->orders()->whereHas('warehouseTransfers')->exists();
        if ($hasOperations) {
            return back()->with('error', 'Không thể xóa phiên import đã phát sinh đóng hàng, điều chuyển hoặc giao hàng.');
        }
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
        [$sourceWarehouse, $targetWarehouse] = $this->workflowWarehouses();
        $workflowDate = $request->input('workflow_date', now()->toDateString());
        $stockInDocuments = collect();
        if ($sourceWarehouse) {
            $stockInDocuments = InventoryDocument::query()
                ->withCount('items')
                ->where('type', 'import')
                ->where('warehouse_id', $sourceWarehouse->id)
                ->whereDate('document_date', $workflowDate)
                ->latest('id')
                ->get();
        }

        $batches = AccountingSalesImportBatch::query()
            ->with([
                'importer:id,name',
                'stockInDocument:id,document_number,document_date',
                'sourceWarehouse:id,name',
                'targetWarehouse:id,name',
                'orders:id,accounting_sales_import_batch_id,status,warehouse_id,shipper_id',
                'orders.accountingReconciliation:id,order_id,status',
                'orders.approvals.step:id,role_slug,step_order',
                'orders.warehouseTransfers' => fn ($q) => $q->latest('id'),
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->each(function (AccountingSalesImportBatch $batch): void {
                $orders = $batch->orders;
                $total = $orders->count();
                $received = $orders->filter(function (Order $order): bool {
                    return $order->warehouseTransfers->first()?->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED
                        || ($order->accounting_sales_import_batch_id && $order->status === Order::STATUS_COMPLETED);
                })->count();
                $leaderRoles = ['leader_sale', 'leader', 'sale_manager'];
                $managerRoles = ['manager_sale', 'manager', 'director'];
                $batch->setAttribute('workflow_progress', [
                    'total' => $total,
                    'leader_approved' => $orders->filter(fn (Order $order) => $order->status === Order::STATUS_COMPLETED
                        || $order->approvals->contains(fn ($approval) => $approval->status === 'approved'
                            && in_array(strtolower((string) $approval->step?->role_slug), $leaderRoles, true)))->count(),
                    'manager_approved' => $orders->filter(fn (Order $order) => $order->status === Order::STATUS_COMPLETED
                        || $order->approvals->contains(fn ($approval) => $approval->status === 'approved'
                            && in_array(strtolower((string) $approval->step?->role_slug), $managerRoles, true)))->count(),
                    'packed' => $orders->whereIn('status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->count(),
                    'received' => $received,
                    'shipping' => $orders->whereIn('status', [Order::STATUS_DELIVERING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->count(),
                    'delivered' => $orders->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->count(),
                    'accounted' => $orders->filter(fn (Order $order) => $order->accountingReconciliation?->status === AccountingReconciliation::STATUS_CONFIRMED)->count(),
                ]);
            });

        $dailyOrders = Order::query()
            ->with(['customer:id,name', 'user:id,name', 'warehouse:id,name', 'accountingReconciliation:id,order_id,status'])
            ->whereDate('created_at', $workflowDate)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $defaultTransferShipper = User::query()
            ->whereHas('roles', fn ($roles) => $roles->whereRaw('LOWER(name) = ?', ['shipper']))
            ->when($sourceWarehouse, fn ($query) => $query->orderByRaw(
                'CASE WHEN warehouse_id = ? THEN 0 ELSE 1 END',
                [$sourceWarehouse->id]
            ))
            ->orderBy('id')
            ->first(['id', 'name', 'warehouse_id']);

        return [
            'entries' => $query->with(['sale:id,name', 'customer:id,name', 'order:id,code'])
                ->orderByDesc('entry_date')->orderByDesc('id')->paginate(30)->withQueryString(),
            'summary' => [
                'rows' => (clone $summaryQuery)->count(),
                'amount' => (float) (clone $summaryQuery)->sum('total_amount'),
                'quantity' => (float) (clone $summaryQuery)->sum('total_quantity'),
            ],
            'salesUsers' => $this->salesUsers(),
            'batches' => $batches,
            'importResult' => $importResult,
            'textData' => $textData,
            'workflowDate' => $workflowDate,
            'sourceWarehouse' => $sourceWarehouse,
            'targetWarehouse' => $targetWarehouse,
            'stockInDocuments' => $stockInDocuments,
            'dailyOrders' => $dailyOrders,
            'defaultTransferShipper' => $defaultTransferShipper,
        ];
    }

    private function filteredQuery(Request $request): Builder
    {
        return AccountingSalesEntry::query()
            ->where(function ($query): void {
                $query->where('source', AccountingSalesEntry::SOURCE_ORDER)
                    ->orWhere(function ($importQuery): void {
                        $importQuery->where('source', AccountingSalesEntry::SOURCE_IMPORT)
                            ->whereNotNull('accounting_reconciliation_id');
                    });
            })
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

    private function workflowWarehouses(): array
    {
        $source = Warehouse::query()->where('name', 'like', '%Long An%')->orderBy('id')->first();
        $target = Warehouse::query()->where('name', 'like', '%Chiến Lược%')->orderBy('id')->first();

        return [$source, $target];
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
