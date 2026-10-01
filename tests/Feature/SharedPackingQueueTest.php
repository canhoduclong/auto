<?php
namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;

class SharedPackingQueueTest extends TestCase
{
    private $app;
    protected function setUp(): void
    {
        parent::setUp();
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(Kernel::class)->bootstrap();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        Schema::create('orders', function(Blueprint $table) {
            $table->id(); $table->integer('warehouse_id')->nullable(); $table->string('status');
            $table->integer('accounting_sales_import_batch_id')->nullable(); $table->date('delivery_date')->nullable(); $table->timestamps();
        });
        foreach ([null, 0, 1, 2] as $warehouse) {
            DB::table('orders')->insert(['warehouse_id'=>$warehouse,'status'=>'approved','created_at'=>'2026-09-30 14:16:00']);
        }
        DB::table('orders')->insert(['warehouse_id'=>null,'status'=>'cancelled','created_at'=>'2026-09-30 14:16:00']);
        DB::table('orders')->insert(['warehouse_id'=>null,'status'=>'packed','created_at'=>'2026-09-30 14:16:00']);
        DB::table('orders')->insert(['warehouse_id'=>null,'status'=>'approved','created_at'=>'2026-10-02 14:16:00']);
    }
    protected function tearDown(): void
    {
        DB::disconnect('sqlite'); $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    public function test_unassigned_backlog_visible_in_both_warehouses_but_not_cancelled_packed_or_future(): void
    {
        foreach ([1,2] as $warehouse) {
            $ids=Order::query()->forPackingDate('2026-10-01')->where(fn($q)=>$q->where('warehouse_id',$warehouse)->orWhereNull('warehouse_id')->orWhere('warehouse_id',0))->pluck('id')->all();
            self::assertSame([1,2],$ids);
        }
        DB::table('orders')->where('id',1)->update(['warehouse_id'=>1]);
        self::assertSame([2],Order::query()->forPackingDate('2026-10-01')->pluck('id')->all());
        self::assertSame(0,Order::query()->forPackingDate('2026-09-29')->count());
    }
}
