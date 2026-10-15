<?php
require dirname(__DIR__,2).'/vendor/autoload.php';$app=require dirname(__DIR__,2).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Models\{Order,OrderTransfer,WarehouseTransfer,InventoryDocument,Inventory,InventoryMovement,InventoryReservation};
$seedOrder=DB::table('orders')->first();$seedItem=DB::table('order_items')->whereNotNull('product_variant_id')->first();
$warehouses=DB::table('warehouses')->limit(2)->pluck('id');$source=$warehouses[0];$target=$warehouses[1];
$tables=['orders','order_items','order_transfers','warehouse_transfers','inventories','inventory_reservations','inventory_documents','inventory_movements','order_histories','order_returns','order_adjustments','order_item_packing_size_allocations','warehouse_dispatch_slip_entries','accounting_reconciliations','accounting_sales_entries','order_commissions','admin_events'];
foreach($tables as $t){$create=(array)DB::selectOne("SHOW CREATE TABLE `$t`");$sql=$create['Create Table'];$sql=preg_replace('/^CREATE TABLE/','CREATE TEMPORARY TABLE',$sql);$sql=preg_replace('/^  CONSTRAINT .*\n/m','',$sql);$sql=str_replace(",\n)","\n)",$sql);DB::statement($sql);}
Notification::fake();
try {
Order::withoutEvents(function()use($seedOrder,$seedItem,$source,$target){
$attrs=(array)$seedOrder;$attrs['id']=999001;$attrs['code']='TEST-CANCEL-TRANSFER';$attrs['order_transfer_id']=999001;$attrs['warehouse_id']=$target;$attrs['status']='packed';DB::table('orders')->insert($attrs);
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
WarehouseTransfer::create(['order_id'=>999001,'source_warehouse_id'=>$source,'target_warehouse_id'=>$target,'shipper_id'=>1,'status'=>'received_completed','export_document_id'=>$export->id,'import_document_id'=>$import->id]);
InventoryMovement::create(['inventory_id'=>999001,'quantity'=>-5,'type'=>'export','reference_type'=>InventoryDocument::class,'reference_id'=>$export->id,'user_id'=>1]);
InventoryMovement::create(['inventory_id'=>999002,'quantity'=>5,'type'=>'import','reference_type'=>InventoryDocument::class,'reference_id'=>$import->id,'user_id'=>1]);
InventoryReservation::create(['inventory_id'=>999002,'order_item_id'=>999001,'quantity'=>5,'reserved_at'=>now()]);
DB::table('accounting_reconciliations')->insert(['order_id'=>999001,'status'=>'confirmed','recognized_revenue'=>1000]);
DB::table('order_commissions')->insert(['order_id'=>999001,'sale_user_id'=>1,'commission_amount'=>100]);
DB::table('accounting_sales_entries')->insert(['order_id'=>999001,'entry_date'=>today(),'entry_month'=>10,'customer_name'=>'Test','unit'=>'Cái','source'=>'order','total_amount'=>1000]);
DB::table('order_histories')->insert(['order_id'=>999001,'action'=>'shipper_accepted','user_id'=>1,'role'=>'shipper','created_at'=>now(),'updated_at'=>now()]);
$short=in_array('--short',$_SERVER['argv']);
if($short)DB::table('inventories')->where('id',999002)->update(['quantity'=>4]);
try {
(new App\Services\CancelCompletedTransferService)->cancel(999001,1,'Test hủy sai số liệu');
if($short)throw new RuntimeException('Short stock accepted');
} catch(Illuminate\Validation\ValidationException $e) {
if(!$short)throw $e;
if(Inventory::find(999001)->quantity!=95||InventoryReservation::count()!=1||Order::find(999001)->status!=='packed'||DB::table('accounting_sales_entries')->count()!=1)throw new RuntimeException('Partial changes not rolled back');
echo "Short stock: all changes rolled back OK\n";return;
}
if(DB::table('accounting_reconciliations')->count()||DB::table('accounting_sales_entries')->count()||DB::table('order_commissions')->count())throw new RuntimeException('Accounting rows remain');
if(DB::table('order_histories')->where('action','shipper_accepted')->exists())throw new RuntimeException('Delivery history remains');

if(Inventory::find(999001)->quantity!=100||Inventory::find(999002)->quantity!=0)throw new RuntimeException('Wrong stock reversal');
if(Order::find(999001)->status!=='cancelled'||Order::find(999001)->warehouse_id!=$source)throw new RuntimeException('Order not cancelled at source');
if(WarehouseTransfer::count()||InventoryReservation::count()||OrderTransfer::count())throw new RuntimeException('Operational rows remain');
echo "Cancellation: source stock restored, receiving removed, reservations released, orders cancelled OK\n";
});
}finally{foreach(array_reverse($tables)as$t)DB::statement("DROP TEMPORARY TABLE IF EXISTS `$t`");}
