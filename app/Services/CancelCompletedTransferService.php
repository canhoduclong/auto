<?php
namespace App\Services;

use App\Models\{OrderTransfer, Order, WarehouseTransfer, Inventory, InventoryReservation, InventoryMovement, InventoryDocument, ProductVariant};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

class CancelCompletedTransferService
{
    public function cancel(int $groupId, int $actorId, string $reason): void
    {
        DB::transaction(function () use ($groupId, $actorId, $reason) {
            $group = OrderTransfer::whereKey($groupId)->lockForUpdate()->firstOrFail();
            $orders = $group->orders()->with('items.packingSizeAllocations')->lockForUpdate()->get();
            $this->check($orders->isNotEmpty(), 'Phiếu không còn đơn để hủy.');
            $transfers = WarehouseTransfer::whereIn('order_id', $orders->modelKeys())->orderBy('id')->lockForUpdate()->get();
            foreach ($orders as $order) {
                $last = $transfers->where('order_id', $order->id)->last();
                $this->check($last?->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED, 'Có đơn chưa được kho nhận tiếp nhận hoàn tất.');
                // These flows may share inventory documents with other orders.
                $this->check(!$order->returnRecords()->exists() && !$order->adjustments()->whereIn('status', ['approved','completed'])->exists(),
                    'Đơn '.$order->code.' có trả hàng hoặc điều chỉnh đã duyệt; cần đối soát chứng từ đó trước khi hủy toàn bộ.');
            }
            $snapshot = ['group'=>$group->toArray(),'orders'=>$orders->toArray(),'transfers'=>$transfers->toArray(),'histories'=>DB::table('order_histories')->whereIn('order_id',$orders->modelKeys())->get()->toArray()];
            $batchIds = collect();
            foreach ($orders as $order) {
                $route = $transfers->where('order_id', $order->id);
                $sourceId = (int) $route->first()->source_warehouse_id;
                $this->check($route->last()->import_document_id !== null, 'Đơn '.$order->code.' thiếu chứng từ nhập kho; cần đối soát trước khi hoàn tồn.');
                $docIds = $route->pluck('export_document_id')->merge($route->pluck('import_document_id'))->filter();
                // Customer pickup is recorded with this exact reference by web and mobile.
                $docIds = $docIds->merge(InventoryDocument::where('notes','Xuất kho cho đơn #'.$order->code)->pluck('id'))->unique();
                foreach ($route->pluck('export_document_id')->filter() as $exportId) {
                    $docIds = $docIds->merge(InventoryDocument::where('notes','like','%[SHIPPER-RETURN:'.$exportId.']%')->pluck('id'));
                }
                $this->check(InventoryMovement::where('reference_type',InventoryDocument::class)
                    ->where('reference_id',$route->last()->import_document_id)->where('quantity','>',0)->exists(),
                    'Đơn '.$order->code.' thiếu biến động nhập kho, không thể tự hoàn tồn.');
                $movements = InventoryMovement::where(function ($q) use ($order,$route,$docIds) {
                    $q->where(fn($q)=>$q->where('reference_type',InventoryDocument::class)->whereIn('reference_id',$docIds))
                        ->orWhere(fn($q)=>$q->where('reference_type',WarehouseTransfer::class)->whereIn('reference_id',$route->modelKeys()))
                        ->orWhere(fn($q)=>$q->where('reference_type',Order::class)->where('reference_id',$order->id));
                })->lockForUpdate()->get();
                $snapshot['stock_movements'][$order->id]=$movements->toArray();
                $reservations = InventoryReservation::whereIn('order_item_id',$order->items->modelKeys())->lockForUpdate()->get();
                $inventoryIds=$reservations->pluck('inventory_id')->merge($movements->pluck('inventory_id'))->unique()->sort();
                InventoryReservation::whereIn('id',$reservations->modelKeys())->delete();
                foreach ($inventoryIds as $inventoryId) {
                    $inventory=Inventory::whereKey($inventoryId)->lockForUpdate()->firstOrFail();
                    $inventory->reserved_quantity=$inventory->reservations()->sum('quantity');
                    $delta=-(float)$movements->where('inventory_id',$inventoryId)->sum('quantity');
                    $this->check((float)$inventory->quantity+$delta >= (float)$inventory->reserved_quantity,
                        'Tồn kho #'.$inventoryId.' không đủ để hoàn tác, cần đối soát trước.');
                    $inventory->quantity += $delta;
                    $inventory->save();
                    if ($delta != 0) InventoryMovement::create(['inventory_id'=>$inventoryId,'quantity'=>$delta,'type'=>'adjustment','reference_type'=>Order::class,'reference_id'=>$order->id,'user_id'=>$actorId]);
                    ProductVariant::whereKey($inventory->product_variant_id)->update(['stock'=>Inventory::where('product_variant_id',$inventory->product_variant_id)->sum('quantity')]);
                }
                foreach (['accounting_sales_entries','order_commissions','accounting_reconciliations'] as $table) {
                    if (!Schema::hasTable($table)) continue;
                    $rows=DB::table($table)->where('order_id',$order->id)->lockForUpdate()->get();
                    $snapshot[$table][$order->id]=$rows->toArray();
                    if($table==='accounting_sales_entries')$batchIds=$batchIds->merge($rows->pluck('import_batch_id'));
                    DB::table($table)->where('order_id',$order->id)->delete();
                }
                foreach ($order->items as $item) {
                    $item->packingSizeAllocations()->delete();
                    $item->forceFill(['packed_quantity'=>null,'packed_weight'=>null,'actual_weight'=>null])->save();
                }
                // Operational delivery/packing history is removed; the admin audit retains the snapshot.
                $order->histories()->where(function ($q) {
                    $q->where('action','like','shipper_%')->orWhere('action','like','mobile_%')
                        ->orWhereIn('action',['start_packing','complete_packing','warehouse_complete_packing','undo_start_packing',
                            'warehouse_transfer_received','package_received_order_transfer','warehouse_pull_transfer_from_shipper',
                            'delivered','mobile_delivered','admin_shipper_transfer_complete','admin_shipper_order_complete']);
                })->delete();
                foreach($order->adjustments()->whereIn('status',['draft','pending_approval'])->get() as $adjustment) {
                    $adjustment->approvalSteps()->where('status','pending')->update(['status'=>'rejected','note'=>$reason]);
                    $adjustment->update(['status'=>'rejected']);
                }
                $order->forceFill(['status'=>Order::STATUS_CANCELLED,'warehouse_id'=>$sourceId,'order_transfer_id'=>null,
                    'shipper_id'=>null,'delivered_at'=>null,'cancelled_by'=>$actorId,'cancelled_at'=>now(),'cancel_reason'=>$reason,
                    'amount_due'=>0,'needs_operational_completion'=>false])->save();
                $order->histories()->create(['action'=>'admin_cancel_transfer_orders','user_id'=>$actorId,'role'=>'admin','status_after'=>Order::STATUS_CANCELLED,'note'=>'Hủy toàn bộ phiếu #'.$groupId.', hoàn về kho gửi. '.$reason]);
            }
            DB::table('warehouse_dispatch_slip_entries')->whereIn('warehouse_transfer_id',$transfers->modelKeys())->delete();
            $group->dispatchEntry()->delete();
            WarehouseTransfer::whereIn('id',$transfers->modelKeys())->delete();
            AdminActivityService::record('order_transfer','cancelled',$group,'Admin hủy toàn bộ phiếu và đơn',$reason,$snapshot);
            $group->delete();
            foreach ($batchIds->filter()->unique() as $batchId) {
                $batch=\App\Models\AccountingSalesImportBatch::find($batchId);
                if($batch)$batch->update(['row_count'=>$batch->entries()->count(),'total_amount'=>$batch->entries()->sum('total_amount')]);
            }
        });
    }
    private function check(bool $valid, string $message): void
    {
        if (!$valid) throw ValidationException::withMessages(['transfer'=>$message]);
    }
}
