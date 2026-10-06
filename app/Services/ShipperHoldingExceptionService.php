<?php

namespace App\Services;

use App\Models\{Order, WarehouseTransfer, Inventory, InventoryDocument, InventoryMovement, InventoryReservation, ProductVariant, OrderHistory};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipperHoldingExceptionService
{
    public const DELIVERY_STATUSES = ['delivering', 'in_delivery', 'shipping', 'picked_up', 'overdue_delivery'];

    public function transfer(int $id, string $action, string $reason, int $actorId, int $expectedShipper): void
    {
        $this->check(in_array($action, ['complete', 'return'], true) && trim($reason) !== '', 'Vui lòng chọn thao tác và nhập lý do.');
        DB::transaction(function () use ($id, $action, $reason, $actorId, $expectedShipper) {
            $transfer = WarehouseTransfer::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->check($transfer->status === WarehouseTransfer::STATUS_IN_TRANSIT && (int) $transfer->shipper_id === $expectedShipper, 'Phiếu đã thay đổi hoặc không còn ở shipper này. Vui lòng tải lại.');
            $order = Order::whereKey($transfer->order_id)->lockForUpdate()->firstOrFail();
            $order->load('items');
            $shipperId = $transfer->shipper_id;
            if ($action === 'complete') {
                $this->check(!$transfer->import_document_id, 'Phiếu đã có chứng từ nhập kho; cần kiểm tra trước khi xử lý.');
                $document = $this->document($transfer->target_warehouse_id, $actorId, "Hoàn tất ngoại lệ điều chuyển #{$transfer->id}; đơn #{$order->code}; {$reason}");
                $weights = [];
                foreach ($order->items as $item) {
                    if ($item->product_variant_id && $item->quantity > 0) {
                        $this->import($document, (int) $item->product_variant_id, (float) $item->quantity, (float) $item->price, $actorId);
                    }
                    $weights[] = ['order_item_id'=>$item->id, 'product_variant_id'=>$item->product_variant_id, 'received_weight'=>$item->quantity > 0 ? (float) ($item->packed_weight ?? $item->actual_weight ?? ((float) $item->unit_weight * $item->quantity)) : 0];
                }
                // Preserve the normal receiving flow for stock still reserved at the source.
                foreach ($order->items as $item) {
                    $reservations = InventoryReservation::where('order_item_id', $item->id)->whereHas('inventory', fn($q) => $q->where('warehouse_id', $transfer->source_warehouse_id))->lockForUpdate()->get();
                    foreach ($reservations as $reservation) {
                        $source = Inventory::whereKey($reservation->inventory_id)->lockForUpdate()->firstOrFail();
                        $quantity = (int) $reservation->quantity;
                        if ($quantity > 0) {
                            $movable = min($quantity, max(0, (int) $source->quantity), max(0, (int) $source->reserved_quantity));
                            if ($movable > 0) {
                                $source->decrement('quantity', $movable);
                                InventoryMovement::create(['inventory_id'=>$source->id,'quantity'=>-$movable,'type'=>'transfer_out','reference_type'=>WarehouseTransfer::class,'reference_id'=>$transfer->id,'user_id'=>$actorId]);
                            }
                            $target = $this->inventory($transfer->target_warehouse_id, $source->product_variant_id);
                            InventoryReservation::create(['order_item_id'=>$item->id,'inventory_id'=>$target->id,'quantity'=>$quantity,'reserved_at'=>now()]);
                            $target->update(['reserved_quantity'=>$target->reservations()->sum('quantity')]);
                        }
                        $reservation->delete();
                        $source->update(['reserved_quantity'=>$source->reservations()->sum('quantity')]);
                        $this->syncStock($source->product_variant_id);
                    }
                }
                $order->update(['warehouse_id'=>$transfer->target_warehouse_id]);
                $receivedWeight = round(array_sum(array_column($weights, 'received_weight')), 3);
                $transfer->update(['status'=>WarehouseTransfer::STATUS_RECEIVED_COMPLETED,'delivered_by'=>$actorId,'delivered_at'=>now(),'received_by'=>$actorId,'received_at'=>now(),'import_document_id'=>$document->id,'received_weights'=>$weights,'received_total_weight'=>$receivedWeight,'weight_loss'=>round((float) $transfer->packed_total_weight - $receivedWeight, 3),'shipper_delivery_note'=>'Admin hoàn tất ngoại lệ: '.$reason]);
                $note = "Admin giao và tiếp nhận hoàn tất điều chuyển #{$transfer->id} vào kho #{$transfer->target_warehouse_id}; shipper #{$shipperId}. Cân nặng lấy từ dữ liệu đóng hàng. Lý do: {$reason}";
            } else {
                $this->restoreExport($transfer->export_document_id, (int) $transfer->source_warehouse_id, $actorId, "Gỡ ngoại lệ điều chuyển #{$transfer->id}; {$reason}");
                $order->update(['warehouse_id'=>$transfer->source_warehouse_id]);
                $transfer->update(['status'=>WarehouseTransfer::STATUS_CANCELLED,'shipper_id'=>null,'export_document_id'=>null,'note'=>trim($transfer->note.' | Admin gỡ giữ hàng, trả về kho nguồn: '.$reason, ' |')]);
                $note = "Admin gỡ điều chuyển #{$transfer->id} khỏi shipper #{$shipperId}, hoàn tồn thực xuất về kho #{$transfer->source_warehouse_id}. Lý do: {$reason}";
            }
            $this->history($order, 'admin_shipper_transfer_'.$action, $order->status, $actorId, $note);
        });
    }

    public function order(int $id, string $action, string $reason, int $actorId, int $expectedShipper): void
    {
        $this->check(in_array($action, ['complete', 'return'], true) && trim($reason) !== '', 'Vui lòng chọn thao tác và nhập lý do.');
        DB::transaction(function () use ($id, $action, $reason, $actorId, $expectedShipper) {
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->check(in_array($order->status, self::DELIVERY_STATUSES, true) && (int) $order->shipper_id === $expectedShipper && $expectedShipper > 0, 'Đơn đã thay đổi hoặc không còn do shipper này giữ.');
            $this->check(!WarehouseTransfer::where('order_id',$id)->whereNotIn('status',['received_completed','cancelled'])->exists(), 'Đơn đang điều chuyển kho. Vui lòng xử lý phiếu điều chuyển trước.');
            $this->check(!$order->returnRecords()->exists(), 'Đơn có nghiệp vụ trả hàng, cần xử lý tại chức năng trả hàng.');
            $before = $order->status;
            if ($action === 'complete') {
                $updates = ['status'=>Order::STATUS_COMPLETED,'delivered_at'=>$order->delivered_at ?? now()];
                if ($order->needs_operational_completion) $updates += ['needs_operational_completion'=>false,'operational_completed_by'=>$actorId,'operational_completed_at'=>now(),'operational_completion_note'=>'Admin hoàn tất ngoại lệ: '.$reason];
                $order->update($updates);
                $note = "Admin xác nhận giao hàng và hoàn tất thay shipper #{$expectedShipper}. Lý do: {$reason}";
            } else {
                $acceptance = $order->histories()->where('action','shipper_accepted')->latest('id')->first();
                $document = InventoryDocument::where('type','export')->where('notes','Xuất kho cho đơn #'.$order->code)->when($acceptance?->user_id, fn($q) => $q->where('user_id',$acceptance->user_id))->latest('id')->first();
                $warehouse = (int) ($document?->warehouse_id ?? $order->warehouse_id);
                $this->check($warehouse > 0, 'Không xác định được kho lấy hàng.');
                $this->restoreExport($document?->id, $warehouse, $actorId, "Gỡ ngoại lệ đơn #{$order->code}; {$reason}");
                $order->update(['status'=>Order::STATUS_READY_TO_SHIP,'shipper_id'=>null,'warehouse_id'=>$warehouse]);
                $note = "Admin gỡ khỏi shipper #{$expectedShipper}, hoàn tồn thực xuất về kho #{$warehouse}, chuyển đơn sang chờ lấy hàng. Lý do: {$reason}";
            }
            $this->history($order, 'admin_shipper_order_'.$action, $before, $actorId, $note);
        });
    }

    private function restoreExport(?int $documentId, int $warehouseId, int $actorId, string $note): void
    {
        if (!$documentId) return; // Legacy rows without a stock export must not invent stock.
        $export = InventoryDocument::whereKey($documentId)->lockForUpdate()->firstOrFail();
        $this->check($export->type === 'export' && (int) $export->warehouse_id === $warehouseId, 'Chứng từ xuất không thuộc kho lấy hàng.');
        $movements = InventoryMovement::where('reference_type', InventoryDocument::class)->where('reference_id',$documentId)->where('quantity','<',0)->lockForUpdate()->get();
        $marker = '[SHIPPER-RETURN:'.$documentId.']';
        $this->check(!InventoryDocument::where('type','import')->where('notes','like','%'.$marker.'%')->exists(), 'Phiếu xuất này đã được hoàn tồn. Vui lòng kiểm tra lịch sử.');
        $document = $this->document($warehouseId, $actorId, $note.'; hoàn phiếu '.$export->document_number.' '.$marker);
        foreach ($movements as $movement) {
            $inventory = Inventory::whereKey($movement->inventory_id)->lockForUpdate()->firstOrFail();
            $this->check((int) $inventory->warehouse_id === $warehouseId, 'Biến động xuất kho không khớp kho lấy hàng.');
            $this->import($document, $inventory->product_variant_id, -(float) $movement->quantity, 0, $actorId);
        }
    }

    private function document(int $warehouseId, int $actorId, string $note): InventoryDocument
    {
        return InventoryDocument::create(['type'=>'import','warehouse_id'=>$warehouseId,'document_date'=>now()->toDateString(),'user_id'=>$actorId,'shipping_fee'=>0,'notes'=>$note]);
    }
    private function inventory(int $warehouseId, int $variantId): Inventory
    {
        $inventory = Inventory::firstOrCreate(['warehouse_id'=>$warehouseId,'product_variant_id'=>$variantId],['quantity'=>0,'reserved_quantity'=>0]);
        return Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
    }
    private function import(InventoryDocument $document, int $variantId, float $quantity, float $cost, int $actorId): void
    {
        $inventory = $this->inventory($document->warehouse_id, $variantId);
        $document->items()->create(['product_variant_id'=>$variantId,'quantity'=>$quantity,'unit_cost'=>$cost]);
        $inventory->increment('quantity',$quantity);
        InventoryMovement::create(['inventory_id'=>$inventory->id,'quantity'=>$quantity,'type'=>'import','reference_type'=>InventoryDocument::class,'reference_id'=>$document->id,'user_id'=>$actorId]);
        $this->syncStock($variantId);
    }
    private function syncStock(int $variantId): void
    {
        ProductVariant::whereKey($variantId)->update(['stock'=>Inventory::where('product_variant_id',$variantId)->sum('quantity')]);
    }
    private function history(Order $order, string $action, string $before, int $actorId, string $note): void
    {
        OrderHistory::create(['order_id'=>$order->id,'action'=>$action,'user_id'=>$actorId,'role'=>'admin','status_before'=>$before,'status_after'=>$order->status,'note'=>$note]);
    }
    private function check(bool $condition, string $message): void
    {
        if (!$condition) throw ValidationException::withMessages(['holding'=>$message]);
    }
}
