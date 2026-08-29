<?php

namespace App\Http\Controllers;

use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
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

    public function resetRange(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Chỉ Admin được reset dữ liệu tồn kho Google Sheet.');

        $validated = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'confirm_reset' => ['accepted'],
            'reset_reason' => ['nullable', 'string', 'max:500'],
        ]);
        $warehouse = $this->resolveWarehouse($request);
        $fromDate = Carbon::parse($validated['from_date'])->toDateString();
        $toDate = Carbon::parse($validated['to_date'])->toDateString();
        $clearDayMode = $request->boolean('_clear_day_mode');

        try {
            $result = Cache::lock(
                'google-sheet-inventory-reset:'.$warehouse->id,
                120
            )->block(10, function () use ($warehouse, $fromDate, $toDate, $validated, $clearDayMode) {
                return DB::transaction(function () use ($warehouse, $fromDate, $toDate, $validated, $clearDayMode): array {
                    $syncs = GoogleSheetInventorySync::query()
                        ->where('warehouse_id', $warehouse->id)
                        ->where('status', 'completed')
                        ->whereBetween('inventory_date', [$fromDate, $toDate])
                        ->orderByDesc('inventory_date')
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->get();

                    if ($syncs->isEmpty()) {
                        throw ValidationException::withMessages([
                            'from_date' => 'Không có lần đồng bộ Google Sheet nào cần reset trong khoảng ngày đã chọn.',
                        ]);
                    }

                    $deltasByVariant = [];
                    foreach ($syncs as $sync) {
                        foreach ((array) $sync->changes as $change) {
                            $variantId = (int) ($change['product_variant_id'] ?? 0);
                            $delta = round((float) ($change['delta'] ?? 0), 3);
                            if ($variantId > 0 && abs($delta) >= 0.001) {
                                $deltasByVariant[$variantId] = round(
                                    ($deltasByVariant[$variantId] ?? 0) + $delta,
                                    3
                                );
                            }
                        }
                    }

                    $releasedReservations = $clearDayMode
                        ? $this->releasePackingReservationsForDay($fromDate, (int) $warehouse->id)
                        : ['count' => 0, 'quantity' => 0];

                    $inventories = Inventory::query()
                        ->where('warehouse_id', $warehouse->id)
                        ->whereIn('product_variant_id', array_keys($deltasByVariant))
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('product_variant_id');

                    foreach ($deltasByVariant as $variantId => $appliedDelta) {
                        $inventory = $inventories->get($variantId);
                        if (! $inventory) {
                            throw ValidationException::withMessages([
                                'from_date' => 'Không thể reset vì sản phẩm #'.$variantId.' không còn dòng tồn kho tương ứng.',
                            ]);
                        }

                        $quantityAfterReset = round((float) $inventory->quantity - $appliedDelta, 3);
                        if ($quantityAfterReset < 0 || $quantityAfterReset < (float) $inventory->reserved_quantity) {
                            throw ValidationException::withMessages([
                                'from_date' => 'Không thể reset vì tồn của sản phẩm #'.$variantId
                                    .' sau hoàn tác sẽ thấp hơn 0 hoặc thấp hơn số lượng đang giữ chỗ.',
                            ]);
                        }
                    }

                    foreach ($syncs as $sync) {
                        $syncDeltas = collect((array) $sync->changes)
                            ->groupBy(fn (array $change) => (int) ($change['product_variant_id'] ?? 0))
                            ->map(fn ($changes) => round((float) $changes->sum('delta'), 3))
                            ->filter(fn ($delta, $variantId) => (int) $variantId > 0 && abs((float) $delta) >= 0.001);

                        foreach ($syncDeltas as $variantId => $appliedDelta) {
                            $inventory = $inventories->get((int) $variantId);
                            InventoryMovement::query()->create([
                                'inventory_id' => $inventory->id,
                                'quantity' => -$appliedDelta,
                                'type' => 'google_sheet_reset',
                                'reference_id' => $sync->id,
                                'reference_type' => GoogleSheetInventorySync::class,
                                'user_id' => Auth::id(),
                            ]);
                        }

                        $sync->update([
                            'status' => 'reset',
                            'reset_by' => Auth::id(),
                            'reset_at' => now(),
                            'reset_reason' => trim((string) ($validated['reset_reason'] ?? '')) ?: null,
                        ]);
                    }

                    foreach ($deltasByVariant as $variantId => $appliedDelta) {
                        $inventory = $inventories->get($variantId);
                        $inventory->update([
                            'quantity' => round((float) $inventory->quantity - $appliedDelta, 3),
                        ]);
                        ProductVariant::query()->whereKey($variantId)->update([
                            'stock' => Inventory::query()->where('product_variant_id', $variantId)->sum('quantity'),
                        ]);
                    }

                    return [
                        'sync_count' => $syncs->count(),
                        'variant_count' => count($deltasByVariant),
                        'reservation_count' => $releasedReservations['count'],
                        'reservation_quantity' => $releasedReservations['quantity'],
                    ];
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

        $response = redirect()->route('warehouse.google-sheet-inventory.index', [
            'date' => $fromDate,
            'warehouse_id' => $warehouse->id,
        ]);

        if ($clearDayMode) {
            return $response->with('success', 'Đã Clear dữ liệu ngày '.Carbon::parse($fromDate)->format('d/m/Y')
                .', giải phóng '.$result['reservation_count'].' lượt giữ chỗ ('
                .number_format((float) $result['reservation_quantity'], 0, ',', '.').' sản phẩm) của đơn chờ kho đóng, và hoàn tác '
                .$result['sync_count'].' lần đồng bộ Google Sheet. Bạn có thể Load và nhập lại Tồn + Nhập.');
        }

        return $response->with('success', 'Đã reset '.$result['sync_count'].' lần đồng bộ, hoàn tác tồn của '
            .$result['variant_count'].' sản phẩm trong khoảng '
            .Carbon::parse($fromDate)->format('d/m/Y').' – '.Carbon::parse($toDate)->format('d/m/Y').'.');
    }

    public function clearDay(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Chỉ Admin được Clear dữ liệu tồn kho Google Sheet.');

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'confirmation_date' => ['required', 'date_format:Y-m-d', 'same:date'],
            'clear_reason' => ['nullable', 'string', 'max:500'],
            'confirm_clear' => ['accepted'],
        ], [
            'confirmation_date.same' => 'Ngày xác nhận phải trùng với ngày cần Clear.',
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $request->merge([
            'from_date' => $date,
            'to_date' => $date,
            'confirm_reset' => '1',
            '_clear_day_mode' => '1',
            'reset_reason' => 'Clear ngày để nhập lại Google Sheet'
                .(filled($validated['clear_reason'] ?? null) ? ': '.trim($validated['clear_reason']) : ''),
        ]);

        return $this->resetRange($request);
    }

    /** @return array{count:int,quantity:float} */
    private function releasePackingReservationsForDay(string $date, int $warehouseId): array
    {
        $orderIds = Order::query()
            ->forPackingDate($date)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('status', [Order::STATUS_APPROVED, Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING])
            ->whereNull('trash_at')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return ['count' => 0, 'quantity' => 0];
        }

        $orderItemIds = DB::table('order_items')->whereIn('order_id', $orderIds)->pluck('id');
        $reservations = InventoryReservation::query()
            ->whereIn('order_item_id', $orderItemIds)
            ->whereHas('inventory', fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->lockForUpdate()
            ->get();
        $inventoryIds = $reservations->pluck('inventory_id')->unique();
        $quantity = (float) $reservations->sum('quantity');

        InventoryReservation::query()->whereIn('id', $reservations->pluck('id'))->delete();
        foreach ($inventoryIds as $inventoryId) {
            Inventory::query()->whereKey($inventoryId)->update([
                'reserved_quantity' => InventoryReservation::query()
                    ->where('inventory_id', $inventoryId)
                    ->sum('quantity'),
            ]);
        }

        Order::query()->whereIn('id', $orderIds)->update([
            'stock_sufficient' => null,
            'stock_shortage_detail' => null,
            'stock_alert_status' => null,
        ]);

        return ['count' => $reservations->count(), 'quantity' => $quantity];
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
