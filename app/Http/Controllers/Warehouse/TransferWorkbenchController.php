<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseDispatchSlip;
use App\Services\WarehouseDispatchFinalizationService;
use App\Services\WarehouseTransferCreationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransferWorkbenchController extends Controller
{
    public function pageData(Request $request, int $warehouseId): array
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(['ready_to_ship', 'packed', 'packed_waiting_pickup'])],
        ]);
        $businessDate = $data['date'] ?? now()->toDateString();
        $status = $data['status'] ?? '';
        $service = app(WarehouseTransferCreationService::class);
        // Match the existing order-transfer screen: creation, imported business day,
        // or the day the warehouse finished packing.
        $onDay = static function ($query, string $day): void {
            $query->where(function ($query) use ($day): void {
                $query->whereDate('created_at', $day)
                    ->orWhere(fn ($q) => $q->whereNotNull('accounting_sales_import_batch_id')->whereDate('delivery_date', $day))
                    ->orWhereHas('histories', fn ($q) => $q->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])->whereDate('created_at', $day));
            });
        };
        $ordersQuery = $service->transferableOrders($warehouseId);
        $onDay($ordersQuery, $businessDate);
        $orders = $ordersQuery->when($status, fn ($q) => $q->where('status', $status))
            ->with(['customer', 'items.variant.product', 'truckStation'])->orderBy('daily_sequence')->orderBy('id')->get();
        $quickDays = collect(range(0, 6))->map(function ($offset) use ($service, $warehouseId, $onDay) {
            $day = now()->subDays($offset)->toDateString();
            $query = $service->transferableOrders($warehouseId);
            $onDay($query, $day);

            return ['date' => $day, 'label' => $offset === 0 ? 'Hôm nay' : Carbon::parse($day)->format('d/m'), 'count' => $query->count()];
        });
        $shippers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'manager_shipper']))->orderBy('name')->get(['id', 'name']);
        $dispatchSlips = WarehouseDispatchSlip::where('source_warehouse_id', $warehouseId)
            ->where(fn ($query) => $query->whereDate('business_date', $businessDate)
                ->orWhere('status', WarehouseDispatchSlip::STATUS_DRAFT))
            ->with(['targetWarehouse', 'shipper', 'entries.inventoryTransfer.items', 'entries.orderTransfer.orders'])
            ->latest('id')->paginate(10, ['*'], 'slips_page')->withQueryString();

        return compact('orders', 'quickDays', 'businessDate', 'status', 'shippers', 'dispatchSlips');
    }

    public function store(Request $request, WarehouseTransferCreationService $service)
    {
        $warehouseId = (int) $request->user()->warehouse_id;
        abort_unless($warehouseId > 0, 403, 'Bạn chưa được gán kho quản lý.');
        $data = $request->validate([
            'submission_token' => ['required', 'uuid'],
            'business_date' => ['required', 'date_format:Y-m-d'],
            'groups' => ['required', 'array', 'min:1', 'max:100'],
            'groups.*.target_warehouse_id' => ['required', 'integer', 'distinct', 'exists:warehouses,id', Rule::notIn([$warehouseId])],
            'groups.*.shipper_id' => ['required', 'integer', 'exists:users,id'],
            'groups.*.note' => ['nullable', 'string', 'max:1000'],
            'groups.*.order_ids' => ['sometimes', 'array'],
            'groups.*.order_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'groups.*.items' => ['sometimes', 'array'],
            'groups.*.items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'groups.*.items.*.quantity' => ['required', 'integer', 'min:1'],
            'groups.*.items.*.weight_kg' => ['required', 'numeric', 'min:0.001', 'max:99999999'],
        ], [
            'groups.required' => 'Hãy chọn đơn hàng hoặc sản phẩm và kho nhận.',
            'groups.*.shipper_id.required' => 'Vui lòng chọn tài xế cho từng kho nhận.',
            'groups.*.target_warehouse_id.not_in' => 'Kho nhận phải khác kho nguồn.',
            'groups.*.order_ids.*.distinct' => 'Một đơn chỉ được chuyển đến một kho nhận.',
        ]);
        $shipperIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'manager_shipper']))->pluck('id');
        foreach ($data['groups'] as $group) {
            if (! $shipperIds->contains($group['shipper_id'])) {
                throw ValidationException::withMessages(['groups' => 'Tài xế được chọn không có quyền vận chuyển.']);
            }
            if (empty($group['order_ids']) && empty($group['items'])) {
                throw ValidationException::withMessages(['groups' => 'Mỗi kho nhận phải có ít nhất một đơn hoặc sản phẩm.']);
            }
        }

        // Serialize submissions from this source warehouse, including double clicks.
        // Store the receipt in the session so revisiting the same form cannot export twice.
        $lock = Cache::lock('warehouse-transfer-batch:'.$warehouseId, 120);
        if (! $lock->get()) {
            return back()->withInput()->withErrors(['groups' => 'Kho đang xử lý điều chuyển. Vui lòng thử lại sau ít giây.']);
        }
        try {
            $receiptKey = 'transfer_receipts.'.$data['submission_token'];
            $codes = $request->session()->get($receiptKey);
            if (! $codes) {
                $codes = DB::transaction(function () use ($data, $warehouseId, $service): array {
                    // Lock the source first to keep batches serialized even if a cache lock expires.
                    Warehouse::whereKey($warehouseId)->lockForUpdate()->firstOrFail();
                    $codes = [];
                    foreach ($data['groups'] as $group) {
                        $targetId = (int) $group['target_warehouse_id'];
                        $slip = WarehouseDispatchSlip::create([
                            'business_date' => $data['business_date'],
                            'source_warehouse_id' => $warehouseId,
                            'target_warehouse_id' => $targetId,
                            'shipper_id' => $group['shipper_id'],
                            'status' => WarehouseDispatchSlip::STATUS_DRAFT,
                            'notes' => $group['note'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                        if (! empty($group['order_ids'])) {
                            $transfer = $service->createOrders($warehouseId, [
                                'warehouse_id' => $targetId, 'shipper_id' => $group['shipper_id'],
                            ], $group['order_ids']);
                            $slip->entries()->create(['order_transfer_id' => $transfer->id]);
                        }
                        if (! empty($group['items'])) {
                            $transfer = $service->createInventory($warehouseId, $targetId, $group + ['business_date' => $data['business_date']]);
                            $slip->entries()->create(['inventory_transfer_id' => $transfer->id]);
                        }
                        app(WarehouseDispatchFinalizationService::class)->finalize($slip, (int) auth()->id());
                        $codes[] = $slip->code;
                    }

                    return $codes;
                });
                $request->session()->put($receiptKey, $codes);
            }
        } catch (\RuntimeException $exception) {
            return back()->withInput()->withErrors(['groups' => $exception->getMessage()]);
        } finally {
            $lock->release();
        }

        return redirect()->route('warehouse.inventory-transfers.index', ['date' => $data['business_date']])
            ->with('success', 'Đã chốt điều chuyển và xuất phiếu tổng: '.implode(', ', $codes).'. Shipper có thể nhận hàng; bạn có thể in phiếu ở danh sách bên dưới.');
    }
}
