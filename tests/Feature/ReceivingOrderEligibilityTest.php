<?php
namespace Tests\Feature;

use App\Models\WarehouseTransfer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use PHPUnit\Framework\TestCase;

class ReceivingOrderEligibilityTest extends TestCase
{
    private $app;
    protected function setUp(): void
    {
        $this->app=require dirname(__DIR__,2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        Schema::create('orders',function(Blueprint $t){$t->temporary();$t->id();$t->string('status');$t->timestamp('trash_at')->nullable();});
        Schema::create('warehouse_transfers',function(Blueprint $t){$t->temporary();$t->id();$t->integer('order_id');$t->string('status');});
    }
    protected function tearDown(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS warehouse_transfers');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS orders');
        $this->app->flush();restore_error_handler();restore_exception_handler();parent::tearDown();
    }
    public function test_cancelled_rejected_trashed_and_deleted_orders_do_not_enter_receiving(): void
    {
        foreach(['packed','cancelled','canceled','rejected','packing','delivered','completed'] as $i=>$status){
            $id=$i+1;
            DB::table('orders')->insert(['id'=>$id,'status'=>$status,'trash_at'=>$status==='packing'?now():null]);
            DB::table('warehouse_transfers')->insert(['id'=>$id,'order_id'=>$id,'status'=>'pending_shipper_pickup']);
        }
        DB::table('warehouse_transfers')->insert(['id'=>8,'order_id'=>999,'status'=>'in_transit']);
        self::assertSame([1,6,7],WarehouseTransfer::forReceiving()->orderBy('id')->pluck('id')->all());
        self::assertFalse(WarehouseTransfer::whereKey(2)->forReceiving()->exists());
        DB::table('orders')->where('id',1)->delete();
        self::assertFalse(WarehouseTransfer::whereKey(1)->forReceiving()->exists());
    }
    public function test_real_incoming_goods_remain_visible_across_transport_states(): void
    {
        DB::table('orders')->insert(['id'=>1,'status'=>'packed']);
        foreach(['pending_shipper_pickup','in_transit','delivered_waiting_receive','received_completed'] as $i=>$status){
            DB::table('warehouse_transfers')->insert(['id'=>$i+1,'order_id'=>1,'status'=>$status]);
        }
        self::assertSame(4,WarehouseTransfer::forReceiving()->count());
    }
}
