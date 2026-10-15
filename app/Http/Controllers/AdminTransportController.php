<?php

namespace App\Http\Controllers;

use App\Models\{OrderTransfer, WarehouseDispatchSlip, WarehouseTransfer, User, OrderHistory};
use App\Services\AdminActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTransportController extends Controller
{
    public function reassignGroup(Request $request, OrderTransfer $transfer)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $hasOpenMovement = $transfer->orders->contains(function ($order) {
            return in_array($order->warehouseTransfers()->latest('id')->value('status'), [
                WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP, WarehouseTransfer::STATUS_IN_TRANSIT, WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
            ], true);
        });
        abort_unless($hasOpenMovement, 422, 'Phiếu đã hoàn tất hoặc không còn đơn điều chuyển để đổi tài xế.');

        if ($transfer->dispatchEntry?->slip) {
            return $this->reassignSlip($request, $transfer->dispatchEntry->slip);
        }
        return $this->reassign($request, null, $transfer);
    }

    public function reassignSlip(Request $request, WarehouseDispatchSlip $dispatchSlip)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        return $this->reassign($request, $dispatchSlip, null);
    }

    private function reassign(Request $request, ?WarehouseDispatchSlip $slip, ?OrderTransfer $group)
    {
        $data = $request->validate(['shipper_id' => ['required', 'integer', 'exists:users,id'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        $driver = User::findOrFail($data['shipper_id']);
        abort_unless($driver->hasRole(['shipper', 'manager_shipper']), 422, 'Người nhận phải có vai trò shipper.');
        DB::transaction(function () use ($request, $slip, $group, $driver, $data) {
            $groups = collect(); $directIds = collect();
            if ($slip) {
                $slip = WarehouseDispatchSlip::whereKey($slip->id)->lockForUpdate()->firstOrFail();
                $entries = $slip->entries()->lockForUpdate()->get();
                $groups = OrderTransfer::whereIn('id', $entries->pluck('order_transfer_id')->filter())->lockForUpdate()->get();
                $directIds = $entries->pluck('warehouse_transfer_id')->filter();
            } else {
                $groups = collect([OrderTransfer::whereKey($group->id)->lockForUpdate()->firstOrFail()]);
            }
            $orderIds = $groups->flatMap(fn ($g) => $g->orders()->pluck('id'));
            $movements = WarehouseTransfer::where(function ($q) use ($orderIds, $directIds) {
                $q->whereIn('order_id', $orderIds)->orWhereIn('id', $directIds);
            })->whereIn('status', [WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP, WarehouseTransfer::STATUS_IN_TRANSIT, WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE])
                ->lockForUpdate()->get();
            abort_if($movements->isEmpty(), 422, 'Không còn đơn điều chuyển đang chờ hoặc đang giao để đổi tài xế.');
            $before = $movements->map->only(['id', 'order_id', 'shipper_id', 'status'])->all();
            foreach ($movements as $movement) {
                $movement->update(['shipper_id' => $driver->id]);
                OrderHistory::create(['order_id' => $movement->order_id, 'action' => 'admin_transfer_driver_changed',
                    'user_id' => $request->user()->id, 'role' => 'admin',
                    'note' => 'Admin đổi tài xế điều chuyển #'.$movement->id.' sang '.$driver->name.'. Lý do: '.$data['reason']]);
            }
            foreach ($groups as $g) {
                if ($g->orders()->whereIn('id', $movements->pluck('order_id'))->exists()) $g->update(['shipper_id' => $driver->id]);
            }
            if ($slip) $slip->update(['shipper_id' => $driver->id]);
            AdminActivityService::record('warehouse_transport', 'driver_changed', $slip ?: $group,
                'Admin đổi tài xế điều chuyển', $data['reason'], ['before' => $before, 'shipper_id' => $driver->id]);
        });
        return back()->with('success', 'Đã chuyển các đơn điều chuyển chưa hoàn tất sang '.$driver->name.'.');
    }
}
