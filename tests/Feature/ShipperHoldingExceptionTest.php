<?php
namespace Tests\Feature;

use App\Models\{InventoryDocument, WarehouseTransfer, Order, Inventory};
use App\Services\ShipperHoldingExceptionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class ShipperHoldingExceptionTest extends TestCase
{
    private $app;
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = require dirname(__DIR__,2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        $tables = [
            'users'=>'name,short_name',
            'orders'=>'code,status,warehouse_id,shipper_id,needs_operational_completion,delivered_at,collected_amount,total',
            'order_items'=>'order_id,product_variant_id,quantity,price,packed_weight,actual_weight,unit_weight',
            'warehouse_transfers'=>'order_id,source_warehouse_id,target_warehouse_id,shipper_id,status,note,export_document_id,import_document_id,packed_total_weight,delivered_by,delivered_at,received_by,received_at,received_weights,received_total_weight,weight_loss,shipper_delivery_note',
            'inventories'=>'warehouse_id,product_variant_id,quantity,reserved_quantity',
            'inventory_reservations'=>'order_item_id,inventory_id,quantity,reserved_at',
            'inventory_documents'=>'type,warehouse_id,document_date,user_id,shipping_fee,notes,document_number',
            'inventory_document_items'=>'inventory_document_id,product_variant_id,quantity,unit_cost',
            'inventory_movements'=>'inventory_id,quantity,type,reference_type,reference_id,user_id',
            'product_variants'=>'stock',
            'order_histories'=>'order_id,action,user_id,role,status_before,status_after,note,source',
            'order_returns'=>'order_id',
        ];
        foreach ($tables as $name=>$columns) Schema::create($name,function(Blueprint $table) use ($columns) {
            $table->id(); foreach(explode(',',$columns) as $column) $table->string($column)->nullable(); $table->timestamps();
        });
        DB::table('orders')->insert(['id'=>1,'code'=>'TEST-1','status'=>'packed','warehouse_id'=>1,'shipper_id'=>7,'total'=>100000,'collected_amount'=>25000]);
        DB::table('order_items')->insert(['id'=>1,'order_id'=>1,'product_variant_id'=>1,'quantity'=>5,'price'=>20000,'packed_weight'=>12]);
        DB::table('product_variants')->insert(['id'=>1,'stock'=>10]);
        DB::table('inventories')->insert(['id'=>1,'warehouse_id'=>1,'product_variant_id'=>1,'quantity'=>10,'reserved_quantity'=>0]);
        DB::table('warehouse_transfers')->insert(['id'=>1,'order_id'=>1,'source_warehouse_id'=>1,'target_warehouse_id'=>2,'shipper_id'=>7,'status'=>'in_transit','packed_total_weight'=>12]);
    }
    protected function tearDown(): void
    {
        DB::disconnect('sqlite'); $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    private function exported(float $deducted): InventoryDocument
    {
        $document = InventoryDocument::create(['type'=>'export','warehouse_id'=>1,'document_date'=>now(),'user_id'=>7,'notes'=>'Xuất kho cho đơn #TEST-1']);
        DB::table('inventory_movements')->insert(['inventory_id'=>1,'quantity'=>-$deducted,'type'=>'export','reference_type'=>InventoryDocument::class,'reference_id'=>$document->id,'user_id'=>7]);
        WarehouseTransfer::find(1)->update(['export_document_id'=>$document->id]);
        return $document;
    }
    public function test_transfer_completion_imports_target_once_and_keeps_customer_delivery_open(): void
    {
        $service = new ShipperHoldingExceptionService;
        $service->transfer(1,'complete','Đã nhận hàng',99,7);
        self::assertSame('received_completed',WarehouseTransfer::find(1)->status);
        self::assertEquals(5, Inventory::where('warehouse_id',2)->value('quantity'));
        self::assertEquals(2, Order::find(1)->warehouse_id);
        self::assertSame('packed', Order::find(1)->status);
        self::assertEquals(12, WarehouseTransfer::find(1)->received_total_weight);
        self::assertEquals(99,DB::table('order_histories')->value('user_id'));
        try { $service->transfer(1,'complete','Lần hai',99,7); self::fail('Duplicate accepted'); } catch(ValidationException $e) {}
        self::assertSame(1,InventoryDocument::where('type','import')->count());
    }
    public function test_reserved_source_stock_moves_without_duplicating_stock(): void
    {
        Inventory::find(1)->update(['reserved_quantity'=>5]);
        DB::table('inventory_reservations')->insert(['order_item_id'=>1,'inventory_id'=>1,'quantity'=>5]);
        (new ShipperHoldingExceptionService)->transfer(1,'complete','Đã nhận hàng',99,7);
        self::assertEquals(5,Inventory::find(1)->quantity);
        self::assertEquals(0,Inventory::find(1)->reserved_quantity);
        self::assertEquals(5,Inventory::where('warehouse_id',2)->value('reserved_quantity'));
        self::assertEquals(10,DB::table('product_variants')->value('stock'));
    }
    public function test_transfer_return_restores_only_actual_export_and_retains_audit_documents(): void
    {
        $export = $this->exported(3);
        (new ShipperHoldingExceptionService)->transfer(1,'return','Hàng đã về kho lấy',99,7);
        self::assertEquals(13,Inventory::find(1)->quantity);
        self::assertNull(WarehouseTransfer::find(1)->shipper_id);
        self::assertSame('cancelled',WarehouseTransfer::find(1)->status);
        self::assertNotNull(InventoryDocument::find($export->id));
        self::assertSame(2,InventoryDocument::count());
    }
    public function test_legacy_transfer_without_export_does_not_invent_stock_on_return(): void
    {
        (new ShipperHoldingExceptionService)->transfer(1,'return','Đã trả kho',99,7);
        self::assertEquals(10,Inventory::find(1)->quantity);
        self::assertSame(0,InventoryDocument::count());
    }
    public function test_changed_shipper_rejects_request_without_stock_changes(): void
    {
        $this->expectException(ValidationException::class);
        try {(new ShipperHoldingExceptionService)->transfer(1,'complete','Đã nhận',99,8);} finally {
            self::assertEquals(10,Inventory::find(1)->quantity);
            self::assertSame(0,InventoryDocument::count());
        }
    }
    public function test_customer_completion_preserves_payment_and_total(): void
    {
        DB::table('warehouse_transfers')->delete();
        DB::table('orders')->where('id',1)->update(['status'=>'delivering']);
        (new ShipperHoldingExceptionService)->order(1,'complete','Khách đã nhận',99,7);
        $order = Order::find(1);
        self::assertSame('completed',$order->status);
        self::assertNotNull($order->delivered_at);
        self::assertEquals(25000,$order->collected_amount);
        self::assertEquals(100000,$order->total);
    }
    public function test_customer_return_releases_shipper_and_restores_stock_once(): void
    {
        $this->exported(5);
        DB::table('warehouse_transfers')->delete();
        DB::table('orders')->where('id',1)->update(['status'=>'delivering']);
        $service = new ShipperHoldingExceptionService;
        $service->order(1,'return','Hàng đã trả về',99,7);
        self::assertSame(Order::STATUS_READY_TO_SHIP,Order::find(1)->status);
        self::assertNull(Order::find(1)->shipper_id);
        self::assertEquals(15,Inventory::find(1)->quantity);
        try { $service->order(1,'return','Lần hai',99,7); self::fail('Duplicate accepted'); } catch(ValidationException $e) {}
        self::assertEquals(15,Inventory::find(1)->quantity);
    }
}
