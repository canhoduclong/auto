<?php
require dirname(__DIR__,2).'/vendor/autoload.php';$app=require dirname(__DIR__,2).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Models\{Order,OrderTransfer,WarehouseTransfer,InventoryDocument,Inventory,InventoryMovement,InventoryReservation};
$seedOrder=DB::table('orders')->first();$seedItem=DB::table('order_items')->whereNotNull('product_variant_id')->first();
$warehouses=DB::table('warehouses')->limit(2)->pluck('id');$source=$warehouses[0];$target=$warehouses[1];
$tables=['orders','order_items','order_transfers','warehouse_transfers','inventories','inventory_reservations','inventory_documents','inventory_movements','order_histories','order_returns','order_adjustments','order_item_packing_size_allocations','warehouse_dispatch_slip_entries','accounting_reconciliations','accounting_sales_entries','order_commissions','admin_events','admin_deleted_orders','shipping_expense_order_locks','shipping_expense_items','shipping_expense_claims','process_runs'];
foreach($tables as $t){$create=(array)DB::selectOne("SHOW CREATE TABLE `$t`");$sql=$create['Create Table'];$sql=preg_replace('/^CREATE TABLE/','CREATE TEMPORARY TABLE',$sql);$sql=preg_replace('/^  CONSTRAINT .*\n/m','',$sql);$sql=str_replace(",\n)","\n)",$sql);DB::statement($sql);}
Notification::fake();
try {
Order::withoutEvents(function()use($seedOrder,$seedItem,$source,$target){
$attrs=(array)$seedOrder;$attrs['id']=999001;$attrs['code']='TEST-CANCEL-TRANSFER';$attrs['order_transfer_id']=999001;$attrs['warehouse_id']=$target;$attrs['status']='packed';$attrs['created_at']='2020-01-01 08:00:00';$attrs['delivered_at']=null;DB::table('orders')->insert($attrs);
$attrs=(array)$seedItem;$attrs['id']=999001;$attrs['order_id']=999001;$attrs['quantity']=5;DB::table('order_items')->insert($attrs);
OrderTransfer::create(['shipper_id'=>1,'warehouse_id'=>$target,'created_by'=>1])->forceFill(['id'=>999001])->save();
// Move the auto-generated group key to the stable fixture ID without model callbacks.
DB::table('order_transfers')->where('id','!=',999001)->delete();
$variant=$seedItem->product_variant_id;
Inventory::create(['warehouse_id'=>$source,'product_variant_id'=>$variant,'quantity'=>95,'reserved_quantity'=>0])->forceFill(['id'=>999001])->save();
Inventory::create(['warehouse_id'=>$target,'product_variant_id'=>$variant,'quantity'=>5,'reserved_quantity'=>5])->forceFill(['id'=>999002])->save();
DB::table('inventories')->whereNotIn('id',[999001,999002])->delete();
$export=InventoryDocument::create(['type'=>'export','warehouse_id'=>$source,'document_date'=>today(),'user_id'=>1,'notes'=>'Test export']);
$import=InventoryDocument::create(['type'=>'import','warehouse_id'=>$target,'document_date'=>today(),'user_id'=>1,'notes'=>'Test import']);
WarehouseTransfer::create(['order_id'=>999001,'source_warehouse_id'=>$source,'target_warehouse_id'=>$target,'shipper_id'=>1,'status'=>(getenv('TEST_TRANSFER_STATUS') ?: 'received_completed'),'export_document_id'=>$export->id,'import_document_id'=>$import->id]);
InventoryMovement::create(['inventory_id'=>999001,'quantity'=>-5,'type'=>'export','reference_type'=>InventoryDocument::class,'reference_id'=>$export->id,'user_id'=>1]);
InventoryMovement::create(['inventory_id'=>999002,'quantity'=>5,'type'=>'import','reference_type'=>InventoryDocument::class,'reference_id'=>$import->id,'user_id'=>1]);
InventoryReservation::create(['inventory_id'=>999002,'order_item_id'=>999001,'quantity'=>5,'reserved_at'=>now()]);
DB::table('accounting_reconciliations')->insert(['order_id'=>999001,'status'=>'confirmed','recognized_revenue'=>1000]);
DB::table('order_commissions')->insert(['order_id'=>999001,'sale_user_id'=>1,'commission_amount'=>100]);
DB::table('accounting_sales_entries')->insert(['order_id'=>999001,'entry_date'=>'2020-01-01','entry_month'=>10,'customer_name'=>'Test','unit'=>'Cái','source'=>'order','total_amount'=>1000]);
DB::table('order_histories')->insert(['order_id'=>999001,'action'=>'shipper_accepted','user_id'=>1,'role'=>'shipper','created_at'=>now(),'updated_at'=>now()]);
foreach([1,2] as $run) {
DB::table('process_runs')->insert(['id'=>$run,'definition_id'=>1,'definition_version'=>1,'activity'=>'shipping_expense','initiator_id'=>1,'configuration'=>'{}','status'=>'running']);
DB::table('shipping_expense_claims')->insert(['id'=>$run,'process_run_id'=>$run,'shipper_id'=>1,'total'=>3000]);
}
DB::table('shipping_expense_items')->insert([
['claim_id'=>1,'order_id'=>999001,'dispatch_id'=>1,'amount'=>1000],
['claim_id'=>1,'order_id'=>999002,'dispatch_id'=>1,'amount'=>2000],
['claim_id'=>2,'order_id'=>999001,'dispatch_id'=>1,'amount'=>3000],
]);
DB::table('shipping_expense_order_locks')->insert(['claim_id'=>1,'order_id'=>999001]);
$short=in_array('--short',$_SERVER['argv']);
if($short)DB::table('inventories')->where('id',999002)->update(['quantity'=>4]);
$admin=new class extends App\Models\User {public function isAdmin(){return true;}};$admin->id=1;
$deleteReason=in_array('--long-reason',$_SERVER['argv']) ? str_repeat('Ừ',500) : 'Dọn dữ liệu cũ kiểm thử';
(new App\Services\DeleteHistoricalTransferService)->delete(999001,$admin,$deleteReason);
if(Inventory::find(999001)->quantity!=95||Inventory::find(999002)->quantity!=5)throw new RuntimeException('Physical stock changed');
if(Order::find(999001)||OrderTransfer::count()||WarehouseTransfer::count()||DB::table('order_histories')->count())throw new RuntimeException('Orders or group remain');
if(InventoryReservation::count()||Inventory::find(999002)->reserved_quantity!=0)throw new RuntimeException('Reservations remain');
if(DB::table('accounting_reconciliations')->count()||DB::table('accounting_sales_entries')->count()||DB::table('order_commissions')->count())throw new RuntimeException('Accounting rows remain');
if(DB::table('shipping_expense_items')->where('order_id',999001)->exists()||DB::table('shipping_expense_order_locks')->where('order_id',999001)->exists())throw new RuntimeException('Expense reconciliation links remain');
if(DB::table('shipping_expense_claims')->where('id',1)->value('total')!=2000)throw new RuntimeException('Shared claim total wrong');
if(DB::table('shipping_expense_claims')->where('id',2)->exists()||DB::table('process_runs')->where('id',2)->value('status')!=='cancelled')throw new RuntimeException('Empty claim not removed');
$deleted=DB::table('admin_deleted_orders')->first();
if($deleted->reason!==$deleteReason)throw new RuntimeException('Deletion reason was truncated or modified');
if((json_decode($deleted->snapshot,true)['cleanup_group_id']??null)!==999001)throw new RuntimeException('Cleanup group context missing');
if(!(json_decode($deleted->snapshot,true)['historical_cleanup']??false))throw new RuntimeException('Cleanup audit marker missing');
if(DB::table('admin_deleted_orders')->count()!=1)throw new RuntimeException('Audit missing');
// MySQL cannot reuse one temporary table in multiple EXISTS subqueries.
// The reconciliation query only reads histories after the temporary orders are empty.
DB::statement('DROP TEMPORARY TABLE `order_histories`');
$reconciliation=(new App\Http\Controllers\AccountingDashboardController)->reconciliation(
Illuminate\Http\Request::create('/accounting/reconciliation?business_date_from=2020-01-01&business_date_to=2020-12-31'));
if($reconciliation->getData()['missingOrders']->contains(fn($row)=>(int)$row->order_id===999001))throw new RuntimeException('Purged order reappeared in reconciliation');
echo "Expense locks/items cleared; shared claim recalculated; empty claim removed; cleanup excluded from reconciliation OK\n";
echo "Historical purge: orders/group and revenue removed; physical stock unchanged; holds released and audit retained OK\n";
});
}finally{foreach(array_reverse($tables)as$t)DB::statement("DROP TEMPORARY TABLE IF EXISTS `$t`");}
