<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseDispatchSlip;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseDispatchSlipController extends Controller
{
    public function ceoIndex(Request $request)
    {
        $from = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->toDateString()
            : now()->startOfMonth()->toDateString();
        $to = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->toDateString()
            : now()->toDateString();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $slips = WarehouseDispatchSlip::query()
            ->with([
                'sourceWarehouse:id,name',
                'targetWarehouse:id,name',
                'shipper:id,name,short_name',
                'entries.orderTransfer.orders.warehouseTransfers',
                'entries.warehouseTransfer',
                'entries.inventoryTransfer',
            ])
            ->whereBetween('business_date', [$from, $to])
            ->when($request->integer('source_warehouse_id') > 0, fn ($query) => $query->where('source_warehouse_id', $request->integer('source_warehouse_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('search'), fn ($query) => $query->where('code', 'like', '%'.trim((string) $request->input('search')).'%'))
            ->latest('business_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $slips->getCollection()->each(fn (WarehouseDispatchSlip $slip) => $this->attachProgress($slip));
        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);

        return view('ceo.warehouse-dispatch-slips.index', compact('slips', 'warehouses', 'from', 'to'));
    }

    public function ceoShow(WarehouseDispatchSlip $dispatchSlip)
    {
        $this->loadSlip($dispatchSlip);
        $this->attachProgress($dispatchSlip);

        return view('warehouse.dispatch-slips.show', [
            'slip' => $dispatchSlip,
            'layout' => 'layouts.ceo',
            'dispatchRoutePrefix' => 'ceo.warehouse-dispatch-slips',
            'readOnly' => true,
        ] + $this->documentData($dispatchSlip));
    }

    public function ceoPrintExport(WarehouseDispatchSlip $dispatchSlip)
    {
        $this->loadSlip($dispatchSlip);
        $this->attachProgress($dispatchSlip);
        $dispatchSlip->increment('print_count');

        return view('warehouse.dispatch-slips.print-export', ['slip' => $dispatchSlip] + $this->documentData($dispatchSlip));
    }

    public function index(Request $request)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $from = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->toDateString()
            : now()->toDateString();
        $to = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->toDateString()
            : now()->toDateString();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $slips = WarehouseDispatchSlip::query()
            ->with(['sourceWarehouse:id,name', 'targetWarehouse:id,name', 'shipper:id,name,short_name', 'creator:id,name', 'entries.orderTransfer.orders.warehouseTransfers', 'entries.warehouseTransfer', 'entries.inventoryTransfer'])
            ->when($managedWarehouseId, fn ($query) => $query->where(function ($warehouseQuery) use ($managedWarehouseId): void {
                $warehouseQuery->where('source_warehouse_id', $managedWarehouseId)
                    ->orWhere('target_warehouse_id', $managedWarehouseId);
            }))
            ->whereBetween('business_date', [$from, $to])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('shipper_id'), fn ($query) => $query->where('shipper_id', $request->integer('shipper_id')))
            ->when($request->filled('search'), fn ($query) => $query->where('code', 'like', '%'.trim((string) $request->input('search')).'%'))
            ->latest('business_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $slips->getCollection()->each(fn (WarehouseDispatchSlip $slip) => $this->attachProgress($slip));

        $sourceWarehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);
        $sourceWarehouseId = $managedWarehouseId ?: (int) ($request->input('source_warehouse_id') ?: $sourceWarehouses->first()?->id);
        $targetWarehouses = Warehouse::query()->whereKeyNot($sourceWarehouseId)->orderBy('name')->get(['id', 'name']);
        $shippers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['shipper', 'manager_shipper']))
            ->orderBy('name')->get(['id', 'name', 'short_name']);

        $orderTransfers = OrderTransfer::query()
            ->with(['shipper:id,name,short_name', 'warehouse:id,name', 'orders.customer:id,name', 'orders.items.variant.product', 'orders.warehouseTransfers' => fn ($query) => $query->latest('id')])
            ->whereDoesntHave('dispatchEntry')
            ->whereHas('orders.warehouseTransfers', fn ($query) => $query
                ->where('source_warehouse_id', $sourceWarehouseId)
                ->where('status', WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP))
            ->latest('id')->get()
            ->filter(function (OrderTransfer $transfer): bool {
                if ($transfer->orders->isEmpty()) {
                    return false;
                }

                return $transfer->orders->every(function (Order $order): bool {
                    return $order->warehouseTransfers->first()?->status === WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP;
                });
            })->values();

        $inventoryTransfers = WarehouseInventoryTransfer::query()
            ->with(['targetWarehouse:id,name', 'items.variant.product'])
            ->where('source_warehouse_id', $sourceWarehouseId)
            ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
            ->whereDoesntHave('dispatchEntry')
            ->latest('id')->get();

        // Phiếu điều chuyển được tạo trực tiếp từ màn hình đóng hàng không có
        // OrderTransfer cha. Tải riêng các phiếu này để kho vẫn lập được phiếu tổng.
        $warehouseTransfers = WarehouseTransfer::query()
            ->with(['order.customer:id,name', 'order.user:id,name,short_name', 'order.items.variant.product', 'targetWarehouse:id,name', 'shipper:id,name,short_name'])
            ->where('source_warehouse_id', $sourceWarehouseId)
            ->where('status', WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP)
            ->whereDoesntHave('dispatchEntry')
            ->whereHas('order', fn ($query) => $query->whereNull('order_transfer_id'))
            ->latest('id')->get();

        return view('warehouse.dispatch-slips.index', compact(
            'slips', 'from', 'to', 'managedWarehouseId', 'sourceWarehouseId',
            'sourceWarehouses', 'targetWarehouses', 'shippers', 'orderTransfers',
            'warehouseTransfers', 'inventoryTransfers'
        ));
    }

    public function store(Request $request)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $validated = $request->validate([
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'target_warehouse_id' => ['required', 'integer', 'different:source_warehouse_id', 'exists:warehouses,id'],
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
            'business_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'order_transfer_ids' => ['nullable', 'array'],
            'order_transfer_ids.*' => ['integer', 'exists:order_transfers,id'],
            'warehouse_transfer_ids' => ['nullable', 'array'],
            'warehouse_transfer_ids.*' => ['integer', 'exists:warehouse_transfers,id'],
            'inventory_transfer_ids' => ['nullable', 'array'],
            'inventory_transfer_ids.*' => ['integer', 'exists:warehouse_inventory_transfers,id'],
        ]);

        $sourceWarehouseId = (int) $validated['source_warehouse_id'];
        $targetWarehouseId = (int) $validated['target_warehouse_id'];
        $shipperId = (int) $validated['shipper_id'];
        if ($managedWarehouseId && $sourceWarehouseId !== $managedWarehouseId) {
            abort(403, 'Bạn chỉ có thể lập phiếu cho kho mình quản lý.');
        }

        $orderTransferIds = collect($validated['order_transfer_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $warehouseTransferIds = collect($validated['warehouse_transfer_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $inventoryTransferIds = collect($validated['inventory_transfer_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($orderTransferIds->isEmpty() && $warehouseTransferIds->isEmpty() && $inventoryTransferIds->isEmpty()) {
            throw ValidationException::withMessages(['entries' => 'Vui lòng chọn ít nhất một nhóm đơn hoặc một phiếu điều chuyển hàng.']);
        }

        $slip = DB::transaction(function () use ($validated, $sourceWarehouseId, $targetWarehouseId, $shipperId, $orderTransferIds, $warehouseTransferIds, $inventoryTransferIds): WarehouseDispatchSlip {
            $orderTransfers = OrderTransfer::query()
                ->with(['dispatchEntry', 'orders.warehouseTransfers' => fn ($query) => $query->latest('id')])
                ->whereIn('id', $orderTransferIds)->lockForUpdate()->get();
            $inventoryTransfers = WarehouseInventoryTransfer::query()
                ->with('dispatchEntry')->whereIn('id', $inventoryTransferIds)->lockForUpdate()->get();
            $warehouseTransfers = WarehouseTransfer::query()
                ->with(['dispatchEntry', 'order.orderTransfer.dispatchEntry'])
                ->whereIn('id', $warehouseTransferIds)->lockForUpdate()->get();

            if ($orderTransfers->count() !== $orderTransferIds->count()
                || $warehouseTransfers->count() !== $warehouseTransferIds->count()
                || $inventoryTransfers->count() !== $inventoryTransferIds->count()) {
                throw ValidationException::withMessages(['entries' => 'Một số nội dung được chọn không còn tồn tại.']);
            }

            foreach ($orderTransfers as $transfer) {
                $valid = ! $transfer->dispatchEntry
                    && (int) $transfer->warehouse_id === $targetWarehouseId
                    && (int) $transfer->shipper_id === $shipperId
                    && $transfer->orders->isNotEmpty()
                    && $transfer->orders->every(function (Order $order) use ($sourceWarehouseId): bool {
                        $movement = $order->warehouseTransfers->first();

                        return $movement
                            && (int) $movement->source_warehouse_id === $sourceWarehouseId
                            && $movement->status === WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP;
                    });
                if (! $valid) {
                    throw ValidationException::withMessages(['entries' => 'Nhóm đơn #'.$transfer->id.' không cùng tài xế/kho hoặc đã bắt đầu vận chuyển.']);
                }
            }

            foreach ($warehouseTransfers as $transfer) {
                if ($transfer->dispatchEntry
                    || $transfer->order?->orderTransfer?->dispatchEntry
                    || (int) $transfer->source_warehouse_id !== $sourceWarehouseId
                    || (int) $transfer->target_warehouse_id !== $targetWarehouseId
                    || (int) $transfer->shipper_id !== $shipperId
                    || $transfer->status !== WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP) {
                    throw ValidationException::withMessages(['entries' => 'Phiếu điều chuyển đơn #'.$transfer->id.' không cùng tài xế/kho, đã thuộc phiếu tổng khác hoặc đã bắt đầu vận chuyển.']);
                }
            }

            foreach ($inventoryTransfers as $transfer) {
                if ($transfer->dispatchEntry
                    || (int) $transfer->source_warehouse_id !== $sourceWarehouseId
                    || (int) $transfer->target_warehouse_id !== $targetWarehouseId
                    || $transfer->status !== WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE) {
                    throw ValidationException::withMessages(['entries' => 'Phiếu hàng '.($transfer->transfer_code ?: '#'.$transfer->id).' không hợp lệ hoặc đã thuộc phiếu tổng khác.']);
                }
            }

            $slip = WarehouseDispatchSlip::create([
                'business_date' => $validated['business_date'],
                'source_warehouse_id' => $sourceWarehouseId,
                'target_warehouse_id' => $targetWarehouseId,
                'shipper_id' => $shipperId,
                'status' => WarehouseDispatchSlip::STATUS_DRAFT,
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'created_by' => Auth::id(),
            ]);
            foreach ($orderTransfers as $transfer) {
                $slip->entries()->create(['order_transfer_id' => $transfer->id]);
            }
            foreach ($warehouseTransfers as $transfer) {
                $slip->entries()->create(['warehouse_transfer_id' => $transfer->id]);
            }
            foreach ($inventoryTransfers as $transfer) {
                $slip->entries()->create(['inventory_transfer_id' => $transfer->id]);
            }

            return $slip;
        });

        return redirect()->route('warehouse.dispatch-slips.show', $slip)
            ->with('success', 'Đã lập phiếu xuất kho tổng '.$slip->code.'. Vui lòng kiểm tra trước khi chốt.');
    }

    public function show(WarehouseDispatchSlip $dispatchSlip)
    {
        $this->authorizeSlip($dispatchSlip);
        $this->loadSlip($dispatchSlip);
        $this->attachProgress($dispatchSlip);

        return view('warehouse.dispatch-slips.show', ['slip' => $dispatchSlip] + $this->documentData($dispatchSlip));
    }

    public function edit(WarehouseDispatchSlip $dispatchSlip)
    {
        $this->authorizeSource($dispatchSlip);
        if ($dispatchSlip->status !== WarehouseDispatchSlip::STATUS_DRAFT) {
            return redirect()->route('warehouse.dispatch-slips.show', $dispatchSlip)
                ->with('error', 'Phiếu đã chốt nên không thể sửa.');
        }

        $dispatchSlip->load(['entries', 'sourceWarehouse:id,name']);
        $sourceWarehouseId = (int) $dispatchSlip->source_warehouse_id;
        $targetWarehouses = Warehouse::query()->whereKeyNot($sourceWarehouseId)->orderBy('name')->get(['id', 'name']);
        $shippers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['shipper', 'manager_shipper']))
            ->orderBy('name')->get(['id', 'name', 'short_name']);

        [$orderTransfers, $warehouseTransfers, $inventoryTransfers] = $this->editableEntries($dispatchSlip);

        return view('warehouse.dispatch-slips.edit', compact(
            'dispatchSlip', 'targetWarehouses', 'shippers', 'orderTransfers',
            'warehouseTransfers', 'inventoryTransfers'
        ));
    }

    public function update(Request $request, WarehouseDispatchSlip $dispatchSlip)
    {
        $this->authorizeSource($dispatchSlip);
        if ($dispatchSlip->status !== WarehouseDispatchSlip::STATUS_DRAFT) {
            return back()->with('error', 'Phiếu đã chốt nên không thể sửa.');
        }

        $validated = $request->validate([
            'target_warehouse_id' => ['required', 'integer', 'different:source_warehouse_id', 'exists:warehouses,id'],
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
            'business_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'order_transfer_ids' => ['nullable', 'array'],
            'order_transfer_ids.*' => ['integer', 'exists:order_transfers,id'],
            'warehouse_transfer_ids' => ['nullable', 'array'],
            'warehouse_transfer_ids.*' => ['integer', 'exists:warehouse_transfers,id'],
            'inventory_transfer_ids' => ['nullable', 'array'],
            'inventory_transfer_ids.*' => ['integer', 'exists:warehouse_inventory_transfers,id'],
        ]);

        $sourceWarehouseId = (int) $dispatchSlip->source_warehouse_id;
        $targetWarehouseId = (int) $validated['target_warehouse_id'];
        $shipperId = (int) $validated['shipper_id'];
        if ($targetWarehouseId === $sourceWarehouseId) {
            throw ValidationException::withMessages(['target_warehouse_id' => 'Kho nhận phải khác kho xuất.']);
        }
        $orderTransferIds = collect($validated['order_transfer_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $warehouseTransferIds = collect($validated['warehouse_transfer_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $inventoryTransferIds = collect($validated['inventory_transfer_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($orderTransferIds->isEmpty() && $warehouseTransferIds->isEmpty() && $inventoryTransferIds->isEmpty()) {
            throw ValidationException::withMessages(['entries' => 'Phiếu phải có ít nhất một nội dung bàn giao.']);
        }

        DB::transaction(function () use ($dispatchSlip, $validated, $sourceWarehouseId, $targetWarehouseId, $shipperId, $orderTransferIds, $warehouseTransferIds, $inventoryTransferIds): void {
            $lockedSlip = WarehouseDispatchSlip::query()->lockForUpdate()->findOrFail($dispatchSlip->id);
            if ($lockedSlip->status !== WarehouseDispatchSlip::STATUS_DRAFT) {
                throw ValidationException::withMessages(['status' => 'Phiếu vừa được chốt nên không thể sửa.']);
            }
            $orderTransfers = OrderTransfer::query()
                ->with(['dispatchEntry', 'orders.warehouseTransfers' => fn ($query) => $query->latest('id')])
                ->whereIn('id', $orderTransferIds)->lockForUpdate()->get();
            $warehouseTransfers = WarehouseTransfer::query()
                ->with(['dispatchEntry', 'order.orderTransfer.dispatchEntry'])
                ->whereIn('id', $warehouseTransferIds)->lockForUpdate()->get();
            $inventoryTransfers = WarehouseInventoryTransfer::query()
                ->with('dispatchEntry')->whereIn('id', $inventoryTransferIds)->lockForUpdate()->get();

            if ($orderTransfers->count() !== $orderTransferIds->count()
                || $warehouseTransfers->count() !== $warehouseTransferIds->count()
                || $inventoryTransfers->count() !== $inventoryTransferIds->count()) {
                throw ValidationException::withMessages(['entries' => 'Một số nội dung được chọn không còn tồn tại.']);
            }

            foreach ($orderTransfers as $transfer) {
                $belongsToThisSlip = (int) $transfer->dispatchEntry?->warehouse_dispatch_slip_id === (int) $dispatchSlip->id;
                $valid = (! $transfer->dispatchEntry || $belongsToThisSlip)
                    && (int) $transfer->warehouse_id === $targetWarehouseId
                    && (int) $transfer->shipper_id === $shipperId
                    && $transfer->orders->isNotEmpty()
                    && $transfer->orders->every(function (Order $order) use ($sourceWarehouseId): bool {
                        $movement = $order->warehouseTransfers->first();

                        return $movement
                            && (int) $movement->source_warehouse_id === $sourceWarehouseId
                            && $movement->status === WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP;
                    });
                if (! $valid) {
                    throw ValidationException::withMessages(['entries' => 'Nhóm đơn #'.$transfer->id.' không cùng tài xế/kho, đã thuộc phiếu khác hoặc đã bắt đầu vận chuyển.']);
                }
            }

            foreach ($warehouseTransfers as $transfer) {
                $entrySlipId = (int) $transfer->dispatchEntry?->warehouse_dispatch_slip_id;
                $parentEntrySlipId = (int) $transfer->order?->orderTransfer?->dispatchEntry?->warehouse_dispatch_slip_id;
                if (($transfer->dispatchEntry && $entrySlipId !== (int) $dispatchSlip->id)
                    || ($transfer->order?->orderTransfer?->dispatchEntry && $parentEntrySlipId !== (int) $dispatchSlip->id)
                    || (int) $transfer->source_warehouse_id !== $sourceWarehouseId
                    || (int) $transfer->target_warehouse_id !== $targetWarehouseId
                    || (int) $transfer->shipper_id !== $shipperId
                    || $transfer->status !== WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP) {
                    throw ValidationException::withMessages(['entries' => 'Phiếu điều chuyển đơn #'.$transfer->id.' không hợp lệ hoặc đã thuộc phiếu tổng khác.']);
                }
            }

            foreach ($inventoryTransfers as $transfer) {
                $belongsToThisSlip = (int) $transfer->dispatchEntry?->warehouse_dispatch_slip_id === (int) $dispatchSlip->id;
                if (($transfer->dispatchEntry && ! $belongsToThisSlip)
                    || (int) $transfer->source_warehouse_id !== $sourceWarehouseId
                    || (int) $transfer->target_warehouse_id !== $targetWarehouseId
                    || $transfer->status !== WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE) {
                    throw ValidationException::withMessages(['entries' => 'Phiếu hàng '.($transfer->transfer_code ?: '#'.$transfer->id).' không hợp lệ hoặc đã thuộc phiếu tổng khác.']);
                }
            }

            $dispatchSlip->update([
                'business_date' => $validated['business_date'],
                'target_warehouse_id' => $targetWarehouseId,
                'shipper_id' => $shipperId,
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            ]);
            $dispatchSlip->entries()->delete();
            foreach ($orderTransfers as $transfer) {
                $dispatchSlip->entries()->create(['order_transfer_id' => $transfer->id]);
            }
            foreach ($warehouseTransfers as $transfer) {
                $dispatchSlip->entries()->create(['warehouse_transfer_id' => $transfer->id]);
            }
            foreach ($inventoryTransfers as $transfer) {
                $dispatchSlip->entries()->create(['inventory_transfer_id' => $transfer->id]);
            }
        });

        return redirect()->route('warehouse.dispatch-slips.show', $dispatchSlip)
            ->with('success', 'Đã cập nhật phiếu xuất kho tổng '.$dispatchSlip->code.'.');
    }

    public function finalize(WarehouseDispatchSlip $dispatchSlip)
    {
        $this->authorizeSource($dispatchSlip);
        if ($dispatchSlip->status !== WarehouseDispatchSlip::STATUS_DRAFT) {
            return back()->with('error', 'Phiếu đã được chốt hoặc hủy.');
        }
        if (! $dispatchSlip->entries()->exists()) {
            return back()->with('error', 'Không thể chốt phiếu chưa có nội dung.');
        }

        $this->loadSlip($dispatchSlip);
        DB::transaction(function () use ($dispatchSlip): void {
            foreach ($dispatchSlip->entries as $entry) {
                $entry->update(['snapshot' => $this->entrySnapshot($entry)]);
            }
            $dispatchSlip->update([
                'status' => WarehouseDispatchSlip::STATUS_FINALIZED,
                'finalized_by' => Auth::id(),
                'finalized_at' => now(),
            ]);
        });

        return back()->with('success', 'Đã chốt phiếu. Danh sách bàn giao đã được khóa.');
    }

    public function destroy(WarehouseDispatchSlip $dispatchSlip)
    {
        $this->authorizeSource($dispatchSlip);
        if ($dispatchSlip->status !== WarehouseDispatchSlip::STATUS_DRAFT) {
            return back()->with('error', 'Chỉ được xóa phiếu đang mở.');
        }
        $dispatchSlip->delete();

        return redirect()->route('warehouse.dispatch-slips.index')->with('success', 'Đã xóa phiếu tổng đang mở.');
    }

    public function printExport(WarehouseDispatchSlip $dispatchSlip)
    {
        $this->authorizeSlip($dispatchSlip);
        $this->loadSlip($dispatchSlip);
        $this->attachProgress($dispatchSlip);
        $dispatchSlip->increment('print_count');

        return view('warehouse.dispatch-slips.print-export', ['slip' => $dispatchSlip] + $this->documentData($dispatchSlip));
    }

    public function printImport(WarehouseDispatchSlip $dispatchSlip)
    {
        $this->authorizeSlip($dispatchSlip);
        $this->loadSlip($dispatchSlip);
        $this->attachProgress($dispatchSlip);

        return view('warehouse.dispatch-slips.print-import', ['slip' => $dispatchSlip] + $this->documentData($dispatchSlip));
    }

    private function loadSlip(WarehouseDispatchSlip $slip): void
    {
        $slip->load([
            'sourceWarehouse:id,name,address,phone', 'targetWarehouse:id,name,address,phone',
            'shipper:id,name,short_name,phone', 'creator:id,name', 'finalizer:id,name',
            'entries.orderTransfer.orders.customer:id,name',
            'entries.orderTransfer.orders.user:id,name,short_name',
            'entries.orderTransfer.orders.items.variant.product',
            'entries.orderTransfer.orders.warehouseTransfers' => fn ($query) => $query->latest('id'),
            'entries.warehouseTransfer.order.customer:id,name',
            'entries.warehouseTransfer.order.user:id,name,short_name',
            'entries.warehouseTransfer.order.items.variant.product',
            'entries.inventoryTransfer.items.variant.product',
            'entries.inventoryTransfer.receiver:id,name',
        ]);
    }

    private function editableEntries(WarehouseDispatchSlip $slip): array
    {
        $sourceWarehouseId = (int) $slip->source_warehouse_id;
        $slipId = (int) $slip->id;
        $available = static fn ($query) => $query->where(function ($entryQuery) use ($slipId): void {
            $entryQuery->whereDoesntHave('dispatchEntry')
                ->orWhereHas('dispatchEntry', fn ($dispatchQuery) => $dispatchQuery->where('warehouse_dispatch_slip_id', $slipId));
        });

        $orderTransfers = OrderTransfer::query()
            ->with(['shipper:id,name,short_name', 'warehouse:id,name', 'orders.customer:id,name', 'orders.items.variant.product', 'orders.warehouseTransfers' => fn ($query) => $query->latest('id'), 'dispatchEntry'])
            ->where($available)
            ->whereHas('orders.warehouseTransfers', fn ($query) => $query
                ->where('source_warehouse_id', $sourceWarehouseId)
                ->where('status', WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP))
            ->latest('id')->get()
            ->filter(fn (OrderTransfer $transfer): bool => $transfer->orders->isNotEmpty()
                && $transfer->orders->every(fn (Order $order): bool => $order->warehouseTransfers->first()?->status === WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP))
            ->values();

        $warehouseTransfers = WarehouseTransfer::query()
            ->with(['order.customer:id,name', 'order.items.variant.product', 'targetWarehouse:id,name', 'shipper:id,name,short_name', 'dispatchEntry'])
            ->where($available)
            ->where('source_warehouse_id', $sourceWarehouseId)
            ->where('status', WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP)
            ->whereHas('order', fn ($query) => $query->whereNull('order_transfer_id'))
            ->latest('id')->get();

        $inventoryTransfers = WarehouseInventoryTransfer::query()
            ->with(['targetWarehouse:id,name', 'items.variant.product', 'dispatchEntry'])
            ->where($available)
            ->where('source_warehouse_id', $sourceWarehouseId)
            ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
            ->latest('id')->get();

        return [$orderTransfers, $warehouseTransfers, $inventoryTransfers];
    }

    private function documentData(WarehouseDispatchSlip $slip): array
    {
        $orderRows = collect();
        $itemRows = collect();
        $inventoryTransferRows = collect();

        foreach ($slip->entries as $entry) {
            if ($entry->orderTransfer) {
                foreach ($entry->orderTransfer->orders as $order) {
                    $movement = $order->warehouseTransfers->first();
                    $orderSnapshot = collect($entry->snapshot['orders'] ?? [])->firstWhere('id', $order->id);
                    $orderRows->push([
                        'order' => $order,
                        'code' => $orderSnapshot['code'] ?? ($order->code ?: '#'.$order->id),
                        'customer_name' => $orderSnapshot['customer_name'] ?? $order->customer?->name,
                        'sale_name' => $orderSnapshot['sale_name'] ?? ($order->user?->short_name ?: $order->user?->name),
                        'order_note' => $orderSnapshot['note'] ?? $order->note,
                        'package_count' => $orderSnapshot['package_count'] ?? $order->package_count,
                        'packing_specification' => $orderSnapshot['packing_specification'] ?? $order->packing_specification,
                        'foam_box_fee' => (float) ($orderSnapshot['foam_box_fee'] ?? (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0)),
                        'shipping_fee' => (float) ($orderSnapshot['shipping_fee'] ?? $this->billableShippingFee($order)),
                        'discount' => (float) ($orderSnapshot['discount'] ?? $order->total_discount ?? 0),
                        'item_quantity' => (int) ($orderSnapshot['item_quantity'] ?? $order->items->sum('quantity')),
                        'sizes' => $this->orderSizes($order, (array) $orderSnapshot),
                        'packed_weight' => (float) ($orderSnapshot['packed_weight'] ?? $movement?->packed_total_weight ?? 0),
                        'movement' => $movement,
                        'received' => $movement?->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
                    ]);
                    $receivedWeights = collect($movement?->received_weights ?? [])->keyBy('order_item_id');
                    $expectedItems = collect($orderSnapshot['items'] ?? [])->isNotEmpty()
                        ? collect($orderSnapshot['items'])->map(fn (array $item) => (object) $item)
                        : $order->items;
                    foreach ($expectedItems as $item) {
                        if (! $item->product_variant_id) {
                            continue;
                        }
                        $liveItem = $order->items->firstWhere('id', $item->id ?? null);
                        $variant = $liveItem?->variant ?? $order->items->firstWhere('product_variant_id', $item->product_variant_id)?->variant;
                        $itemRows->push([
                            'source' => 'Đơn '.($order->code ?: '#'.$order->id),
                            'variant_id' => (int) $item->product_variant_id,
                            'product_name' => $item->product_name ?? $variant?->product?->name ?? $variant?->name ?? 'Sản phẩm',
                            'sku' => $item->sku ?? $variant?->sku,
                            'size' => $item->size ?? $variant?->size,
                            'quantity' => (int) $item->quantity,
                            'weight' => (float) ($item->weight ?? $item->packed_weight ?? $item->total_weight ?? 0),
                            'received_quantity' => $movement?->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED ? (int) $item->quantity : null,
                            'received_weight' => $movement?->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED
                                ? (float) ($receivedWeights->get($item->id ?? 0)['received_weight'] ?? 0) : null,
                        ]);
                    }
                }
            }

            if ($entry->warehouseTransfer?->order) {
                $movement = $entry->warehouseTransfer;
                $order = $movement->order;
                $orderSnapshot = $entry->snapshot['order'] ?? [];
                $orderRows->push([
                    'order' => $order,
                    'code' => $orderSnapshot['code'] ?? ($order->code ?: '#'.$order->id),
                    'customer_name' => $orderSnapshot['customer_name'] ?? $order->customer?->name,
                    'sale_name' => $orderSnapshot['sale_name'] ?? ($order->user?->short_name ?: $order->user?->name),
                    'order_note' => $orderSnapshot['note'] ?? $order->note,
                    'package_count' => $orderSnapshot['package_count'] ?? $order->package_count,
                    'packing_specification' => $orderSnapshot['packing_specification'] ?? $order->packing_specification,
                    'foam_box_fee' => (float) ($orderSnapshot['foam_box_fee'] ?? (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0)),
                    'shipping_fee' => (float) ($orderSnapshot['shipping_fee'] ?? $this->billableShippingFee($order)),
                    'discount' => (float) ($orderSnapshot['discount'] ?? $order->total_discount ?? 0),
                    'item_quantity' => (int) ($orderSnapshot['item_quantity'] ?? $order->items->sum('quantity')),
                    'sizes' => $this->orderSizes($order, (array) $orderSnapshot),
                    'packed_weight' => (float) ($orderSnapshot['packed_weight'] ?? $movement->packed_total_weight ?? 0),
                    'movement' => $movement,
                    'received' => $movement->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
                ]);
                $receivedWeights = collect($movement->received_weights ?? [])->keyBy('order_item_id');
                $expectedItems = ! empty($orderSnapshot['items'])
                    ? collect($orderSnapshot['items'])->map(fn (array $item) => (object) $item)
                    : $order->items;
                foreach ($expectedItems as $item) {
                    if (! $item->product_variant_id) {
                        continue;
                    }
                    $liveItem = $order->items->firstWhere('id', $item->id ?? null);
                    $variant = $liveItem?->variant ?? $order->items->firstWhere('product_variant_id', $item->product_variant_id)?->variant;
                    $itemRows->push([
                        'source' => 'Đơn '.($orderSnapshot['code'] ?? ($order->code ?: '#'.$order->id)),
                        'variant_id' => (int) $item->product_variant_id,
                        'product_name' => $item->product_name ?? $variant?->product?->name ?? $variant?->name ?? 'Sản phẩm',
                        'sku' => $item->sku ?? $variant?->sku,
                        'size' => $item->size ?? $variant?->size,
                        'quantity' => (int) $item->quantity,
                        'weight' => (float) ($item->weight ?? $item->packed_weight ?? $item->total_weight ?? 0),
                        'received_quantity' => $movement->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED ? (int) $item->quantity : null,
                        'received_weight' => $movement->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED
                            ? (float) ($receivedWeights->get($item->id ?? 0)['received_weight'] ?? 0) : null,
                    ]);
                }
            }

            if ($entry->inventoryTransfer) {
                $transfer = $entry->inventoryTransfer;
                $inventorySnapshot = $entry->snapshot['inventory_transfer'] ?? null;
                $expectedItems = ! empty($inventorySnapshot['items'])
                    ? collect($inventorySnapshot['items'])->map(fn (array $item) => (object) $item)
                    : $transfer->items;
                $inventoryTransferRows->push([
                    'code' => $inventorySnapshot['code'] ?? ($transfer->transfer_code ?: '#'.$transfer->id),
                    'note' => $inventorySnapshot['note'] ?? $transfer->note,
                    'item_count' => $expectedItems->count(),
                    'quantity' => (int) $expectedItems->sum('quantity'),
                    'weight' => round((float) $expectedItems->sum('weight_kg'), 3),
                ]);
                foreach ($expectedItems as $item) {
                    $received = $transfer->status === WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED;
                    $variant = $transfer->items->firstWhere('product_variant_id', $item->product_variant_id)?->variant;
                    $itemRows->push([
                        'source' => 'Hàng '.($inventorySnapshot['code'] ?? ($transfer->transfer_code ?: '#'.$transfer->id)),
                        'variant_id' => (int) $item->product_variant_id,
                        'product_name' => $item->product_name ?? $variant?->product?->name ?? $variant?->name ?? 'Sản phẩm',
                        'sku' => $item->sku ?? $variant?->sku,
                        'size' => $item->size ?? $variant?->size,
                        'quantity' => (int) $item->quantity,
                        'weight' => (float) $item->weight_kg,
                        'received_quantity' => $received ? (int) $item->quantity : null,
                        'received_weight' => $received ? (float) $item->weight_kg : null,
                    ]);
                }
            }
        }

        $summaryRows = $itemRows->groupBy('variant_id')->map(function (Collection $rows): array {
            $first = $rows->first();
            $receivedRows = $rows->whereNotNull('received_quantity');

            return [
                'product_name' => $first['product_name'],
                'sku' => $first['sku'],
                'size' => $first['size'],
                'quantity' => (int) $rows->sum('quantity'),
                'weight' => round((float) $rows->sum('weight'), 3),
                'received_quantity' => $receivedRows->isEmpty() ? null : (int) $receivedRows->sum('received_quantity'),
                'received_weight' => $receivedRows->isEmpty() ? null : round((float) $receivedRows->sum('received_weight'), 3),
            ];
        })->values();

        return compact('orderRows', 'itemRows', 'summaryRows', 'inventoryTransferRows');
    }

    private function orderSizes(Order $order, array $snapshot = []): string
    {
        $sizes = collect($snapshot['items'] ?? [])
            ->pluck('size')
            ->map(fn ($size) => trim((string) $size))
            ->filter();

        if ($sizes->isEmpty()) {
            $sizes = $order->items
                ->map(fn ($item) => trim((string) ($item->variant?->size ?? '')))
                ->filter();
        }

        return $sizes->unique()->values()->join(', ') ?: '—';
    }

    private function entrySnapshot($entry): array
    {
        if ($entry->orderTransfer) {
            return [
                'type' => 'order_transfer',
                'order_transfer_id' => $entry->orderTransfer->id,
                'orders' => $entry->orderTransfer->orders->map(function (Order $order): array {
                    $movement = $order->warehouseTransfers->first();

                    return [
                        'id' => $order->id,
                        'code' => $order->code ?: '#'.$order->id,
                        'customer_name' => $order->customer?->name,
                        'sale_name' => $order->user?->short_name ?: $order->user?->name,
                        'note' => $order->note,
                        'package_count' => $order->package_count,
                        'packing_specification' => $order->packing_specification,
                        'foam_box_fee' => (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0),
                        'shipping_fee' => $this->billableShippingFee($order),
                        'discount' => (float) ($order->total_discount ?? 0),
                        'item_quantity' => (int) $order->items->sum('quantity'),
                        'packed_weight' => (float) ($movement?->packed_total_weight ?? 0),
                        'items' => $order->items->filter(fn ($item) => $item->product_variant_id)->map(fn ($item) => [
                            'id' => $item->id,
                            'product_variant_id' => (int) $item->product_variant_id,
                            'product_name' => $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm',
                            'sku' => $item->variant?->sku,
                            'size' => $item->variant?->size,
                            'quantity' => (int) $item->quantity,
                            'weight' => (float) ($item->packed_weight ?? $item->total_weight ?? 0),
                        ])->values()->all(),
                    ];
                })->values()->all(),
            ];
        }

        if ($entry->warehouseTransfer?->order) {
            $movement = $entry->warehouseTransfer;
            $order = $movement->order;

            return [
                'type' => 'warehouse_transfer',
                'warehouse_transfer_id' => $movement->id,
                'order' => [
                    'id' => $order->id,
                    'code' => $order->code ?: '#'.$order->id,
                    'customer_name' => $order->customer?->name,
                    'sale_name' => $order->user?->short_name ?: $order->user?->name,
                    'note' => $order->note,
                    'package_count' => $order->package_count,
                    'packing_specification' => $order->packing_specification,
                    'foam_box_fee' => (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0),
                    'shipping_fee' => $this->billableShippingFee($order),
                    'discount' => (float) ($order->total_discount ?? 0),
                    'item_quantity' => (int) $order->items->sum('quantity'),
                    'packed_weight' => (float) ($movement->packed_total_weight ?? 0),
                    'items' => $order->items->filter(fn ($item) => $item->product_variant_id)->map(fn ($item) => [
                        'id' => $item->id,
                        'product_variant_id' => (int) $item->product_variant_id,
                        'product_name' => $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm',
                        'sku' => $item->variant?->sku,
                        'size' => $item->variant?->size,
                        'quantity' => (int) $item->quantity,
                        'weight' => (float) ($item->packed_weight ?? $item->actual_weight ?? $item->total_weight ?? 0),
                    ])->values()->all(),
                ],
            ];
        }

        $transfer = $entry->inventoryTransfer;

        return [
            'type' => 'inventory_transfer',
            'inventory_transfer' => [
                'id' => $transfer?->id,
                'code' => $transfer?->transfer_code ?: '#'.$transfer?->id,
                'note' => $transfer?->note,
                'items' => $transfer?->items->map(fn ($item) => [
                    'product_variant_id' => (int) $item->product_variant_id,
                    'product_name' => $item->variant?->product?->name ?? $item->variant?->name ?? 'Sản phẩm',
                    'sku' => $item->variant?->sku,
                    'size' => $item->variant?->size,
                    'quantity' => (int) $item->quantity,
                    'weight_kg' => (float) $item->weight_kg,
                ])->values()->all() ?? [],
            ],
        ];
    }

    private function billableShippingFee(Order $order): float
    {
        $assignedFee = (bool) ($order->charge_shipping_fee ?? false)
            ? max(0, (float) ($order->shipping_fee ?? 0))
            : 0.0;
        $customerFee = (bool) ($order->collect_customer_shipping_fee ?? false)
            ? max(0, (float) ($order->customer_shipping_fee ?? 0))
            : 0.0;

        return $assignedFee + $customerFee;
    }

    private function attachProgress(WarehouseDispatchSlip $slip): void
    {
        $statuses = collect();
        foreach ($slip->entries as $entry) {
            if ($entry->orderTransfer) {
                foreach ($entry->orderTransfer->orders as $order) {
                    $statuses->push($order->warehouseTransfers->first()?->status);
                }
            } elseif ($entry->warehouseTransfer) {
                $statuses->push($entry->warehouseTransfer->status);
            } elseif ($entry->inventoryTransfer) {
                $statuses->push($entry->inventoryTransfer->status);
            }
        }
        $received = $statuses->filter(fn ($status) => in_array($status, [
            WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED,
        ], true))->count();
        $slip->setAttribute('entry_total', $statuses->count());
        $slip->setAttribute('entry_received', $received);
        $slip->setAttribute('progress_label', $received.'/'.$statuses->count().' mục đã tiếp nhận');
    }

    private function authorizeSlip(WarehouseDispatchSlip $slip): void
    {
        $warehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if ($warehouseId && ! in_array($warehouseId, [(int) $slip->source_warehouse_id, (int) $slip->target_warehouse_id], true)) {
            abort(403, 'Phiếu không thuộc kho bạn quản lý.');
        }
    }

    private function authorizeSource(WarehouseDispatchSlip $slip): void
    {
        $warehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if ($warehouseId && $warehouseId !== (int) $slip->source_warehouse_id) {
            abort(403, 'Chỉ kho xuất được thay đổi phiếu này.');
        }
    }
}
