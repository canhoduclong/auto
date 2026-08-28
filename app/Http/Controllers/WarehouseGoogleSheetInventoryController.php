<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\GoogleSheetsInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WarehouseGoogleSheetInventoryController extends Controller
{
    public function index(Request $request, GoogleSheetsInventoryService $sheets)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);
        $warehouse = $this->resolveWarehouse($request);
        $selectedDate = Carbon::parse($validated['date'] ?? now())->toDateString();
        $preview = null;
        $loadError = null;
        $existingDocuments = collect();

        try {
            $preview = $this->withInventoryState($sheets->preview($warehouse, $selectedDate), $warehouse);
            $marker = $this->importMarker($preview['spreadsheet_id'], $preview['sheet_id'], $selectedDate, (int) $warehouse->id);
            $existingDocuments = InventoryDocument::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('type', 'import')
                ->where('notes', 'like', '%'.$marker.'%')
                ->orderBy('id')
                ->get();
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
            'existingDocuments'
        ));
    }

    public function store(Request $request, GoogleSheetsInventoryService $sheets)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'confirm_import' => ['accepted'],
            'ignore_unmatched' => ['nullable', 'boolean'],
            'allow_duplicate' => ['nullable', 'boolean'],
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
        if ($preview['import_rows']->isEmpty()) {
            throw ValidationException::withMessages([
                'date' => 'Ngày đã chọn không có số tồn Móc hợp lệ để nhập.',
            ]);
        }

        $marker = $this->importMarker(
            (string) $preview['spreadsheet_id'],
            (int) $preview['sheet_id'],
            $selectedDate,
            (int) $warehouse->id
        );

        try {
            $document = Cache::lock('google-sheet-inventory-import:'.sha1($marker), 120)
                ->block(10, function () use ($preview, $warehouse, $selectedDate, $marker, $validated) {
                    $existingDocuments = InventoryDocument::query()
                        ->where('warehouse_id', $warehouse->id)
                        ->where('type', 'import')
                        ->where('notes', 'like', '%'.$marker.'%')
                        ->orderBy('id')
                        ->get();
                    if ($existingDocuments->isNotEmpty() && empty($validated['allow_duplicate'])) {
                        throw new RuntimeException('DUPLICATE:'.$existingDocuments->last()->id);
                    }

                    return DB::transaction(function () use ($preview, $warehouse, $selectedDate, $marker, $existingDocuments) {
                        $importNumber = $existingDocuments->count() + 1;
                        $document = InventoryDocument::create([
                            'type' => 'import',
                            'document_date' => $selectedDate,
                            'warehouse_id' => $warehouse->id,
                            'supplier_id' => null,
                            'shipping_fee' => 0,
                            'notes' => 'Nhập tồn đầu kỳ từ Google Sheet '.$preview['sheet_name']
                                .' ngày '.Carbon::parse($selectedDate)->format('d/m/Y')
                                .' – lần nhập '.$importNumber."\n".$marker,
                            'user_id' => Auth::id(),
                        ]);

                        foreach ($preview['import_rows'] as $row) {
                            $quantity = round((float) $row['quantity'], 3);
                            $variantId = (int) $row['variant_id'];
                            $document->items()->create([
                                'product_variant_id' => $variantId,
                                'quantity' => $quantity,
                                'unit_cost' => 0,
                                'note' => 'Google Sheet dòng '.$row['sheet_row'].' – '.$row['sheet_code'],
                            ]);

                            $inventory = Inventory::query()->firstOrCreate([
                                'product_variant_id' => $variantId,
                                'warehouse_id' => $warehouse->id,
                            ], ['quantity' => 0, 'reserved_quantity' => 0]);
                            $inventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();

                            InventoryMovement::create([
                                'inventory_id' => $inventory->id,
                                'quantity' => $quantity,
                                'type' => 'import',
                                'reference_id' => $document->id,
                                'reference_type' => InventoryDocument::class,
                                'user_id' => Auth::id(),
                            ]);
                            $inventory->increment('quantity', $quantity);

                            $totalStock = (float) Inventory::query()
                                ->where('product_variant_id', $variantId)
                                ->sum('quantity');
                            ProductVariant::query()->whereKey($variantId)->update(['stock' => $totalStock]);
                        }

                        return $document;
                    });
                });
        } catch (RuntimeException $exception) {
            if (str_starts_with($exception->getMessage(), 'DUPLICATE:')) {
                $documentId = (int) str($exception->getMessage())->after('DUPLICATE:')->toString();

                return redirect()->route('warehouse.google-sheet-inventory.index', [
                    'date' => $selectedDate,
                    'warehouse_id' => $warehouse->id,
                ])->with('warning', 'Tồn kho ngày '.Carbon::parse($selectedDate)->format('d/m/Y')
                    .' đã được nhập trước đó. Nếu vẫn muốn nhập thêm lần nữa, hãy tích xác nhận nhập lại bên cạnh nút nhập kho.');
            }
            throw $exception;
        }

        try {
            app(WarehouseDashboardController::class)
                ->refreshQueuedOrdersAfterInventoryChange((int) $warehouse->id);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('warehouse.stock-in.show', $document)
            ->with('success', 'Đã nhập '.number_format((float) $preview['total_quantity'], 0, ',', '.')
                .' sản phẩm từ Google Sheet vào '.$warehouse->name.'.');
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

    /** @param array<string, mixed> $preview */
    private function withInventoryState(array $preview, Warehouse $warehouse): array
    {
        $current = Inventory::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereIn('product_variant_id', $preview['rows']->pluck('variant_id')->filter()->all())
            ->pluck('quantity', 'product_variant_id');
        $preview['rows'] = $preview['rows']->map(function (array $row) use ($current): array {
            $row['current_quantity'] = $row['variant_id'] ? (float) ($current[$row['variant_id']] ?? 0) : null;
            $row['projected_quantity'] = $row['variant_id'] ? $row['current_quantity'] + $row['quantity'] : null;

            return $row;
        });

        return $preview;
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
