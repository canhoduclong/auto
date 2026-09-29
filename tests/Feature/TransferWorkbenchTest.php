<?php

namespace Tests\Feature;

use App\Http\Controllers\Warehouse\TransferWorkbenchController;
use App\Http\Controllers\Warehouse\WarehouseDispatchSlipController;
use App\Models\Order;
use App\Models\User;
use App\Models\WarehouseDispatchSlip;
use App\Services\WarehouseTransferCreationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

/** Isolated SQLite integration tests; never connect to the application database. */
class TransferWorkbenchTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(Kernel::class)->bootstrap();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        // Order observers send external journals/notifications; these tests exercise
        // transfer persistence, not those unrelated integrations.
        Order::flushEventListeners();
        $tables = [
            'warehouses' => 'name,address,phone',
            'users' => 'name,short_name,phone,warehouse_id',
            'roles' => 'name',
            'role_user' => 'role_id,user_id',
            'product_variants' => 'name,stock,product_id,size',
            'products' => 'name,unit',
            'customers' => 'name',
            'inventories' => 'product_variant_id,warehouse_id,quantity',
            'inventory_reservations' => 'inventory_id,quantity',
            'orders' => 'code,status,warehouse_id,order_transfer_id',
            'order_items' => 'order_id,product_variant_id,product_id,quantity,packed_weight,total_weight,price,is_priced_by_kg',
            'order_transfers' => 'shipper_id,warehouse_id,notes,created_by',
            'warehouse_transfers' => 'order_id,source_warehouse_id,target_warehouse_id,shipper_id,status,packed_total_weight',
            'warehouse_inventory_transfers' => 'transfer_code,source_warehouse_id,target_warehouse_id,requested_by,status,note,requested_at,export_document_id',
            'warehouse_inventory_transfer_items' => 'transfer_id,product_variant_id,quantity,weight_kg,unit_cost',
            'inventory_documents' => 'type,document_number,document_date,warehouse_id,supplier_id,shipping_fee,notes,user_id',
            'inventory_document_items' => 'inventory_document_id,product_variant_id,quantity,unit_cost',
            'inventory_movements' => 'inventory_id,quantity,type,reference_id,reference_type,user_id',
            'warehouse_dispatch_slips' => 'code,business_date,source_warehouse_id,target_warehouse_id,shipper_id,status,notes,created_by',
            'warehouse_dispatch_slip_entries' => 'warehouse_dispatch_slip_id,order_transfer_id,inventory_transfer_id,warehouse_transfer_id,snapshot',
        ];
        foreach ($tables as $table => $columns) {
            Schema::create($table, function (Blueprint $table) use ($columns): void {
                $table->id();
                foreach (explode(',', $columns) as $column) {
                    $table->string($column)->nullable();
                }
                $table->timestamps();
            });
        }
        DB::table('warehouses')->insert([['id' => 1, 'name' => 'Kho nguồn'], ['id' => 2, 'name' => 'Kho A'], ['id' => 3, 'name' => 'Kho B']]);
        DB::table('users')->insert([['id' => 1, 'name' => 'Kho', 'warehouse_id' => 1], ['id' => 2, 'name' => 'Tài xế', 'warehouse_id' => null]]);
        DB::table('roles')->insert(['id' => 1, 'name' => 'shipper']);
        DB::table('role_user')->insert(['role_id' => 1, 'user_id' => 2]);
        DB::table('product_variants')->insert(['id' => 1, 'name' => 'Size 2.7', 'stock' => 10, 'product_id' => 1, 'size' => '2.7']);
        DB::table('products')->insert(['id' => 1, 'name' => 'Vịt nguyên con', 'unit' => 'kg']);
        DB::table('inventories')->insert(['id' => 1, 'product_variant_id' => 1, 'warehouse_id' => 1, 'quantity' => 10]);
        DB::table('inventory_reservations')->insert(['inventory_id' => 1, 'quantity' => 2]);
        DB::table('orders')->insert(['id' => 1, 'code' => 'TEST-1', 'status' => 'packed', 'warehouse_id' => 1]);
        DB::table('order_items')->insert(['order_id' => 1, 'quantity' => 2, 'packed_weight' => 5.4, 'product_variant_id' => 1, 'price' => 100000, 'is_priced_by_kg' => 1]);
        Auth::setUser(User::findOrFail(1));
        $this->app['session']->start();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    private function group(int $target = 2, int $quantity = 3, array $orders = [1]): array
    {
        return ['target_warehouse_id' => $target, 'shipper_id' => 2, 'order_ids' => $orders,
            'items' => [['product_variant_id' => 1, 'quantity' => $quantity, 'weight_kg' => $quantity * 2.7]]];
    }

    private function submit(array $groups, ?string $token = null)
    {
        $request = Request::create('/warehouse/inventory-transfers/batch', 'POST', [
            'submission_token' => $token ?? (string) Str::uuid(), 'business_date' => '2026-09-28', 'groups' => $groups,
        ]);
        $request->setUserResolver(fn () => Auth::user());
        $request->setLaravelSession($this->app['session']->driver());
        $this->app->instance('request', $request);

        return (new TransferWorkbenchController)->store($request, new WarehouseTransferCreationService);
    }

    public function test_creates_order_stock_and_manifest_atomically_per_destination(): void
    {
        $response = $this->submit([$this->group(), $this->group(3, 2, [])]);
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals(2, DB::table('warehouse_dispatch_slips')->count());
        self::assertEquals(3, DB::table('warehouse_dispatch_slip_entries')->count());
        self::assertEquals(1, DB::table('order_transfers')->count());
        self::assertEquals(2, DB::table('warehouse_inventory_transfers')->count());
        self::assertEquals(5, DB::table('inventories')->value('quantity'));
        self::assertEquals(5, DB::table('product_variants')->value('stock'));
        self::assertEquals(-5, DB::table('inventory_movements')->sum('quantity'));
        self::assertEquals(13.5, DB::table('warehouse_inventory_transfer_items')->sum('weight_kg'));
        self::assertEquals(5.4, DB::table('warehouse_transfers')->value('packed_total_weight'));
        self::assertEquals('2026-09-28', substr(DB::table('inventory_documents')->value('document_date'), 0, 10));
        self::assertEquals('draft', DB::table('warehouse_dispatch_slips')->value('status'));
    }

    public function test_rolls_back_all_destinations_and_order_assignments_when_stock_runs_out(): void
    {
        $this->submit([$this->group(2, 6), $this->group(3, 3, [])]);
        self::assertEquals(0, DB::table('warehouse_dispatch_slips')->count());
        self::assertEquals(0, DB::table('warehouse_inventory_transfers')->count());
        self::assertEquals(0, DB::table('order_transfers')->count());
        self::assertEquals(0, DB::table('warehouse_transfers')->count());
        self::assertEquals(0, DB::table('inventory_movements')->count());
        self::assertEquals(0, DB::table('inventory_documents')->count());
        self::assertNull(DB::table('orders')->value('order_transfer_id'));
        self::assertEquals(10, DB::table('inventories')->value('quantity'));
        self::assertTrue($this->app['session']->get('errors')->has('groups'));
    }

    public function test_same_submission_token_cannot_export_twice(): void
    {
        $token = (string) Str::uuid();
        $this->submit([$this->group(2, 3, [])], $token);
        $this->submit([$this->group(2, 3, [])], $token);
        self::assertEquals(1, DB::table('warehouse_dispatch_slips')->count());
        self::assertEquals(7, DB::table('inventories')->value('quantity'));
    }

    public function test_rejects_order_owned_by_another_warehouse(): void
    {
        DB::table('orders')->update(['warehouse_id' => 3]);
        try {
            $this->submit([$this->group()]);
            self::fail('Expected validation failure');
        } catch (ValidationException) {
            self::assertEquals(0, DB::table('warehouse_dispatch_slips')->count());
            self::assertEquals(10, DB::table('inventories')->value('quantity'));
        }
    }

    public function test_rejects_orders_already_in_an_active_movement(): void
    {
        DB::table('warehouse_transfers')->insert(['order_id' => 1, 'status' => 'in_transit']);
        $this->expectException(ValidationException::class);
        $this->submit([$this->group()]);
    }

    public function test_rejects_duplicate_order_in_two_destinations(): void
    {
        $this->expectException(ValidationException::class);
        $this->submit([$this->group(), $this->group(3)]);
    }

    public function test_rejects_transfer_back_to_source_warehouse(): void
    {
        $this->expectException(ValidationException::class);
        $this->submit([$this->group(1)]);
    }

    public function test_rejects_non_shipper(): void
    {
        $group = $this->group();
        $group['shipper_id'] = 1;
        $this->expectException(ValidationException::class);
        $this->submit([$group]);
    }

    public function test_order_only_batch_does_not_change_stock(): void
    {
        $group = $this->group();
        unset($group['items']);
        $this->submit([$group]);
        self::assertEquals(1, DB::table('warehouse_dispatch_slips')->count());
        self::assertEquals(1, DB::table('warehouse_dispatch_slip_entries')->count());
        self::assertEquals(0, DB::table('inventory_movements')->count());
        self::assertEquals(10, DB::table('inventories')->value('quantity'));
    }

    public function test_combined_slip_print_contains_orders_goods_and_correct_totals(): void
    {
        $this->submit([$this->group()]);
        $slip = WarehouseDispatchSlip::firstOrFail();
        $controller = new WarehouseDispatchSlipController;
        (new \ReflectionMethod($controller, 'loadSlip'))->invoke($controller, $slip);
        $data = (new \ReflectionMethod($controller, 'documentData'))->invoke($controller, $slip);
        self::assertCount(1, $data['orderRows']);
        self::assertCount(1, $data['inventoryTransferRows']);
        self::assertEquals(5, $data['exportSummaryRows']->sum('quantity'));
        self::assertEquals(13.5, $data['exportSummaryRows']->sum('weight'));
        self::assertEquals(540000, $data['orderRows']->sum('product_amount'));
        $html = view('warehouse.dispatch-slips.print-export', ['slip' => $slip] + $data)->render();
        self::assertStringContainsString('TEST-1', $html);
        self::assertStringContainsString('Vịt nguyên con', $html);
        self::assertStringContainsString('13,5 kg', $html);
        self::assertStringContainsString('540.000đ', $html);
        self::assertStringContainsString('window.print()', $html);
    }
}
