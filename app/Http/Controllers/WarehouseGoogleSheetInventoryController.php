<?php

namespace App\Http\Controllers;

use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\GoogleSheetInventoryComparisonService;
use App\Services\GoogleSheetsInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseGoogleSheetInventoryController extends Controller
{
    public function index(
        Request $request,
        GoogleSheetsInventoryService $sheets,
        GoogleSheetInventoryComparisonService $comparisonService
    ) {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);
        $warehouse = $this->resolveWarehouse($request);
        $selectedDate = Carbon::parse($validated['date'] ?? now())->toDateString();
        $preview = null;
        $loadError = null;
        $comparison = null;

        try {
            $preview = $sheets->preview($warehouse, $selectedDate);
            $marker = $this->importMarker($preview['spreadsheet_id'], $preview['sheet_id'], $selectedDate, (int) $warehouse->id);
            $comparison = $comparisonService->compare($preview, $warehouse, $marker);
            $preview['rows'] = $comparison['rows'];
        } catch (\Throwable $exception) {
            report($exception);
            $loadError = $this->friendlyGoogleError($exception, $sheets);
        }

        $warehouses = Auth::user()?->isAdmin()
            ? Warehouse::query()->where('status', true)->orderBy('name')->get(['id', 'name'])
            : collect([$warehouse]);

        return view('warehouse.google-sheet-inventory.index', compact(
            'warehouse',
            'warehouses',
            'selectedDate',
            'preview',
            'loadError',
            'comparison'
        ));
    }

    public function store(
        Request $request,
        GoogleSheetsInventoryService $sheets,
        GoogleSheetInventoryComparisonService $comparisonService
    ) {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'confirm_import' => ['accepted'],
            'ignore_unmatched' => ['nullable', 'boolean'],
            'selected_variant_ids' => ['required', 'array', 'min:1'],
            'selected_variant_ids.*' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
        ]);
        $warehouse = $this->resolveWarehouse($request);
        $selectedDate = Carbon::parse($validated['date'])->toDateString();

        try {
            $preview = $sheets->preview($warehouse, $selectedDate);
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $this->friendlyGoogleError($exception, $sheets));
        }

        if ($preview['has_blocking_errors'] && empty($validated['ignore_unmatched'])) {
            throw ValidationException::withMessages([
                'ignore_unmatched' => 'Sheet có mã đang có tồn nhưng chưa ghép được với biến thể. Hãy kiểm tra hoặc xác nhận bỏ qua các mã này.',
            ]);
        }
        $marker = $this->importMarker(
            (string) $preview['spreadsheet_id'],
            (int) $preview['sheet_id'],
            $selectedDate,
            (int) $warehouse->id
        );

        try {
            $result = Cache::lock('google-sheet-inventory-import:'.sha1($marker), 120)
                ->block(10, function () use ($preview, $warehouse, $selectedDate, $marker, $validated, $comparisonService) {
                    return DB::transaction(function () use ($preview, $warehouse, $selectedDate, $marker, $validated, $comparisonService) {
                        $comparison = $comparisonService->compare($preview, $warehouse, $marker);
                        $selectedIds = collect($validated['selected_variant_ids'])->map(fn ($id) => (int) $id)->unique()->values();
                        $selectedRows = $comparison['changed_rows']
                            ->filter(fn (array $row): bool => $selectedIds->contains((int) $row['variant_id']))
                            ->values();

                        if ($selectedRows->count() !== $selectedIds->count()) {
                            throw ValidationException::withMessages([
                                'selected_variant_ids' => 'Dữ liệu Sheet đã thay đổi hoặc có sản phẩm không còn chênh lệch. Vui lòng tải lại để kiểm tra.',
                            ]);
                        }
                        if ($selectedRows->contains(fn (array $row): bool => ! $row['can_apply'])) {
                            throw ValidationException::withMessages([
                                'selected_variant_ids' => 'Có sản phẩm không thể điều chỉnh do tồn sau cập nhật thấp hơn số lượng đang giữ chỗ.',
                            ]);
                        }

                        $sync = GoogleSheetInventorySync::create([
                            'warehouse_id' => $warehouse->id,
                            'spreadsheet_id' => $preview['spreadsheet_id'],
                            'sheet_id' => $preview['sheet_id'],
                            'inventory_date' => $selectedDate,
                            'sync_number' => $comparison['next_sync_number'],
                            'created_by' => Auth::id(),
                            'status' => 'applying',
                            'snapshot' => $comparison['baseline']->all(),
                        ]);
                        $positiveRows = $selectedRows->filter(fn (array $row): bool => (float) $row['delta'] > 0)->values();
                        $negativeRows = $selectedRows->filter(fn (array $row): bool => (float) $row['delta'] < 0)->values();
                        $document = null;

                        if ($positiveRows->isNotEmpty()) {
                            $document = InventoryDocument::create([
                                'type' => 'import',
                                'document_date' => $selectedDate,
                                'warehouse_id' => $warehouse->id,
                                'supplier_id' => null,
                                'shipping_fee' => 0,
                                'notes' => 'Đồng bộ tăng tồn từ Google Sheet '.$preview['sheet_name']
                                .' ngày '.Carbon::parse($selectedDate)->format('d/m/Y')
                                .' – lần đồng bộ '.$comparison['next_sync_number']."\n"
                                .$marker."\n[google_sheet_inventory_sync:".$sync->id.']',
                                'user_id' => Auth::id(),
                            ]);
                        }

                        foreach ($selectedRows as $row) {
                            $delta = round((float) $row['delta'], 3);
                            $variantId = (int) $row['variant_id'];
                            $inventory = Inventory::query()->firstOrCreate([
                                'product_variant_id' => $variantId,
                                'warehouse_id' => $warehouse->id,
                            ], ['quantity' => 0, 'reserved_quantity' => 0]);
                            $inventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();

                            $newQuantity = round((float) $inventory->quantity + $delta, 3);
                            if ($newQuantity < (float) $inventory->reserved_quantity) {
                                throw ValidationException::withMessages([
                                    'selected_variant_ids' => 'Không thể giảm '.$row['variant_name'].' vì tồn sau cập nhật thấp hơn số lượng đang giữ chỗ.',
                                ]);
                            }

                            if ($delta > 0) {
                                $document->items()->create([
                                    'product_variant_id' => $variantId,
                                    'quantity' => $delta,
                                    'unit_cost' => 0,
                                    'note' => 'Chênh lệch Google Sheet dòng '.$row['sheet_row'].' – '.$row['sheet_code'],
                                ]);
                                InventoryMovement::create([
                                    'inventory_id' => $inventory->id,
                                    'quantity' => $delta,
                                    'type' => 'import',
                                    'reference_id' => $document->id,
                                    'reference_type' => InventoryDocument::class,
                                    'user_id' => Auth::id(),
                                ]);
                            } else {
                                InventoryAdjustment::create([
                                    'inventory_id' => $inventory->id,
                                    'quantity' => $delta,
                                    'reason' => 'Giảm theo Google Sheet ngày '.Carbon::parse($selectedDate)->format('d/m/Y')
                                        .' (lần đồng bộ '.$comparison['next_sync_number'].')',
                                    'user_id' => Auth::id(),
                                ]);
                                InventoryMovement::create([
                                    'inventory_id' => $inventory->id,
                                    'quantity' => $delta,
                                    'type' => 'google_sheet_adjustment',
                                    'reference_id' => $sync->id,
                                    'reference_type' => GoogleSheetInventorySync::class,
                                    'user_id' => Auth::id(),
                                ]);
                            }
                            $inventory->update(['quantity' => $newQuantity]);

                            $totalStock = (float) Inventory::query()
                                ->where('product_variant_id', $variantId)
                                ->sum('quantity');
                            ProductVariant::query()->whereKey($variantId)->update(['stock' => $totalStock]);
                        }

                        $snapshot = collect($comparison['baseline']);
                        foreach ($selectedRows as $row) {
                            $snapshot->put((string) $row['variant_id'], round((float) $row['quantity'], 3));
                        }
                        $changes = $selectedRows->map(fn (array $row): array => [
                            'product_variant_id' => (int) $row['variant_id'],
                            'variant_name' => $row['variant_name'],
                            'variant_sku' => $row['variant_sku'],
                            'sheet_code' => $row['sheet_code'],
                            'sheet_row' => (int) $row['sheet_row'],
                            'previous_quantity' => (float) $row['previous_sheet_quantity'],
                            'sheet_quantity' => (float) $row['quantity'],
                            'delta' => (float) $row['delta'],
                            'change_type' => $row['change_type'],
                        ])->all();
                        $sync->update([
                            'import_document_id' => $document?->id,
                            'status' => 'completed',
                            'total_positive_delta' => $positiveRows->sum('delta'),
                            'total_negative_delta' => abs((float) $negativeRows->sum('delta')),
                            'applied_rows_count' => $selectedRows->count(),
                            'snapshot' => $snapshot->all(),
                            'changes' => $changes,
                        ]);

                        return ['sync' => $sync, 'document' => $document];
                    });
                });
        } catch (ValidationException $exception) {
            throw $exception;
        }

        try {
            app(WarehouseDashboardController::class)
                ->refreshQueuedOrdersAfterInventoryChange((int) $warehouse->id);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $sync = $result['sync'];

        return redirect()->route('warehouse.google-sheet-inventory.index', [
            'date' => $selectedDate,
            'warehouse_id' => $warehouse->id,
        ])->with('success', 'Đã đồng bộ lần '.$sync->sync_number.': nhập thêm '
            .number_format((float) $sync->total_positive_delta, 0, ',', '.')
            .', điều chỉnh giảm '.number_format((float) $sync->total_negative_delta, 0, ',', '.')
            .' tại '.$warehouse->name.'.');
    }

    private function resolveWarehouse(Request $request): Warehouse
    {
        $user = Auth::user();
        if ((int) ($user?->warehouse_id ?? 0) > 0) {
            return Warehouse::query()->findOrFail((int) $user->warehouse_id);
        }

        abort_unless($user?->isAdmin(), 403, 'Tài khoản chưa được gán kho quản lý.');
        $warehouseId = (int) $request->input('warehouse_id', 0);

        return $warehouseId > 0
            ? Warehouse::query()->findOrFail($warehouseId)
            : Warehouse::query()->where('status', true)->orderByRaw("CASE WHEN name LIKE '%Long An%' THEN 0 ELSE 1 END")->firstOrFail();
    }

    private function importMarker(string $spreadsheetId, int $sheetId, string $date, int $warehouseId): string
    {
        return '[google_sheet_inventory:'.$spreadsheetId.':'.$sheetId.':'.$date.':warehouse:'.$warehouseId.']';
    }

    private function friendlyGoogleError(\Throwable $exception, GoogleSheetsInventoryService $sheets): string
    {
        $message = $exception->getMessage();
        if (str_contains($message, 'PERMISSION_DENIED') || str_contains($message, '403') || str_contains($message, '404')) {
            return 'Không đọc được Google Sheet. Hãy chia sẻ quyền Người xem cho service account: '.($sheets->serviceAccountEmail() ?: 'chưa xác định').'.';
        }

        return 'Không thể load tồn kho từ Google Sheet: '.$message;
    }
}
