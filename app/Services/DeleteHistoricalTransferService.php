<?php
namespace App\Services;

use App\Models\{OrderTransfer, Order, WarehouseTransfer, Inventory, InventoryReservation, User};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

class DeleteHistoricalTransferService
{
    public function delete(int $groupId, User $actor, string $reason): void
    {
        abort_unless($actor->isAdmin(), 403);
        $this->check(mb_strlen(trim($reason)) >= 5 && mb_strlen($reason) <= 500, 'Lý do xóa phải từ 5 đến 500 ký tự.');
        DB::transaction(function () use ($groupId,$actor,$reason) {
            $group=OrderTransfer::whereKey($groupId)->lockForUpdate()->firstOrFail();
            $orders=$group->orders()->with(['items','histories','returnRecords.returnItems','adjustments.items'])->lockForUpdate()->get();
            $this->check($orders->isNotEmpty(), 'Phiếu không còn đơn để xóa.');
            $transfers=WarehouseTransfer::whereIn('order_id',$orders->modelKeys())->lockForUpdate()->get();
            foreach($orders as $order) {
                $this->check($order->created_at && $order->created_at->lt(today()) && (!$order->delivered_at || $order->delivered_at->lt(today())), 'Chỉ được dọn đơn cũ trước hôm nay.');
                if(Schema::hasTable('accounting_sales_entries')) $this->check(!DB::table('accounting_sales_entries')->where('order_id',$order->id)->whereDate('entry_date','>=',today())->exists(), 'Đơn có doanh thu trong kỳ hiện tại hôm nay; cần xử lý riêng.');
            }
            $snapshot=['group'=>$group->toArray(),'orders'=>$orders->toArray(),'transfers'=>$transfers->toArray()];
            $batchIds=collect();
            foreach(['accounting_sales_entries','order_commissions','accounting_reconciliations'] as $table) {
                if(!Schema::hasTable($table))continue;
                $rows=DB::table($table)->whereIn('order_id',$orders->modelKeys())->lockForUpdate()->get();
                $snapshot[$table]=$rows->toArray();
                if($table==='accounting_sales_entries')$batchIds=$rows->pluck('import_batch_id')->filter()->unique();
                DB::table($table)->whereIn('order_id',$orders->modelKeys())->delete();
            }
            // Expense reconciliation has restrictive FKs to orders; remove its
            // order lines and locks before deleting the selected historical orders.
            $claimIds=collect();
            foreach(['shipping_expense_order_locks','shipping_expense_items'] as $table) {
                if(!Schema::hasTable($table))continue;
                $rows=DB::table($table)->whereIn('order_id',$orders->modelKeys())->lockForUpdate()->get();
                $snapshot[$table]=$rows->toArray();
                $claimIds=$claimIds->merge($rows->pluck('claim_id'));
                DB::table($table)->whereIn('order_id',$orders->modelKeys())->delete();
            }
            if(Schema::hasTable('shipping_expense_claims')) {
                $claims=DB::table('shipping_expense_claims')->whereIn('id',$claimIds->unique())->lockForUpdate()->get();
                $snapshot['shipping_expense_claims']=$claims->toArray();
                foreach($claims as $claim) {
                    $remaining=DB::table('shipping_expense_items')->where('claim_id',$claim->id);
                    if(!(clone $remaining)->exists()) {
                        DB::table('shipping_expense_claims')->where('id',$claim->id)->delete();
                        if(Schema::hasTable('process_runs')) {
                            $snapshot['expense_process_runs'][]=DB::table('process_runs')->where('id',$claim->process_run_id)->first();
                            DB::table('process_runs')->where('id',$claim->process_run_id)->update(['status'=>'cancelled','finished_at'=>now(),'updated_at'=>now()]);
                        }
                    } else {
                        DB::table('shipping_expense_claims')->where('id',$claim->id)->update(['total'=>$remaining->sum('amount'),'updated_at'=>now()]);
                    }
                }
            }
            $itemIds=$orders->flatMap(fn($order)=>$order->items->modelKeys());
            $reservations=InventoryReservation::whereIn('order_item_id',$itemIds)->lockForUpdate()->get();
            $snapshot['reservations']=$reservations->toArray();
            InventoryReservation::whereIn('id',$reservations->modelKeys())->delete();
            foreach($reservations->pluck('inventory_id')->unique()->sort() as $inventoryId) {
                $inventory=Inventory::whereKey($inventoryId)->lockForUpdate()->firstOrFail();
                // Release obsolete holds, never restore/subtract physical inventory.
                $inventory->reserved_quantity=$inventory->reservations()->sum('quantity');
                $inventory->save();
            }
            $entries=DB::table('warehouse_dispatch_slip_entries')->where(function($q)use($groupId,$transfers){
                $q->where('order_transfer_id',$groupId)->orWhereIn('warehouse_transfer_id',$transfers->modelKeys());
            })->lockForUpdate()->get();
            $snapshot['dispatch_entries']=$entries->toArray();
            DB::table('warehouse_dispatch_slip_entries')->whereIn('id',$entries->pluck('id'))->delete();
            WarehouseTransfer::whereIn('id',$transfers->modelKeys())->delete();
            foreach($orders as $order) {
                DB::table('admin_deleted_orders')->insert([
                    'order_id'=>$order->id,'order_code'=>$order->code,'customer_id'=>$order->customer_id,'sale_user_id'=>$order->user_id,
                    'order_total'=>(float)$order->total,'recognized_revenue'=>(float)collect($snapshot['accounting_reconciliations']??[])->firstWhere('order_id',$order->id)?->recognized_revenue,
                    'commission_amount'=>0,'accounting_sales_import_batch_id'=>$order->accounting_sales_import_batch_id,
                    'reason'=>'Dọn lịch sử phiếu #'.$groupId.': '.$reason,
                    'snapshot'=>json_encode(['order'=>$order->getAttributes(),'items'=>$order->items->toArray(),'preserve_current_inventory'=>true,'historical_cleanup'=>true],JSON_UNESCAPED_UNICODE),
                    'deleted_by'=>$actor->id,'deleted_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
                ]);
                $order->histories()->delete();
                $order->delete(); // Cascades items, receiving, delivery history, adjustments and accounting links.
            }
            AdminActivityService::record('order_transfer','historical_data_deleted',$group,'Admin xóa phiếu cũ và toàn bộ đơn',$reason,$snapshot);
            $group->delete();
            foreach($batchIds as $batchId) {
                $batch=\App\Models\AccountingSalesImportBatch::find($batchId);
                if($batch)$batch->update(['row_count'=>$batch->entries()->count(),'total_amount'=>$batch->entries()->sum('total_amount')]);
            }
        });
    }
    private function check(bool $valid,string $message): void
    {
        if(!$valid)throw ValidationException::withMessages(['transfer'=>$message]);
    }
}
