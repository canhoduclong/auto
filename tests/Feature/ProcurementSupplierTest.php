<?php

use App\Http\Controllers\ProcurementSupplierController;
use App\Models\{Supplier, ProcurementPurchase};
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use PHPUnit\Framework\TestCase;

class ProcurementSupplierTest extends TestCase
{
    protected function setUp(): void
    {
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        restore_error_handler();
        restore_exception_handler();
        if (config('database.connections.mysql.database') !== 'hltntc_test') {
            throw new RuntimeException('Supplier tests require hltntc_test.');
        }
        config(['database.connections.mysql.prefix' => 'supplier_feature_'.getmypid().'_']);
        DB::purge('mysql');
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('procurement_purchases', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('supplier_id'); $table->string('purchase_type');
            $table->dateTime('purchased_at'); $table->decimal('total_amount', 15, 2); $table->decimal('remaining_amount', 15, 2); $table->timestamps();
        });
        Carbon::setTestNow('2026-11-11 12:00:00');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('procurement_purchases');
        Schema::dropIfExists('suppliers');
        Carbon::setTestNow();
        DB::purge('mysql');
        parent::tearDown();
    }

    public function test_period_totals_and_all_time_debt_are_separate_and_search_is_grouped(): void
    {
        $supplier = Supplier::create(['name' => 'Sơ chế A', 'phone' => '0900000000', 'is_active' => true]);
        Supplier::create(['name' => 'Ngừng hợp tác', 'phone' => '0900000000', 'is_active' => false]);
        foreach ([['2026-11-11 23:59:59', 1000, 400, 'processed_duck'], ['2026-10-01', 2000, 900, 'processed_duck'], ['2026-11-11', 5000, 5000, 'live_duck']] as [$date, $total, $debt, $type]) {
            ProcurementPurchase::create(['supplier_id' => $supplier->id, 'purchase_type' => $type, 'purchased_at' => $date, 'total_amount' => $total, 'remaining_amount' => $debt]);
        }
        $view = (new ProcurementSupplierController)->index(Request::create('/procurement/suppliers', 'GET', ['period' => 'today', 'search' => '0900000000', 'status' => 'active']));
        $rows = $view->getData()['suppliers'];
        self::assertCount(1, $rows);
        self::assertEquals(1, $rows[0]->period_count);
        self::assertEquals(1000, $rows[0]->period_total);
        self::assertEquals(1300, $rows[0]->current_debt);
        $view = (new ProcurementSupplierController)->index(Request::create('/', 'GET', ['period' => 'custom', 'from_date' => '2026-10-01', 'to_date' => '2026-10-01']));
        self::assertEquals(2000, $view->getData()['suppliers']->firstWhere('id', $supplier->id)->period_total);
    }

    public function test_supplier_views_compile(): void
    {
        foreach (glob(resource_path('views/procurement/suppliers/*.blade.php')) as $path) {
            $compiled = app('blade.compiler')->compileString(file_get_contents($path));
            self::assertNotEmpty($compiled);
            $file = tempnam(sys_get_temp_dir(), 'supplier_blade_');
            try {
                file_put_contents($file, $compiled);
                exec('php -l '.escapeshellarg($file), $output, $status);
                self::assertSame(0, $status, implode("\n", $output));
            } finally { unlink($file); }
        }
    }
}
