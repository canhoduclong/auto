<?php

namespace Tests\Feature;

use App\Models\{Customer, Role, User};
use App\Services\CustomerCardContextService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use PHPUnit\Framework\TestCase;

class CustomerCardContextTest extends TestCase
{
    private $app;
    private array $tables = ['customers', 'orders', 'users', 'customer_ownership_histories', 'settings'];

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.connections.mysql.prefix' => 'card_context_test_'.getmypid().'_']);
        DB::purge('mysql');
        Carbon::setTestNow('2026-10-27 12:00:00');
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('phone')->nullable(); $t->string('customer_status');
            foreach (['assigned_to', 'current_owner_sale_id', 'user_id'] as $column) $t->unsignedBigInteger($column)->nullable();
            $t->timestamp('assigned_at')->nullable(); $t->boolean('is_employee')->default(false); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('customer_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->string('status'); $t->unsignedBigInteger('accounting_sales_import_batch_id')->nullable();
            $t->date('delivery_date')->nullable(); $t->timestamp('delivered_at')->nullable(); $t->timestamp('trash_at')->nullable();
            $t->string('customer_feedback_status')->nullable(); $t->text('customer_feedback_note')->nullable(); $t->timestamp('customer_feedback_at')->nullable();
            $t->string('recipient_phone')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('customer_ownership_histories', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('customer_id'); $t->unsignedBigInteger('sale_id'); $t->timestamp('ended_at')->nullable();
        });
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->string('value'); });
        DB::table('users')->insert([['id' => 1, 'name' => 'Sale trước'], ['id' => 2, 'name' => 'Sale mới']]);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Khách tự do', 'phone' => '0703867945', 'customer_status' => 'free', 'user_id' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach ($this->tables as $table) Schema::dropIfExists($table);
        config(['database.connections.mysql.prefix' => '']);
        DB::purge('mysql');
        $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }

    private function sale(): User
    {
        $user = new User; $user->id = 2;
        $user->setRelation('roles', collect([new Role(['name' => 'sale'])]));
        return $user;
    }

    public function test_unclaimed_phone_is_redacted_from_json_and_history_is_summarized(): void
    {
        DB::table('customer_ownership_histories')->insert(['customer_id' => 1, 'sale_id' => 1, 'ended_at' => '2026-10-20']);
        $base = ['customer_id' => 1, 'user_id' => 1, 'recipient_phone' => '0703867945'];
        DB::table('orders')->insert($base + ['id' => 1, 'status' => 'completed', 'delivery_date' => '2026-10-10', 'created_at' => '2026-10-09', 'customer_feedback_status' => 'good', 'customer_feedback_at' => '2026-10-10', 'customer_feedback_note' => 'Khách hài lòng']);
        DB::table('orders')->insert($base + ['id' => 2, 'status' => 'delivered', 'delivery_date' => '2026-10-15', 'created_at' => '2026-10-14', 'customer_feedback_status' => 'careful', 'customer_feedback_at' => '2026-10-16', 'customer_feedback_note' => 'Đóng hàng kỹ hơn']);
        DB::table('orders')->insert($base + ['id' => 3, 'status' => 'approved', 'delivery_date' => '2026-10-28', 'created_at' => '2026-10-26']);
        DB::table('orders')->insert($base + ['id' => 4, 'status' => 'cancelled', 'delivery_date' => '2026-10-27', 'created_at' => '2026-10-27', 'customer_feedback_status' => 'risk', 'customer_feedback_at' => '2026-10-27']);
        DB::table('orders')->insert($base + ['id' => 5, 'status' => 'completed', 'trash_at' => '2026-10-27', 'delivery_date' => '2026-10-26', 'created_at' => '2026-10-26']);
        $customers = Customer::all();
        (new CustomerCardContextService)->enrich($customers, $this->sale());
        $customer = $customers->first(); $context = $customer->card_context;
        self::assertFalse($customer->can_view_contact);
        self::assertNull($customer->phone);
        self::assertStringNotContainsString('0703867945', $customer->toJson());
        self::assertSame('Sale trước', $context['previous_sale']);
        self::assertSame('26/10/2026', $context['last_order_date']);
        self::assertSame('15/10/2026', $context['last_received_date']);
        self::assertSame(12, $context['days_without_order']);
        self::assertSame(2, $context['feedback_total']);
        self::assertSame('Đóng hàng kỹ hơn', $context['latest_feedback']);
        self::assertStringContainsString('Rủi ro/khó tính: 0', $context['feedback_counts']);
        self::assertSame('0703867945', DB::table('customers')->where('id', 1)->value('phone'));
    }

    public function test_contact_becomes_visible_after_takeover_and_admin_retains_access(): void
    {
        $service = new CustomerCardContextService;
        $customer = Customer::first();
        self::assertFalse($service->canViewContact($customer, $this->sale()));
        $admin = new User; $admin->setRelation('roles', collect([new Role(['name' => 'admin'])]));
        self::assertTrue($service->canViewContact($customer, $admin));
        DB::table('customers')->where('id', 1)->update(['customer_status' => 'active', 'assigned_to' => 2, 'current_owner_sale_id' => 2, 'assigned_at' => now()]);
        $customers = Customer::all(); $service->enrich($customers, $this->sale());
        self::assertTrue($customers->first()->can_view_contact);
        self::assertSame('0703867945', $customers->first()->phone);
        self::assertNull($customers->first()->card_context['last_received_date']);
        self::assertSame(0, $customers->first()->card_context['feedback_total']);
    }
    public function test_free_customer_detail_requires_takeover_even_for_the_previous_creator(): void
    {
        $sale = $this->sale(); $sale->id = 1; auth()->setUser($sale);
        $controller = (new \ReflectionClass(\App\Http\Controllers\PageController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'ensureManagedCustomer');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Vui lòng nhận khách');
        $method->invoke($controller, Customer::first());
    }

}
