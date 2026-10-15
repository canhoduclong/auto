<?php
namespace Tests\Feature;

use App\Models\{Inventory, InventoryDocument, InventoryReservation, Order, OrderItem, User, WarehouseTransfer};
use App\Http\Controllers\ShipperDashboardController;
use App\Services\PackedInventoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use PHPUnit\Framework\TestCase;

class PackedTransferInventoryTest extends TestCase
{
    private $app;
    private array $tables = ['inventories', 'inventory_reservations', 'inventory_movements', 'product_variants'];
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        // MySQL temporary tables shadow real tables only on this connection.
        foreach ($this->tables as $name) Schema::create($name, function (Blueprint $t) use ($name) {
            $t->temporary(); $t->id(); $t->timestamps();
            if ($name === 'inventories') {
                $t->integer('warehouse_id'); $t->integer('product_variant_id');
                $t->double('quantity'); $t->double('reserved_quantity')->default(0);
            } elseif ($name === 'inventory_reservations') {
                $t->integer('order_item_id'); $t->integer('inventory_id'); $t->double('quantity');
                $t->timestamp('reserved_at')->nullable(); $t->timestamp('packed_at')->nullable(); $t->double('packed_weight_kg')->nullable();
            } elseif ($name === 'inventory_movements') {
                $t->integer('inventory_id'); $t->double('quantity'); $t->string('type');
                $t->string('reference_type'); $t->integer('reference_id'); $t->integer('user_id')->nullable();
            } else $t->double('stock')->default(0);
        });
        DB::table('product_variants')->insert(['id'=>1,'stock'=>10]);
        DB::table('inventories')->insert([
            ['id'=>1,'warehouse_id'=>1,'product_variant_id'=>1,'quantity'=>5,'reserved_quantity'=>5],
            ['id'=>2,'warehouse_id'=>2,'product_variant_id'=>1,'quantity'=>5,'reserved_quantity'=>0],
        ]);
        DB::table('inventory_reservations')->insert(['inventory_id'=>1,'order_item_id'=>10,'quantity'=>5,'packed_at'=>now()]);
        $user = new User; $user->id=7; auth()->setUser($user);
    }
    protected function tearDown(): void
    {
        foreach ($this->tables as $table) DB::statement('DROP TEMPORARY TABLE IF EXISTS `'.$table.'`');
        $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    private function order(): Order
    {
        $o = new Order(['status'=>'packed','warehouse_id'=>2]); $o->id=1;
        $i = new OrderItem(['product_variant_id'=>1,'quantity'=>5,'packed_weight'=>12]); $i->id=10;
        $o->setRelation('items',collect([$i])); return $o;
    }
    private function receive(Order $o, ?int $exportId=null): void
    {
        $t = new WarehouseTransfer(['source_warehouse_id'=>1,'target_warehouse_id'=>2,'export_document_id'=>$exportId]); $t->id=9;
        DB::transaction(fn()=>(new PackedInventoryService)->receiveTransfer($o,$t));
    }
    public function test_legacy_reserved_transfer_is_moved_once_and_can_be_picked_up(): void
    {
        $o=$this->order(); $this->receive($o);
        self::assertEquals(0,Inventory::find(1)->quantity);
        self::assertEquals(5,Inventory::find(2)->reserved_quantity);
        self::assertNotNull(InventoryReservation::first()->packed_at);
        $doc=new InventoryDocument; $doc->id=99;
        DB::transaction(fn()=>(new \ReflectionMethod(ShipperDashboardController::class,'deductStockForAcceptedOrderItem'))->invoke(new ShipperDashboardController,$o,$doc,$o->items->first(),2));
        self::assertEquals(0,Inventory::find(2)->quantity);
        self::assertSame(0,InventoryReservation::count());
        self::assertEquals(-5,DB::table('inventory_movements')->where('reference_type',InventoryDocument::class)->sum('quantity'));
    }
    public function test_already_exported_legacy_reservation_does_not_deduct_source_twice(): void
    {
        DB::table('inventories')->where('id',1)->update(['quantity'=>0]);
        DB::table('inventory_movements')->insert(['inventory_id'=>1,'quantity'=>-5,'type'=>'export','reference_type'=>InventoryDocument::class,'reference_id'=>99]);
        $this->receive($this->order(),99);
        self::assertEquals(0,Inventory::find(1)->quantity);
        self::assertEquals(5,Inventory::find(2)->reserved_quantity);
        self::assertSame(1,DB::table('inventory_movements')->count());
    }
    public function test_new_transfer_without_source_reservation_still_seals_received_goods(): void
    {
        DB::table('inventory_reservations')->delete();
        $this->receive($this->order());
        self::assertEquals(5,Inventory::find(2)->reserved_quantity);
        self::assertNotNull(InventoryReservation::first()->packed_at);
    }
    public function test_adjustment_cannot_take_packed_goods(): void
    {
        $this->expectException(\RuntimeException::class);
        Inventory::find(1)->update(['quantity'=>4]);
    }
    public function test_transfer_pickup_uses_own_reservation_when_available_stock_is_zero(): void
    {
        $o=$this->order(); $doc=new InventoryDocument; $doc->id=99;
        DB::transaction(fn()=>(new \ReflectionMethod(ShipperDashboardController::class,'deductStockForWarehouseTransferItem'))->invoke(new ShipperDashboardController,$o,$doc,$o->items->first(),1));
        self::assertEquals(0,Inventory::find(1)->quantity);
        self::assertEquals(0,Inventory::find(1)->reserved_quantity);
        self::assertSame(0,InventoryReservation::count());
    }
    public function test_cancel_releases_packed_goods_without_increasing_physical_stock(): void
    {
        DB::transaction(fn()=>(new \ReflectionMethod(\App\Http\Controllers\OrderController::class,'releaseReservedStockForOrder'))->invoke(new \App\Http\Controllers\OrderController,$this->order()));
        self::assertEquals(5,Inventory::find(1)->quantity);
        self::assertEquals(0,Inventory::find(1)->reserved_quantity);
        self::assertSame(0,InventoryReservation::count());
    }
    public function test_pickup_keeps_other_orders_packed_goods_untouched(): void
    {
        DB::table('inventories')->where('id',1)->update(['quantity'=>8,'reserved_quantity'=>8]);
        DB::table('inventory_reservations')->insert(['inventory_id'=>1,'order_item_id'=>11,'quantity'=>3,'packed_at'=>now()]);
        $o=$this->order(); $doc=new InventoryDocument; $doc->id=99;
        DB::transaction(fn()=>(new \ReflectionMethod(ShipperDashboardController::class,'deductStockForWarehouseTransferItem'))->invoke(new ShipperDashboardController,$o,$doc,$o->items->first(),1));
        self::assertEquals(3,Inventory::find(1)->quantity);
        self::assertEquals(3,Inventory::find(1)->reserved_quantity);
        self::assertEquals(3,InventoryReservation::where('order_item_id',11)->sum('quantity'));
    }
    public function test_insufficient_legacy_source_rolls_back_instead_of_inventing_stock(): void
    {
        DB::table('inventories')->where('id',1)->update(['quantity'=>2]);
        try {
            $this->receive($this->order());
            self::fail('Invalid legacy source accepted');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('Tồn kho gửi không đủ',$e->getMessage());
        }
        self::assertEquals(2,Inventory::find(1)->quantity);
        self::assertEquals(5,InventoryReservation::where('inventory_id',1)->sum('quantity'));
        self::assertEquals(0,Inventory::find(2)->reserved_quantity);
    }

    public function test_sealing_rejects_existing_over_reservation(): void
    {
        DB::table('inventories')->where('id',1)->update(['quantity'=>2]);
        $o=$this->order(); $o->items->first()->setRelation('packingSizeAllocations',collect());
        $this->expectException(\RuntimeException::class);
        DB::transaction(fn()=>(new PackedInventoryService)->seal($o,1));
    }
    public function test_sealing_preserves_packed_weight_and_refreshes_reserved_total(): void
    {
        DB::table('inventories')->where('id',1)->update(['reserved_quantity'=>0]);
        $o=$this->order(); $o->items->first()->setRelation('packingSizeAllocations',collect());
        DB::transaction(fn()=>(new PackedInventoryService)->seal($o,1));
        self::assertEquals(5,Inventory::find(1)->reserved_quantity);
        self::assertEquals(12,InventoryReservation::first()->packed_weight_kg);
        self::assertNotNull(InventoryReservation::first()->packed_at);
    }

}
