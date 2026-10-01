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
    private function queue(int $warehouse, string $date)
    {
        return Order::query()->forPackingDate($date)->whereIn('status', ['approved', 'ready_to_pack', 'packing'])
            ->where(fn ($q) => $q->where('warehouse_id', $warehouse)->orWhereNull('warehouse_id')->orWhere('warehouse_id', 0));
    }

    public function test_shared_orders_stay_on_original_day_for_all_warehouses(): void
    {
        self::assertSame([1, 2, 3], $this->queue(1, '2026-09-30')->pluck('id')->all());
        self::assertSame([1, 2, 4], $this->queue(2, '2026-09-30')->pluck('id')->all());
        foreach ([1, 2] as $warehouse) {
            self::assertSame(0, $this->queue($warehouse, '2026-10-01')->count());
            self::assertSame(0, $this->queue($warehouse, '2026-09-29')->count());
        }
        DB::table('orders')->where('id', 1)->update(['warehouse_id' => 1, 'status' => 'packing']);
        self::assertSame([1, 2, 3], $this->queue(1, '2026-09-30')->pluck('id')->all());
        self::assertSame([2, 4], $this->queue(2, '2026-09-30')->pluck('id')->all());
        self::assertSame(0, $this->queue(1, '2026-10-01')->count());
    }

    public function test_accounting_import_keeps_its_business_date(): void
    {
        DB::table('orders')->insert(['warehouse_id' => null, 'status' => 'approved', 'created_at' => '2026-10-01 08:00:00', 'delivery_date' => '2026-09-30', 'accounting_sales_import_batch_id' => 1]);
        self::assertSame(4, $this->queue(1, '2026-09-30')->count());
        self::assertSame(0, $this->queue(1, '2026-10-01')->count());
    }
}
