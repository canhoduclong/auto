<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ExternalCuttingReceiptService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class ExternalCuttingReceiptTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(Kernel::class)->bootstrap();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        $tables = [
            'google_sheet_inventory_syncs' => 'inventory_date',
            'products' => 'name,product_type,cutting_product_targets,cutting_percentage',
            'product_variants' => 'name,product_id,status,sort_order,stock',
            'orders' => 'code',
            'inventories' => 'warehouse_id,product_variant_id,quantity,weight_kg,reserved_quantity,low_stock_threshold',
            'inventory_documents' => 'type,warehouse_id,document_date,document_number,notes,shipping_fee,user_id',
            'inventory_document_items' => 'inventory_document_id,product_variant_id,quantity,unit_cost,note',
            'inventory_movements' => 'inventory_id,quantity,weight_kg,type,reference_id,reference_type,user_id',
            'product_cutting_batches' => 'warehouse_id,order_id,target_product_variant_id,status,source_materials,performed_by,completed_by,completed_at,finished_import_document_id,input_weight,planned_finished_weight,actual_finished_weight,actual_component_weight,loss_weight,loss_percent,planned_components,actual_components,note',
            'cutting_component_import_requests' => 'warehouse_id,request_date,status,created_by,note',
            'cutting_component_import_request_items' => 'cutting_component_import_request_id,cutting_batch_id,order_id,product_variant_id,quantity,source_order_code',
        ];
        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach (explode(',', $columns) as $column) {
                    $table->string($column)->nullable();
                } $table->timestamps();
            });
        }
        DB::table('products')->insert([
            ['id' => 1, 'name' => 'Nguyên con', 'product_type' => 'whole', 'cutting_product_targets' => '{"2":[3]}', 'cutting_percentage' => null],
            ['id' => 2, 'name' => 'Thành phẩm', 'product_type' => 'cut', 'cutting_product_targets' => null, 'cutting_percentage' => null],
            ['id' => 3, 'name' => 'Phụ phẩm', 'product_type' => 'cut', 'cutting_product_targets' => null, 'cutting_percentage' => 20],
        ]);
        DB::table('product_variants')->insert([['id' => 1, 'product_id' => 1, 'status' => 1], ['id' => 2, 'product_id' => 2, 'status' => 1], ['id' => 3, 'product_id' => 3, 'status' => 1]]);
        DB::table('orders')->insert(['id' => 1, 'code' => 'TEST']);
        DB::table('inventories')->insert(['warehouse_id' => 1, 'product_variant_id' => 1, 'quantity' => 99, 'weight_kg' => 297]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    private function receive(): void
    {
        app(ExternalCuttingReceiptService::class)->receive(Order::findOrFail(1), ProductVariant::findOrFail(2), Product::findOrFail(1), 10, 25, 1, 1);
    }

    public function test_external_receipt_keeps_count_and_kg_separate_and_accumulates_byproducts(): void
    {
        DB::transaction(fn () => $this->receive());
        DB::transaction(fn () => $this->receive());
        self::assertEquals(99, DB::table('inventories')->where('product_variant_id', 1)->value('quantity'));
        self::assertEquals(20, DB::table('inventories')->where('product_variant_id', 2)->value('quantity'));
        self::assertEquals(50, DB::table('inventories')->where('product_variant_id', 2)->value('weight_kg'));
        self::assertSame(0, DB::table('inventories')->where('product_variant_id', 3)->count());
        self::assertSame(0, DB::table('inventory_documents')->where('type', 'export')->count());
        self::assertSame(1, DB::table('cutting_component_import_requests')->count());
        self::assertEquals(12.5, DB::table('cutting_component_import_request_items')->sum('quantity'));
        self::assertEquals(25, DB::table('inventory_movements')->first()->weight_kg);
    }

    public function test_failure_after_receipt_rolls_back_all_stock_documents_and_pending_components(): void
    {
        try {
            DB::transaction(function () {
                $this->receive();
                throw new \RuntimeException('Packing failed');
            });
        } catch (\RuntimeException $e) {
            self::assertSame('Packing failed', $e->getMessage());
        }
        self::assertSame(0, DB::table('inventory_documents')->count());
        self::assertSame(0, DB::table('product_cutting_batches')->count());
        self::assertSame(0, DB::table('cutting_component_import_request_items')->count());
        self::assertSame(1, DB::table('inventories')->count());
    }

    public function test_invalid_yield_is_rejected_before_import(): void
    {
        $this->expectException(ValidationException::class);
        ExternalCuttingReceiptService::componentWeights(25, 2, [2 => 0, 3 => 100]);
    }

    public function test_missing_component_variant_leaves_no_receipt(): void
    {
        DB::table('product_variants')->where('id', 3)->delete();
        try {
            DB::transaction(fn () => $this->receive());
            self::fail('Missing component must reject the receipt');
        } catch (ValidationException $exception) {
            self::assertSame(0, DB::table('inventory_documents')->count());
            self::assertSame(1, DB::table('inventories')->count());
        }
    }
    public function test_previous_day_receipt_is_available_to_packing_snapshot_before_completion(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-01 09:00:00');
        try {
            DB::transaction(function () {
                $document = app(ExternalCuttingReceiptService::class)->receive(Order::findOrFail(1), ProductVariant::findOrFail(2), Product::findOrFail(1), 43, 87.5, 1, 1, '2026-09-30');
                self::assertSame('2026-09-30', $document->document_date->toDateString());
                self::assertSame('2026-10-01', $document->created_at->toDateString());
                $controller = app(\App\Http\Controllers\WarehouseDashboardController::class);
                $snapshot = new \ReflectionMethod($controller, 'getStockAtDate');
                $stock = $snapshot->invoke($controller, collect([2]), 1, '2026-09-30');
                self::assertSame(43.0, $stock[2]);
                self::assertEquals(0.0, $snapshot->invoke($controller, collect([2]), 1, '2026-09-29')[2]);
                self::assertStringStartsWith('2026-09-30', DB::table('cutting_component_import_requests')->value('request_date'));
                self::assertSame(0, DB::table('inventory_documents')->where('type', 'export')->count());
            });
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }

}
