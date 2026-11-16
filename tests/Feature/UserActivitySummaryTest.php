<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserActivitySummaryService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;

class UserActivitySummaryTest extends TestCase
{
    private $app;

    private array $tables = ['admin_events', 'order_histories', 'orders', 'customers', 'mobile_api_tokens'];

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        Carbon::setTestNow(Carbon::parse('2026-11-16 14:00:00'));
        Schema::create('admin_events', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->integer('actor_id')->nullable();
            foreach (['event_type', 'action', 'subject_type', 'subject_id', 'title', 'message', 'url'] as $column) {
                $t->string($column)->nullable();
            }
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('order_histories', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->integer('user_id');
            $t->integer('order_id');
            foreach (['action', 'status_after', 'source', 'ip_address', 'note'] as $column) {
                $t->string($column)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->string('code');
            $t->integer('customer_id');
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('mobile_api_tokens', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->integer('user_id');
            $t->string('name');
            $t->string('ip_address')->nullable();
            $t->string('device_name')->nullable();
            $t->timestamps();
        });
        DB::table('customers')->insert(['id' => 1, 'name' => 'Khách A']);
        DB::table('orders')->insert(['id' => 1, 'code' => 'DH001', 'customer_id' => 1]);
        Schema::shouldReceive('hasTable')->andReturnUsing(fn ($table) => in_array($table, $this->tables, true));
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $table) {
            DB::statement('DROP TEMPORARY TABLE IF EXISTS `'.$table.'`');
        }
        Carbon::setTestNow();
        \Mockery::close();
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    private function user(): User
    {
        $user = new User;
        $user->id = 7;

        return $user;
    }

    private function summary(): array
    {
        return app(UserActivitySummaryService::class)->forUser($this->user(), now()->subDays(6)->startOfDay());
    }

    public function test_order_milestones_count_once_despite_observer_audits_and_exclude_other_users_and_old_events(): void
    {
        foreach ([['shipper_accepted', 'delivering'], ['shipper_pickup', 'shipping'], ['shipper_delivered', 'delivered'], ['mobile_complete_delivery', 'completed']] as $i => [$action, $status]) {
            $at = now()->subHours(4 - $i);
            DB::table('order_histories')->insert(['user_id' => 7, 'order_id' => 1, 'action' => $action, 'status_after' => $status, 'created_at' => $at, 'source' => 'api']);
            DB::table('admin_events')->insert(['actor_id' => 7, 'event_type' => 'order', 'action' => 'status_changed', 'title' => 'Cập nhật đơn', 'created_at' => $at,
                'metadata' => json_encode(['order_id' => 1, 'changes' => ['status' => $status]])]);
        }
        DB::table('order_histories')->insert(['user_id' => 8, 'order_id' => 1, 'action' => 'shipper_pickup', 'created_at' => now()]);
        DB::table('order_histories')->insert(['user_id' => 7, 'order_id' => 1, 'action' => 'shipper_pickup', 'created_at' => now()->subDays(7)]);
        $summary = $this->summary();
        self::assertSame(4, $summary['total']);
        foreach (['confirmed', 'pickup', 'delivered', 'completed'] as $key) {
            self::assertSame(1, $summary['counts'][$key]);
        }
        self::assertSame(0, $summary['counts']['shipping']);
        self::assertSame('Khách A', $summary['actions']->first()['customer']);
        self::assertSame('DH001', $summary['actions']->first()['order_code']);
        self::assertCount(7, $summary['days']);
    }

    public function test_real_login_audits_preserve_actor_and_are_not_doubled_by_tokens_or_token_refresh(): void
    {
        foreach ([1 => 'flutter-mobile', 2 => 'flutter-mobile-google', 3 => 'flutter-mobile-refresh'] as $id => $name) {
            DB::table('mobile_api_tokens')->insert(['id' => $id, 'user_id' => 7, 'name' => $name, 'created_at' => now()->subHour()]);
        }
        $request = Request::create('/api/mobile/login', 'POST', ['password' => 'never-log-this']);
        app(UserActivitySummaryService::class)->recordLogin($this->user(), $request, 'api', 1);
        $summary = $this->summary();
        self::assertSame(2, $summary['counts']['login']);
        $event = DB::table('admin_events')->first();
        self::assertSame(7, $event->actor_id);
        self::assertStringNotContainsString('never-log-this', $event->metadata);
        self::assertSame('Đăng nhập ứng dụng', $summary['actions']->first()['label']);
    }

    public function test_audit_only_status_changes_are_readable_but_ordinary_edits_and_undo_are_not_completions(): void
    {
        DB::table('admin_events')->insert(['actor_id' => 7, 'event_type' => 'order', 'action' => 'status_changed', 'created_at' => now(), 'metadata' => json_encode(['order_id' => 1, 'changes' => ['status' => 'delivered']])]);
        DB::table('admin_events')->insert(['actor_id' => 7, 'event_type' => 'order', 'action' => 'updated', 'title' => 'Cập nhật ghi chú', 'created_at' => now(), 'metadata' => json_encode(['order_id' => 1, 'changes' => ['shipper_note' => 'A']])]);
        DB::table('order_histories')->insert(['user_id' => 7, 'order_id' => 1, 'action' => 'undo_complete_delivery', 'status_after' => 'delivered', 'created_at' => now()->subHour()]);
        $summary = $this->summary();
        self::assertSame(1, $summary['counts']['delivered']);
        self::assertSame(0, $summary['counts']['completed']);
        self::assertSame(2, $summary['counts']['other']);
    }

    public function test_create_order_history_and_observer_are_one_business_action(): void
    {
        DB::table('order_histories')->insert(['user_id' => 7, 'order_id' => 1, 'action' => 'create_order', 'created_at' => now()]);
        DB::table('admin_events')->insert(['actor_id' => 7, 'event_type' => 'order', 'action' => 'created', 'subject_id' => 1, 'title' => 'Tạo đơn hàng', 'created_at' => now()]);
        self::assertSame(1, $this->summary()['total']);
    }

    public function test_web_login_event_records_a_durable_login_for_the_authenticated_user(): void
    {
        \Illuminate\Support\Facades\Event::dispatch(new \Illuminate\Auth\Events\Login('web', $this->user(), false));
        $summary = $this->summary();
        self::assertSame(1, $summary['counts']['login']);
        self::assertSame('Đăng nhập website', $summary['actions']->first()['label']);
        self::assertSame('Website', $summary['actions']->first()['source']);
    }

    public function test_daily_summary_counts_all_events_when_detail_display_is_limited(): void
    {
        $items = collect(range(1, 105))->map(fn ($i) => ['at' => now()->subSeconds($i), 'category' => 'pickup']);
        $summary = (new UserActivitySummaryService)->summarize($items, now()->subDays(6)->startOfDay());
        self::assertSame(105, $summary['counts']['pickup']);
        self::assertSame(105, $summary['days'][0]['total']);
        self::assertCount(100, $summary['days'][0]['items']);
        self::assertSame(5, $summary['days'][0]['remaining']);
    }
}
