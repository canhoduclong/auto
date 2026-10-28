<?php
namespace Tests\Feature;

use App\Http\Controllers\Admin\TextOrderImportController;
use App\Http\Controllers\OrderController;
use App\Models\{Order, TextOrderDraft, User, Role};
use App\Services\ApprovalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class AdditionalTemplateOrderTest extends TestCase
{
    private $app;
    private array $tables = [];
    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php'; $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.connections.mysql.prefix' => 'additional_template_test_'.getmypid().'_']); DB::purge('mysql');
        $this->table('users', ['name']);
        $this->table('customers', ['name', 'phone', 'address', 'deleted_at']);
        $this->table('product_variants', ['name']);
        $this->table('text_order_drafts', ['draft_scope', 'status', 'customer_name', 'phone', 'address', 'delivery_date', 'delivery_time', 'note', 'parsed_items', 'error_message', 'truck_brand_name', 'truck_station_name', 'truck_station_address', 'truck_station_phone', 'truck_receive_time'], ['created_by', 'sale_id', 'customer_id', 'order_id', 'use_truck_station', 'truck_brand_id', 'truck_station_id']);
        $this->table('orders', ['code', 'status', 'trash_at', 'creation_token', 'creation_payload_hash', 'additional_template_date', 'delivery_date'], ['user_id', 'customer_id', 'additional_text_order_draft_id']);
        $this->table('order_schedules', ['schedule_date', 'status', 'price_status', 'stock_status', 'review_meta'], ['customer_id', 'text_order_draft_id', 'generated_order_id', 'created_by', 'is_active']);
        DB::table('users')->insert(['id' => 3, 'name' => 'Sale']); DB::table('customers')->insert(['id' => 1, 'name' => 'Khách']); DB::table('product_variants')->insert(['id' => 1, 'name' => 'Hàng']);
        DB::table('orders')->insert(['id' => 1, 'code' => 'ORIGINAL', 'status' => 'completed', 'user_id' => 3, 'customer_id' => 1]);
        DB::table('text_order_drafts')->insert(['id' => 1, 'created_by' => 3, 'sale_id' => 3, 'customer_id' => 1, 'order_id' => 1, 'status' => 'draft', 'draft_scope' => 'sale_private', 'delivery_date' => '2026-10-28', 'parsed_items' => json_encode([['product_variant_id' => 1, 'quantity' => 2, 'unit_price' => 11000, 'size_kg' => 1]])]);
        DB::table('order_schedules')->insert(['id' => 1, 'text_order_draft_id' => 1, 'customer_id' => 1, 'generated_order_id' => 1, 'schedule_date' => '2026-10-28', 'status' => 'generated']);
        $user = User::findOrFail(3); $user->setRelation('roles', collect([new Role(['name' => 'sale'])])); auth()->setUser($user);
        $controller = new class extends OrderController {
            public function __construct() {}
            public function createOrderFromSchedule(array $items, array $orderData, ApprovalService $approvalService): Order {
                $order = new Order;
                $order->forceFill(['code' => 'EXTRA-'.Order::count(), 'status' => 'pending', 'user_id' => $orderData['user_id'], 'customer_id' => $orderData['customer_id'], 'creation_token' => $orderData['creation_token'], 'creation_payload_hash' => $orderData['creation_payload_hash']])->save();
                return $order;
            }
        };
        $this->app->instance(OrderController::class, $controller);
    }
    protected function tearDown(): void
    {
        foreach (array_reverse($this->tables) as $table) Schema::dropIfExists($table);
        config(['database.connections.mysql.prefix' => '']); DB::purge('mysql'); $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    private function table(string $name, array $strings, array $ids = []): void
    {
        Schema::create($name, function (Blueprint $t) use ($strings, $ids) { $t->id(); foreach ($strings as $field) $t->text($field)->nullable(); foreach ($ids as $field) $t->unsignedBigInteger($field)->nullable(); $t->timestamps(); }); $this->tables[] = $name;
    }
    private function request(string $token = '123e4567-e89b-42d3-a456-426614174000'): Request
    {
        $request = Request::create('/my-order-drafts/1/confirm-additional', 'POST', ['creation_token' => $token, 'customer_id' => 1, 'delivery_date' => '2026-10-28', 'items' => [['product_variant_id' => 1, 'quantity' => 2, 'unit_price' => 11000, 'size_kg' => 1]]]); $request->setUserResolver(fn () => auth()->user()); return $request;
    }
    private function confirm(Request $request)
    {
        $controller = (new \ReflectionClass(TextOrderImportController::class))->newInstanceWithoutConstructor();
        return $controller->saleConfirmAdditional($request, TextOrderDraft::findOrFail(1), app(ApprovalService::class))->getData(true);
    }
    public function test_additional_orders_keep_original_schedule_and_retry_does_not_duplicate(): void
    {
        Model::withoutEvents(function () {
            $first = $this->confirm($this->request()); $retry = $this->confirm($this->request());
            self::assertSame($first['order_id'], $retry['order_id']); self::assertSame(2, Order::count());
            $second = $this->confirm($this->request('123e4567-e89b-42d3-a456-426614174001'));
            self::assertNotSame($first['order_id'], $second['order_id']); self::assertSame(3, Order::count());
            self::assertSame(1, (int) DB::table('order_schedules')->value('generated_order_id')); self::assertSame(1, DB::table('order_schedules')->count());
            self::assertSame('completed', Order::findOrFail(1)->status);
            self::assertSame(2, TextOrderDraft::findOrFail(1)->additionalOrders()->count());
        });
    }
    public function test_reusing_token_with_changed_data_is_rejected(): void
    {
        Model::withoutEvents(function () {
            $this->confirm($this->request()); $request = $this->request(); $request->merge(['note' => 'Changed']);
            try { $this->confirm($request); self::fail('Changed replay accepted'); }
            catch (ValidationException $e) { self::assertArrayHasKey('creation_token', $e->errors()); }
            self::assertSame(2, Order::count());
        });
    }
    public function test_no_active_original_order_requires_normal_confirmation(): void
    {
        Model::withoutEvents(function () {
            DB::table('orders')->where('id', 1)->update(['status' => 'cancelled']);
            try { $this->confirm($this->request()); self::fail('No original order accepted'); }
            catch (ValidationException $e) { self::assertArrayHasKey('delivery_date', $e->errors()); }
            self::assertSame(1, Order::count());
        });
    }
    public function test_another_sale_cannot_create_additional_order(): void
    {
        $user = new User; $user->id = 999; auth()->setUser($user);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->confirm($this->request());
    }
}
