<?php

namespace Tests\Feature;

use App\Http\Controllers\CustomerDebtMonitorController;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerDebtSheetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerDebtMonitorTest extends TestCase
{
    private $app;

    private array $tables = [];

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        Schema::create('users', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->string('name');
            $t->string('short_name')->nullable();
        });
        $this->tables[] = 'users';
        Schema::create('roles', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->string('name');
        });
        Schema::create('role_user', function (Blueprint $t) {
            $t->temporary();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('user_id');
        });
        $this->tables[] = 'roles';
        $this->tables[] = 'role_user';
        Schema::create('permissions', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->string('name');
        });
        Schema::create('permission_role', function (Blueprint $t) {
            $t->temporary();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
        });
        $this->tables[] = 'permissions';
        $this->tables[] = 'permission_role';
        DB::table('roles')->insert(['id' => 1, 'name' => 'sale']);
        DB::table('role_user')->insert([['role_id' => 1, 'user_id' => 7], ['role_id' => 1, 'user_id' => 8]]);
        DB::table('users')->insert([['id' => 7, 'name' => 'Nguyễn Văn Huy', 'short_name' => 'Huy'], ['id' => 8, 'name' => 'Trần Văn Hà', 'short_name' => 'Hà']]);
        Schema::create('customers', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->string('name');
            $t->string('customer_code')->nullable();
            $t->string('name_normalized')->nullable()->unique();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('address')->nullable();
            $t->string('delivery_time')->nullable();
            $t->timestamp('free_from_date')->nullable();
            $t->unsignedInteger('current_cycle_no')->default(1);
            $t->timestamps();
            $t->string('phone')->nullable();
            $t->unsignedBigInteger('assigned_to')->nullable();
            $t->unsignedBigInteger('current_owner_sale_id')->nullable();
            $t->boolean('is_employee')->default(false);
            $t->string('customer_status')->default('managed');
            $t->timestamp('assigned_at')->nullable();
            $t->softDeletes();
        });
        $this->tables[] = 'customers';
        Schema::create('customer_addresses', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->unsignedBigInteger('customer_id');
            $t->text('note')->nullable();
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });
        $this->tables[] = 'customer_addresses';
        Schema::create('orders', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->unsignedBigInteger('customer_id');
            $t->date('delivery_date');
            $t->timestamp('created_at')->nullable();
        });
        $this->tables[] = 'orders';
        Schema::create('settings', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->string('key');
            $t->text('value')->nullable();
            $t->timestamps();
        });
        $this->tables[] = 'settings';
        Schema::create('debt_collection_reminders', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->uuid('request_id')->unique();
            $t->string('spreadsheet_id');
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('source_key', 64)->nullable();
            $t->json('source_keys');
            $t->string('customer_name');
            $t->timestamp('reminded_at');
            $t->bigInteger('balance_at_reminder');
            $t->bigInteger('target_amount');
            $t->date('promised_date')->nullable();
            $t->date('follow_up_date')->nullable();
            $t->string('channel');
            $t->text('note');
            $t->json('baseline_receipts');
            $t->string('snapshot_loaded_at')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->string('actor_name');
            $t->timestamps();
        });
        $this->tables[] = 'debt_collection_reminders';
        Schema::create('debt_collection_events', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->uuid('request_id')->unique();
            $t->unsignedBigInteger('reminder_id');
            $t->string('type');
            $t->text('note');
            $t->bigInteger('promised_amount')->nullable();
            $t->date('promised_date')->nullable();
            $t->string('receipt_token', 64)->nullable()->unique();
            $t->bigInteger('receipt_amount')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->string('actor_name');
            $t->timestamps();
        });
        $this->tables[] = 'debt_collection_events';
        Schema::create('customer_debt_balance_adjustments', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->uuid('request_id')->unique();
            foreach (['spreadsheet_id', 'identity', 'source_key', 'customer_name', 'customer_code', 'sale_name', 'actor_name'] as $field) {
                $t->string($field)->nullable();
            }
            $t->unsignedBigInteger('customer_id')->nullable();
            foreach (['sheet_balance', 'previous_balance', 'new_balance', 'delta'] as $field) {
                $t->bigInteger($field);
            }
            $t->date('effective_date');
            $t->text('reason');
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
        });
        $this->tables[] = 'customer_debt_balance_adjustments';
        DB::table('customers')->insert([
            ['id' => 1, 'name' => 'Chị Bình', 'customer_code' => 'CRM01', 'assigned_to' => 7],
            ['id' => 2, 'name' => 'Anh Hải', 'customer_code' => 'KH02', 'assigned_to' => 8],
        ]);
        DB::table('orders')->insert([
            ['id' => 1, 'customer_id' => 1, 'delivery_date' => now()->toDateString()],
            ['id' => 2, 'customer_id' => 2, 'delivery_date' => now()->toDateString()],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            DB::statement('DROP TEMPORARY TABLE IF EXISTS `'.$table.'`');
        }
        \Carbon\Carbon::setTestNow();
        \Carbon\CarbonImmutable::setTestNow();
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $user = new User;
        $user->id = 7;
        $user->setRelation('roles', collect([new Role(['name' => $role])]));

        return $user;
    }

    private function entry(string $code, string $name, int $row = 1): array
    {
        return ['code' => $code, 'name' => $name, 'date' => now()->toDateString(), 'row' => $row, 'note' => '', 'type' => 'sale', 'amount' => 1000];
    }

    private function service(array $entries): CustomerDebtSheetService
    {
        return new class($entries) extends CustomerDebtSheetService
        {
            public function __construct(private array $entries) {}

            public function snapshot(): ?array
            {
                return ['entries' => $this->entries, 'issues' => []];
            }
        };
    }

    private function request(array $params = [], string $role = 'sale'): Request
    {
        $request = Request::create('/customer-debt', 'GET', $params + ['policy_version' => 0]);
        $this->app->instance('request', $request);
        $request->setUserResolver(fn () => $this->user($role));
        $request->setLaravelSession($this->app['session']->driver('array'));

        return $request;
    }

    private function adjust(CustomerDebtSheetService $service, string $key, int|string $amount, ?string $revision = null, ?string $requestId = null): array
    {
        $controller = new CustomerDebtMonitorController;
        $view = $controller->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        $data = ['source_key' => $key, 'new_balance' => $amount, 'reason' => 'Đối chiếu thực tế', 'revision' => $revision ?? $view['groups'][$key]['balance_revision'], 'request_id' => $requestId ?? (string) \Illuminate\Support\Str::uuid()];

        return $controller->adjustBalance($this->request($data, 'admin'), $service, new \App\Services\CustomerDebtBalanceService)->getData(true);
    }

    public function test_actual_balance_adjustment_is_audited_and_survives_new_sheet_transactions(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $result = $this->adjust($service, $key, '500');
        self::assertSame(-500, $result['delta']);
        $audit = DB::table('customer_debt_balance_adjustments')->first();
        self::assertSame('Đối chiếu thực tế', $audit->reason);
        self::assertEquals(1000, $audit->previous_balance);
        self::assertEquals(7, $audit->created_by);
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('CRM01', 'Chị Bình', 2)]);
        $view = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key, 'window' => 'all'], 'admin'), $service)->getData();
        self::assertSame(1500, $view['closing']);
        self::assertSame(2000, $view['detailSales']);
        self::assertSame(0, $view['detailPayments']);
        self::assertSame(-500, $view['detailAdjustments']);
        self::assertSame(1, $view['balanceAdjustments']->total());
        self::assertNull($view['reportLastPayment']);
    }

    public function test_adjustment_of_linked_aliases_applies_once_to_the_customer_total(): void
    {
        $service = $this->service([$this->entry('OLD1', 'Chị Bình'), $this->entry('OLD2', 'Tên khác')]);
        $keys = array_keys($service->summarize($service->snapshot()['entries']));
        foreach ($keys as $key) {
            $service->mapCustomer($key, 1, 7);
        }
        $this->adjust($service, $keys[0], 800);
        $view = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        self::assertSame(800, $view['customerDebts'][1]['balance']);
        foreach ($view['groups'] as $group) {
            self::assertSame(800, $group['display_balance']);
        }
        self::assertCount(1, DB::table('customer_debt_balance_adjustments')->get());
    }

    public function test_retry_is_idempotent_and_stale_balance_cannot_overwrite_a_newer_adjustment(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $view = (new CustomerDebtMonitorController)->index($this->request([], 'admin'), $service)->getData();
        $key = array_key_first($view['groups']);
        $revision = $view['groups'][$key]['balance_revision'];
        $requestId = (string) \Illuminate\Support\Str::uuid();
        $first = $this->adjust($service, $key, 500, $revision, $requestId);
        self::assertSame($first['adjustment_id'], $this->adjust($service, $key, 500, $revision, $requestId)['adjustment_id']);
        self::assertSame(1, DB::table('customer_debt_balance_adjustments')->count());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->adjust($service, $key, 200, $revision);
    }

    public function test_unmapped_adjustment_can_be_negative_and_follows_later_mapping(): void
    {
        $service = $this->service([$this->entry('OLD1', 'Khách chưa ghép')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->adjust($service, $key, '-2.000');
        $service->mapCustomer($key, 1, 7);
        $view = (new CustomerDebtMonitorController)->index($this->request([], 'admin'), $service)->getData();
        self::assertSame(-2000, $view['customerDebts'][1]['balance']);
        self::assertSame(0, $view['customerDebts'][1]['alerts']['unpaid_orders']);
    }

    public function test_manual_balance_remains_visible_when_sheet_source_disappears(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->adjust($service, $key, 1500);
        $view = (new CustomerDebtMonitorController)->index($this->request([], 'admin'), $this->service([]))->getData();
        self::assertSame(500, $view['customerDebts'][1]['balance']);
        self::assertSame(0, $view['customerDebts'][1]['alerts']['unpaid_orders']);
    }

    public function test_sheet_refresh_invalidates_an_open_adjustment_form(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $data = (new CustomerDebtMonitorController)->index($this->request([], 'admin'), $service)->getData();
        $key = array_key_first($data['groups']);
        $revision = $data['groups'][$key]['balance_revision'];
        $newService = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('CRM01', 'Chị Bình', 2)]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->adjust($newService, $key, 500, $revision);
    }

    public function test_zero_actual_balance_clears_warning_without_recording_a_payment(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->adjust($service, $key, 0);
        $data = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key, 'window' => 'all'], 'admin'), $service)->getData();
        self::assertSame(0, $data['closing']);
        self::assertSame(0, $data['detailPayments']);
        self::assertFalse($data['reportAlerts']['remind']);
        self::assertFalse($data['reportAlerts']['overdue']);
        self::assertSame(0, $data['reportAlerts']['unpaid_orders']);
    }

    public function test_sales_cannot_adjust_actual_balances(): void
    {
        $this->expectException(HttpException::class);
        (new CustomerDebtMonitorController)->adjustBalance($this->request(), $this->service([]), new \App\Services\CustomerDebtBalanceService);
    }

    public function test_balance_adjustment_requires_a_reason(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new CustomerDebtMonitorController)->adjustBalance($this->request(['source_key' => str_repeat('a', 64), 'new_balance' => 0, 'revision' => str_repeat('a', 64), 'request_id' => (string) \Illuminate\Support\Str::uuid(), 'reason' => ' '], 'admin'), $this->service([]), new \App\Services\CustomerDebtBalanceService);
    }

    private function markRecovery(CustomerDebtSheetService $service, string $key, string $status, string $role = 'admin'): void
    {
        $controller = new CustomerDebtMonitorController;
        $data = $controller->index($this->request(['tab' => 'all', 'recovery' => 'all'], $role), $service)->getData();
        $controller->updateRecovery($this->request(['source_key' => $key, 'recovery_status' => $status, 'recovery_note' => 'Khách ngưng hoạt động', 'recovery_version' => $data['groups'][$key]['recovery']['version']], $role), $service, new \App\Services\CustomerDebtRecoveryService);
    }

    public function test_flagged_recovery_is_hidden_by_default_and_can_be_filtered_or_restored(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải')]);
        $key = array_key_first($service->summarize([$this->entry('CRM01', 'Chị Bình')]));
        $this->markRecovery($service, $key, 'difficult');
        $controller = new CustomerDebtMonitorController;
        $default = $controller->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        self::assertSame(1, $default['debts']->total());
        self::assertSame(1000, $default['customerDebts'][1]['balance']);
        self::assertSame(2, $controller->index($this->request(['tab' => 'all', 'recovery' => 'all'], 'admin'), $service)->getData()['debts']->total());
        self::assertSame(1, $controller->index($this->request(['tab' => 'all', 'recovery' => 'difficult'], 'admin'), $service)->getData()['debts']->total());
        $this->markRecovery($service, $key, 'unrecoverable');
        self::assertSame(1, $controller->index($this->request(['tab' => 'all', 'recovery' => 'unrecoverable'], 'admin'), $service)->getData()['debts']->total());
        $this->markRecovery($service, $key, 'normal');
        self::assertSame(2, $controller->index($this->request(['tab' => 'all'], 'admin'), $service)->getData()['debts']->total());
        self::assertSame(3, DB::table('settings')->where('key', 'like', 'customer_debt_recovery_audit_%')->count());
    }

    public function test_recovery_status_follows_source_mapping_and_covers_customer_aliases(): void
    {
        $service = $this->service([$this->entry('OLD1', 'Nguồn A'), $this->entry('OLD2', 'Nguồn B')]);
        $keys = array_keys($service->summarize($service->snapshot()['entries']));
        $this->markRecovery($service, $keys[0], 'unrecoverable');
        foreach ($keys as $key) {
            $service->mapCustomer($key, 1, 7);
        }
        $controller = new CustomerDebtMonitorController;
        self::assertSame(0, $controller->index($this->request(['tab' => 'all'], 'admin'), $service)->getData()['debts']->total());
        $this->markRecovery($service, $keys[1], 'normal');
        self::assertSame(2, $controller->index($this->request(['tab' => 'all'], 'admin'), $service)->getData()['debts']->total());
    }

    public function test_sales_cannot_mark_another_customers_recovery(): void
    {
        $service = $this->service([$this->entry('KH02', 'Anh Hải')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(HttpException::class);
        (new CustomerDebtMonitorController)->updateRecovery($this->request(['source_key' => $key, 'recovery_status' => 'difficult', 'recovery_version' => str_repeat('a', 64)]), $service, new \App\Services\CustomerDebtRecoveryService);
    }

    public function test_recovery_updates_reject_a_stale_form(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $before = (new CustomerDebtMonitorController)->index($this->request([], 'admin'), $service)->getData()['groups'][$key]['recovery']['version'];
        $this->markRecovery($service, $key, 'difficult');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new CustomerDebtMonitorController)->updateRecovery($this->request(['source_key' => $key, 'recovery_status' => 'normal', 'recovery_version' => $before], 'admin'), $service, new \App\Services\CustomerDebtRecoveryService);
    }

    private function quickCreate(CustomerDebtSheetService $service, array $overrides = []): void
    {
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $priorities = $this->createMock(\App\Services\CustomerPriorityService::class);
        $priorities->expects(self::once())->method('ensureLifecycle');
        \Illuminate\Support\Facades\Event::fake(['eloquent.created: App\Models\Customer']);
        (new CustomerDebtMonitorController)->createCustomer($this->request($overrides + ['source_key' => $key, 'name' => 'Khách hàng mới', 'customer_code' => 'NEW01', 'phone' => '0901234567', 'delivery_time' => '07:30'], 'admin'), $service, $priorities);
    }

    public function test_quick_creation_creates_and_maps_customer_without_changing_debt(): void
    {
        $service = $this->service([$this->entry('NEW01', 'Khách hàng mới')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->quickCreate($service);
        $customer = DB::table('customers')->where('customer_code', 'NEW01')->first();
        self::assertNotNull($customer);
        self::assertSame('khách hàng mới', $customer->name_normalized);
        self::assertSame('free', $customer->customer_status);
        self::assertSame('07:30', $customer->delivery_time);
        self::assertSame($customer->id, $service->mappings()[$key]['customer_id']);
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        self::assertSame(1000, $data['customerDebts'][$customer->id]['balance']);
    }

    public function test_quick_creation_assigns_selected_sale_through_the_existing_lifecycle(): void
    {
        $service = $this->service([$this->entry('NEW01', 'Khách mới')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        \Illuminate\Support\Facades\Event::fake(['eloquent.created: App\Models\Customer']);
        $priorities = $this->createMock(\App\Services\CustomerPriorityService::class);
        $priorities->expects(self::once())->method('attachSale')->with(self::callback(fn ($customer) => (int) $customer->assigned_to === 8), 8, 1, 'created')->willReturn(new \App\Models\CustomerPriority);
        (new CustomerDebtMonitorController)->createCustomer($this->request(['source_key' => $key, 'name' => 'Khách mới', 'customer_code' => 'NEW01', 'delivery_time' => '07:30', 'sale_id' => 8], 'admin'), $service, $priorities);
        $customer = DB::table('customers')->where('customer_code', 'NEW01')->first();
        self::assertEquals(8, $customer->current_owner_sale_id);
        self::assertSame('active', $customer->customer_status);
    }

    public function test_quick_creation_rolls_back_if_customer_lifecycle_fails(): void
    {
        $service = $this->service([$this->entry('NEW01', 'Khách mới')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        \Illuminate\Support\Facades\Event::fake(['eloquent.created: App\Models\Customer']);
        $priorities = $this->createMock(\App\Services\CustomerPriorityService::class);
        $priorities->method('ensureLifecycle')->willThrowException(new \RuntimeException('Simulated failure'));
        try {
            (new CustomerDebtMonitorController)->createCustomer($this->request(['source_key' => $key, 'name' => 'Khách mới', 'delivery_time' => '07:30'], 'admin'), $service, $priorities);
            self::fail('Failure not propagated');
        } catch (\RuntimeException $e) {
            self::assertSame('Simulated failure', $e->getMessage());
        }
        self::assertSame(2, DB::table('customers')->count());
        self::assertSame([], $service->mappings());
    }

    public function test_quick_creation_rejects_duplicate_customers_and_does_not_map_source(): void
    {
        DB::table('customers')->where('id', 1)->update(['name_normalized' => \App\Models\Customer::normalizeName('Chị Bình')]);
        $service = $this->service([$this->entry('NEW01', 'Nguồn khác')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        try {
            (new CustomerDebtMonitorController)->createCustomer($this->request(['source_key' => $key, 'name' => 'Chị Bình', 'delivery_time' => '07:30'], 'admin'), $service, $this->createMock(\App\Services\CustomerPriorityService::class));
            self::fail('Duplicate was accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            self::assertArrayHasKey('name', $e->errors());
        }
        self::assertSame(2, DB::table('customers')->count());
        self::assertSame([], $service->mappings());
    }

    public function test_quick_creation_rejects_source_that_has_already_been_mapped(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new CustomerDebtMonitorController)->createCustomer($this->request(['source_key' => $key, 'name' => 'Tên mới', 'delivery_time' => '07:30'], 'admin'), $service, $this->createMock(\App\Services\CustomerPriorityService::class));
    }

    public function test_sales_cannot_create_unmatched_source_customers(): void
    {
        $this->expectException(HttpException::class);
        (new CustomerDebtMonitorController)->createCustomer($this->request(), $this->service([]), $this->createMock(\App\Services\CustomerPriorityService::class));
    }

    public function test_quick_creation_validates_sale_assignment(): void
    {
        $service = $this->service([$this->entry('NEW01', 'Khách hàng mới')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new CustomerDebtMonitorController)->createCustomer($this->request(['source_key' => $key, 'name' => 'Tên mới', 'delivery_time' => '07:30', 'sale_id' => 999], 'admin'), $service, $this->createMock(\App\Services\CustomerPriorityService::class));
    }

    private function sheetCustomerImporter(array $values): \App\Services\GoogleSheetCustomerImportService
    {
        $sheets = new class($values) extends CustomerDebtSheetService
        {
            public function __construct(private array $values) {}

            public function fetchSales(): array
            {
                return $this->values;
            }
        };

        return new \App\Services\GoogleSheetCustomerImportService($sheets, $this->createMock(\App\Services\CustomerPriorityService::class));
    }

    private function customerSheetValues(array $rows): array
    {
        return array_merge([['Ngày', 'Mã KH', 'Khách hàng', 'NVKD', 'Tổng tiền']], array_map(fn ($row) => array_merge(['12/11/2026'], $row), $rows));
    }

    public function test_google_customer_preview_groups_sales_and_detects_conflicting_matches_without_writes(): void
    {
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([
            ['NEW01', 'Khách mới', 'Huy', '1000'], ['NEW01', 'Khách mới', 'Huy', '2000'],
            ['KH02', 'Chị Bình', '', '1000'], ['CRM01', 'Tên nguồn khác', '', '1000'],
        ]));
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        $rows = $importer->plan($importer->snapshot($token, $actor));
        self::assertCount(3, $rows);
        self::assertSame(1, collect($rows)->where('status', 'conflict')->count());
        self::assertSame(1, collect($rows)->where('status', 'create')->count());
        self::assertSame(1, collect($rows)->where('status', 'link')->count());
        self::assertSame(7, collect($rows)->firstWhere('code', 'NEW01')['sale_id']);
        self::assertSame(2, DB::table('customers')->count());
        self::assertSame([], (new CustomerDebtSheetService)->mappings());
    }

    public function test_google_customer_import_links_and_creates_once_without_overwriting_existing_data(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.created: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([
            ['CRM01', 'Alias khác tên', '', '1000'], ['NEW01', 'Khách mới', '', '1000'],
        ]));
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        $counts = $importer->commit($token, $actor, true);
        self::assertSame(['created' => 1, 'linked' => 1, 'skipped' => 0, 'errors' => 0], $counts);
        self::assertSame(3, DB::table('customers')->count());
        self::assertSame('Chị Bình', DB::table('customers')->where('id', 1)->value('name'));
        self::assertCount(2, (new CustomerDebtSheetService)->mappings());
        $again = $importer->commit($token, $actor, true);
        self::assertSame(0, $again['created']);
        self::assertSame(0, $again['linked']);
        self::assertSame(3, DB::table('customers')->count());
        self::assertCount(2, collect($importer->plan($importer->snapshot($token, $actor)))->where('status', 'imported'));
    }

    public function test_google_customer_manual_link_resolves_ambiguous_source_aliases(): void
    {
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([
            ['OLD01', 'Tên trùng', '', '1000'], ['OLD02', 'Tên trùng', '', '1000'],
        ]));
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        $snapshot = $importer->snapshot($token, $actor);
        self::assertCount(2, collect($importer->plan($snapshot))->where('status', 'conflict'));
        foreach (array_keys($snapshot['groups']) as $key) {
            $importer->decide($token, $actor, ['source_key' => $key, 'action' => 'link', 'customer_id' => 1]);
        }
        $counts = $importer->commit($token, $actor, true);
        self::assertSame(2, $counts['linked']);
        self::assertSame(0, $counts['created']);
        self::assertSame(2, DB::table('customers')->count());
    }

    public function test_google_customer_import_respects_skip_and_create_permission(): void
    {
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([['NEW01', 'Khách mới', '', '1000'], ['CRM01', 'Chị Bình', '', '1000']]));
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        $snapshot = $importer->snapshot($token, $actor);
        $key = collect($snapshot['groups'])->firstWhere('code', 'CRM01')['key'];
        $importer->decide($token, $actor, ['source_key' => $key, 'action' => 'skip']);
        $counts = $importer->commit($token, $actor, false);
        self::assertSame(0, $counts['created']);
        self::assertSame(2, $counts['skipped']);
        self::assertSame(2, DB::table('customers')->count());
    }

    public function test_google_customer_preview_is_scoped_to_the_loading_user(): void
    {
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([['NEW01', 'Khách mới', '', '1000']]));
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        $other = $this->user('admin');
        $other->id = 8;
        $this->expectException(HttpException::class);
        $importer->snapshot($token, $other);
    }

    public function test_google_customer_import_rechecks_customers_added_since_preview(): void
    {
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([['NEW01', 'Khách mới', '', '1000']]));
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        DB::table('customers')->insert(['id' => 3, 'name' => 'Khách mới', 'name_normalized' => \App\Models\Customer::normalizeName('Khách mới'), 'customer_code' => 'NEW01']);
        $counts = $importer->commit($token, $actor, true);
        self::assertSame(0, $counts['created']);
        self::assertSame(1, $counts['linked']);
        self::assertSame(3, DB::table('customers')->count());
    }

    public function test_google_customer_import_processes_in_batches_and_resumes_without_duplicates(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.created: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([['NEW01', 'Khách mới A', '', '1000'], ['NEW02', 'Khách mới B', '', '1000']]));
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        self::assertSame(1, $importer->commit($token, $actor, true, 1)['created']);
        self::assertSame(3, DB::table('customers')->count());
        self::assertSame(1, $importer->commit($token, $actor, true, 1)['created']);
        self::assertSame(4, DB::table('customers')->count());
        self::assertSame(0, $importer->commit($token, $actor, true, 1)['created']);
    }

    public function test_google_customer_import_endpoint_enforces_import_permission(): void
    {
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\GoogleSheetCustomerImportController)->index($this->request(), $this->sheetCustomerImporter([]), new CustomerDebtSheetService);
    }

    public function test_google_customer_sync_updates_selected_fields_and_creates_new_customers(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.created: App\Models\Customer', 'eloquent.updated: App\Models\Customer']);
        DB::table('customers')->where('id', 1)->update(['phone' => '0900000000']);
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([['CRM01', 'Chị Bình mới', '', '1000'], ['NEW01', 'Khách mới', '', '1000']]));
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync');
        $rows = $importer->plan($importer->snapshot($token, $actor));
        self::assertSame(1, collect($rows)->where('status', 'update')->count());
        $counts = $importer->commit($token, $actor, true);
        self::assertSame(1, $counts['updated']);
        self::assertSame(1, $counts['created']);
        self::assertSame('Chị Bình mới', DB::table('customers')->where('id', 1)->value('name'));
        self::assertSame('0900000000', DB::table('customers')->where('id', 1)->value('phone'));
        self::assertEquals(7, DB::table('customers')->where('id', 1)->value('assigned_to'));
        $audit = DB::table('settings')->where('key', 'like', 'customer_sheet_import_audit_%')->get()->map(fn ($record) => json_decode($record->value, true))->firstWhere('action', 'update');
        self::assertSame('Chị Bình', $audit['before']['name']);
        self::assertSame('Chị Bình mới', $audit['after']['name']);
        $next = $importer->load($actor, 'sync');
        self::assertCount(2, collect($importer->plan($importer->snapshot($next, $actor)))->where('status', 'unchanged'));
    }

    public function test_google_customer_sync_keeps_latest_nonempty_contact_information(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.updated: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter([
            ['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền', 'SĐT', 'Địa chỉ'],
            ['01/11/2026', 'CRM01', 'Tên cũ', '1000', '0901111111', 'Địa chỉ mới'],
            ['12/11/2026', 'CRM01', 'Tên mới', '1000', '', ''],
        ]);
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync');
        $row = array_values($importer->plan($importer->snapshot($token, $actor)))[0];
        self::assertSame('Tên mới', $row['new_name']);
        self::assertSame('0901111111', $row['phone']);
        self::assertSame(1, $importer->commit($token, $actor, true)['updated']);
        self::assertSame('0901111111', DB::table('customers')->where('id', 1)->value('phone'));
        self::assertSame('Địa chỉ mới', DB::table('customer_addresses')->where('customer_id', 1)->value('note'));
    }

    public function test_google_customer_sync_preserves_unselected_fields(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.updated: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter([['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền', 'SĐT'], ['12/11/2026', 'CRM01', 'Tên nguồn khác', '1000', '0901111111']]);
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync', ['phone']);
        self::assertSame(1, $importer->commit($token, $actor, true)['updated']);
        self::assertSame('Chị Bình', DB::table('customers')->where('id', 1)->value('name'));
        self::assertSame('0901111111', DB::table('customers')->where('id', 1)->value('phone'));
    }

    public function test_google_customer_sync_rejects_stale_customer_changes(): void
    {
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([['CRM01', 'Tên mới', '', '1000']]));
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync');
        DB::table('customers')->where('id', 1)->update(['phone' => '0903333333']);
        self::assertSame(1, $importer->commit($token, $actor, true)['errors']);
        self::assertSame('Chị Bình', DB::table('customers')->where('id', 1)->value('name'));
    }

    public function test_google_customer_sync_requires_one_authoritative_source_for_conflicting_aliases(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.updated: App\Models\Customer']);
        $values = $this->customerSheetValues([['OLD1', 'Tên nguồn A', '', '1000'], ['OLD2', 'Tên nguồn B', '', '1000']]);
        $importer = $this->sheetCustomerImporter($values);
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        $snapshot = $importer->snapshot($token, $actor);
        $sheets = new CustomerDebtSheetService;
        foreach ($snapshot['groups'] as $group) {
            $sheets->mapSourceGroupWithinLock($group, 1, 7);
        }
        $token = $importer->load($actor, 'sync');
        $snapshot = $importer->snapshot($token, $actor);
        self::assertCount(2, collect($importer->plan($snapshot))->where('status', 'conflict'));
        $keys = array_keys($snapshot['groups']);
        $importer->decide($token, $actor, ['source_key' => $keys[0], 'action' => 'link', 'customer_id' => 1]);
        self::assertSame(1, $importer->commit($token, $actor, true)['updated']);
        self::assertSame('Tên nguồn B', DB::table('customers')->where('id', 1)->value('name'));
        $next = $importer->load($actor, 'sync');
        self::assertCount(2, collect($importer->plan($importer->snapshot($next, $actor)))->where('status', 'unchanged'));
    }

    public function test_google_customer_sync_updates_a_shared_customer_only_once_for_identical_source_values(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.updated: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([['OLD1', 'Tên mới', '', '1000'], ['OLD2', 'Tên mới', '', '1000']]));
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        foreach ($importer->snapshot($token, $actor)['groups'] as $group) {
            (new CustomerDebtSheetService)->mapSourceGroupWithinLock($group, 1, 7);
        }
        $token = $importer->load($actor, 'sync');
        $counts = $importer->commit($token, $actor, true);
        self::assertSame(1, $counts['updated']);
        self::assertSame(1, $counts['linked']);
        self::assertSame(0, $counts['errors']);
        self::assertSame('Tên mới', DB::table('customers')->where('id', 1)->value('name'));
    }

    public function test_google_customer_sync_respects_global_field_selection_with_saved_source_preferences(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.updated: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter([['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền', 'SĐT'], ['12/11/2026', 'CRM01', 'Tên nguồn mới', '1000', '0901111111']]);
        $actor = $this->user('admin');
        $token = $importer->load($actor);
        $group = array_values($importer->snapshot($token, $actor)['groups'])[0];
        (new CustomerDebtSheetService)->mapSourceGroupWithinLock($group + ['sync_preferences' => ['name', 'phone']], 1, 7);
        $token = $importer->load($actor, 'sync', ['phone']);
        $row = array_values($importer->plan($importer->snapshot($token, $actor)))[0];
        self::assertSame(['phone'], array_keys($row['changes']));
        self::assertSame(1, $importer->commit($token, $actor, true)['updated']);
        self::assertSame('Chị Bình', DB::table('customers')->where('id', 1)->value('name'));
    }

    public function test_google_customer_sync_enforces_customer_update_permission(): void
    {
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([['CRM01', 'Tên nguồn mới', '', '1000']]));
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync');
        \Illuminate\Support\Facades\Gate::before(fn ($user, $ability) => $ability === 'update' ? false : null);
        self::assertSame(1, $importer->commit($token, $actor, true)['errors']);
        self::assertSame('Chị Bình', DB::table('customers')->where('id', 1)->value('name'));
    }

    private function collectionClock(string $at): void
    {
        \Carbon\Carbon::setTestNow($at);
        \Carbon\CarbonImmutable::setTestNow($at);
    }

    private function collectionRow(array $payments = []): array
    {
        $sale = $this->entry('CRM01', 'Chị Bình');
        $sale['date'] = '2026-11-01';
        $sale['amount'] = 10000;
        $entries = [$sale];
        foreach ($payments as $index => $payment) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $index + 1), ['type' => 'payment', 'note' => 'Thu tiền'], $payment);
        }

        return ['key' => hash('sha256', 'code:CRM01'), 'source_keys' => [hash('sha256', 'code:CRM01')], 'customer_id' => 1, 'name' => 'Chị Bình', 'balance' => 10000 - collect($payments)->sum('amount'), 'entries' => $entries];
    }

    private function reminderData(array $overrides = []): array
    {
        return $overrides + ['request_id' => (string) \Illuminate\Support\Str::uuid(), 'target_amount' => 1000, 'channel' => 'phone', 'note' => 'Đã gọi điện nhắc thanh toán', 'promised_date' => null, 'follow_up_date' => null];
    }

    public function test_debt_report_is_admin_only_and_deduplicates_linked_sources(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('OLD02', 'Tên nguồn khác')]);
        $keys = array_keys($service->summarize($service->snapshot()['entries']));
        foreach ($keys as $key) {
            $service->mapCustomer($key, 1, 7);
        }
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $data = $controller->index($this->request([], 'admin'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(1, $data['rows']->total());
        self::assertSame(2000, $data['rows']->first()['balance']);
        $this->expectException(HttpException::class);
        $controller->index($this->request(), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService);
    }

    public function test_debt_report_filters_order_dates_without_truncating_historical_balance(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $entries = [array_replace($this->entry('CRM01', 'Chị Bình'), ['date' => '2026-11-01']), array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-10-01']), array_replace($this->entry('KH02', 'Anh Hải'), ['date' => '2026-11-05'])];
        $data = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request(['scope' => 'orders', 'from' => '2026-11-01', 'to' => '2026-11-01'], 'admin'), $this->service($entries), new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(1, $data['rows']->total());
        self::assertSame(2000, $data['rows']->first()['balance']);
        self::assertSame(11, $data['rows']->first()['days_without_purchase']);
        self::assertNull($data['rows']->first()['days_without_payment']);
    }

    public function test_debt_report_order_filter_excludes_old_sheet_buyers_even_with_recent_system_orders(): void
    {
        $this->collectionClock('2026-11-13 10:00:00');
        DB::table('orders')->update(['delivery_date' => '2026-11-13']);
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình'), ['date' => '2025-03-08']),
            array_replace($this->entry('KH02', 'Anh Hải'), ['date' => '2026-11-12']),
        ];
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $report = new \App\Services\CustomerDebtReportService;
        $collections = new \App\Services\DebtCollectionService;
        $data = $controller->index($this->request(['scope' => 'orders', 'from' => '2026-11-01', 'to' => '2026-11-13'], 'admin'), $this->service($entries), $report, $collections)->getData();
        self::assertSame(1, $data['rows']->total());
        self::assertSame(2, $data['rows']->first()['customer_id']);
        $all = $controller->index($this->request(['scope' => 'all'], 'admin'), $this->service($entries), $report, $collections)->getData();
        self::assertSame(2, $all['rows']->total());
        self::assertSame(615, $all['rows']->firstWhere('customer_id', 1)['days_without_purchase']);
    }

    public function test_debt_report_order_filter_ignores_zero_value_sales(): void
    {
        $this->collectionClock('2026-11-13 10:00:00');
        $entries = [
            array_replace($this->entry('OLD01', 'Khách nguồn cũ'), ['date' => '2025-03-08']),
            array_replace($this->entry('OLD01', 'Khách nguồn cũ', 2), ['date' => '2026-11-12', 'amount' => 0]),
        ];
        $data = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request(['scope' => 'orders', 'from' => '2026-11-01', 'to' => '2026-11-13'], 'admin'), $this->service($entries), new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(0, $data['rows']->total());
    }

    public function test_debt_report_order_filter_matches_any_positive_purchase_in_the_selected_period(): void
    {
        $this->collectionClock('2026-11-13 10:00:00');
        $entries = [
            array_replace($this->entry('OLD01', 'Khách nguồn'), ['date' => '2026-11-05']),
            array_replace($this->entry('OLD01', 'Khách nguồn', 2), ['date' => '2026-11-20']),
        ];
        $data = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request(['scope' => 'orders', 'from' => '2026-11-01', 'to' => '2026-11-13'], 'admin'), $this->service($entries), new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(1, $data['rows']->total());
        self::assertSame(2000, $data['rows']->first()['balance']);
    }

    public function test_debt_report_all_balance_includes_system_customers_without_sheet_data(): void
    {
        $data = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request(['balance' => 'all'], 'admin'), $this->service([$this->entry('CRM01', 'Chị Bình')]), new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(2, $data['rows']->total());
        self::assertFalse($data['rows']->firstWhere('customer_id', 2)['data_available']);
    }

    public function test_collection_reminder_is_audited_and_idempotent(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $row = $this->collectionRow([['date' => '2026-11-10', 'amount' => 500]]);
        $data = $this->reminderData();
        $id = $service->remind($row, ['loaded_at' => '2026-11-12T09:00:00+07:00'], $data, $this->user('admin'));
        self::assertSame($id, $service->remind($row, [], $data, $this->user('admin')));
        self::assertSame(1, DB::table('debt_collection_reminders')->count());
        $record = DB::table('debt_collection_reminders')->first();
        self::assertEquals(9500, $record->balance_at_reminder);
        self::assertEquals(7, $record->created_by);
        self::assertSame(0, $service->evaluate($row, $service->records())['latest']['paid']);
    }

    public function test_collection_evaluation_counts_new_payments_and_checks_the_promised_deadline(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $service->remind($this->collectionRow(), ['loaded_at' => '2026-11-12'], $this->reminderData(['promised_date' => '2026-11-14']), $this->user('admin'));
        $this->collectionClock('2026-11-15 10:00:00');
        $row = $this->collectionRow([['date' => '2026-11-13', 'amount' => 1000]]);
        $result = $service->evaluate($row, $service->records());
        self::assertSame('met_on_time', $result['status']);
        self::assertSame(1000, $result['latest']['paid']);
        $row = $this->collectionRow([['date' => '2026-11-15', 'amount' => 1000]]);
        self::assertSame('met_late', $service->evaluate($row, $service->records())['status']);
    }

    public function test_same_day_receipts_require_confirmation_and_are_not_counted_twice(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $actor = $this->user('admin');
        $id = $service->remind($this->collectionRow(), ['loaded_at' => '2026-11-12'], $this->reminderData(), $actor);
        $row = $this->collectionRow([['date' => '2026-11-12', 'amount' => 1000]]);
        $result = $service->evaluate($row, $service->records());
        self::assertSame(0, $result['latest']['paid']);
        self::assertSame('needs_review', $result['status']);
        self::assertCount(1, $result['latest']['pending']);
        $data = ['request_id' => (string) \Illuminate\Support\Str::uuid(), 'reminder_id' => $id, 'type' => 'payment_verified', 'receipt_token' => $result['latest']['pending'][0]['token'], 'note' => 'Đã đối chiếu thu sau nhắc'];
        $event = $service->respond($row, $data, $actor);
        self::assertSame($event, $service->respond($row, $data, $actor));
        $result = $service->evaluate($row, $service->records());
        self::assertSame(1000, $result['latest']['paid']);
        self::assertSame('met', $result['status']);
        self::assertCount(0, $result['latest']['pending']);
    }

    public function test_collection_payments_are_attributed_to_only_one_reminder(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $actor = $this->user('admin');
        $first = $service->remind($this->collectionRow(), ['loaded_at' => '2026-11-12'], $this->reminderData(), $actor);
        $this->collectionClock('2026-11-13 10:00:00');
        $row = $this->collectionRow([['date' => '2026-11-13', 'amount' => 200]]);
        $second = $service->remind($row, ['loaded_at' => '2026-11-13'], $this->reminderData(), $actor);
        $this->collectionClock('2026-11-14 10:00:00');
        $row = $this->collectionRow([['date' => '2026-11-13', 'amount' => 200], ['date' => '2026-11-14', 'amount' => 300]]);
        $result = $service->evaluate($row, $service->records());
        self::assertSame(500, $result['received_total']);
        $logs = collect($result['logs'])->keyBy(fn ($log) => $log['record']->id);
        self::assertSame(200, $logs[$first]['paid']);
        self::assertSame(300, $logs[$second]['paid']);
    }

    public function test_collection_response_updates_promise_and_records_customer_feedback(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $actor = $this->user('admin');
        $id = $service->remind($this->collectionRow(), ['loaded_at' => '2026-11-12'], $this->reminderData(['promised_date' => '2026-11-12', 'follow_up_date' => '2026-11-13']), $actor);
        $service->respond($this->collectionRow(), ['request_id' => (string) \Illuminate\Support\Str::uuid(), 'reminder_id' => $id, 'type' => 'response', 'note' => 'Khách hứa trả 500 ngày mai', 'promised_amount' => 500, 'promised_date' => '2026-11-13'], $actor);
        $this->collectionClock('2026-11-14 10:00:00');
        $row = $this->collectionRow([['date' => '2026-11-13', 'amount' => 500]]);
        $result = $service->evaluate($row, $service->records());
        self::assertSame('met_on_time', $result['status']);
        self::assertSame(500, $result['latest']['target']);
        self::assertFalse($result['latest']['follow_up_due']);
        self::assertCount(1, $result['latest']['events']);
    }

    public function test_collection_does_not_treat_adjustments_as_payments_and_detects_missed_promises(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $service->remind($this->collectionRow(), ['loaded_at' => '2026-11-12'], $this->reminderData(['promised_date' => '2026-11-13', 'follow_up_date' => '2026-11-14']), $this->user('admin'));
        $this->collectionClock('2026-11-15 10:00:00');
        $row = $this->collectionRow();
        $row['entries'][] = ['type' => 'adjustment', 'date' => '2026-11-14', 'amount' => -5000, 'row' => 1];
        $result = $service->evaluate($row, $service->records());
        self::assertSame(0, $result['latest']['paid']);
        self::assertSame('overdue', $result['status']);
        self::assertTrue($result['latest']['follow_up_due']);
    }

    public function test_unmapped_collection_history_survives_customer_mapping(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $row = $this->collectionRow();
        $row['customer_id'] = null;
        $service->remind($row, ['loaded_at' => '2026-11-12'], $this->reminderData(), $this->user('admin'));
        $row['customer_id'] = 1;
        self::assertCount(1, $service->evaluate($row, $service->records())['logs']);
        $row['source_keys'] = [str_repeat('a', 64)];
        self::assertCount(0, $service->evaluate($row, $service->records())['logs']);
    }

    public function test_collection_edited_existing_receipt_is_not_automatically_treated_as_new_money(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $actor = $this->user('admin');
        $service->remind($this->collectionRow([['date' => '2026-11-01', 'amount' => 500]]), ['loaded_at' => '2026-11-12'], $this->reminderData(), $actor);
        $this->collectionClock('2026-11-13 10:00:00');
        $row = $this->collectionRow([['date' => '2026-11-13', 'amount' => 600]]);
        $result = $service->evaluate($row, $service->records());
        self::assertSame(0, $result['latest']['paid']);
        self::assertCount(1, $result['latest']['pending']);
        self::assertSame('needs_review', $result['status']);
    }

    public function test_collection_reminder_endpoint_resolves_current_customer_and_rejects_excess_target(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $sheets = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = hash('sha256', 'code:CRM01');
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $service = new \App\Services\DebtCollectionService;
        try {
            $controller->remind($this->request($this->reminderData(['source_key' => $key, 'target_amount' => 2000]), 'admin'), $sheets, $service);
            self::fail('Excess target accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            self::assertArrayHasKey('target_amount', $e->errors());
        }
        self::assertSame(0, DB::table('debt_collection_reminders')->count());
        $response = $controller->remind($this->request($this->reminderData(['source_key' => $key, 'target_amount' => '500']), 'admin'), $sheets, $service)->getData(true);
        self::assertGreaterThan(0, $response['id']);
        self::assertEquals(1, DB::table('debt_collection_reminders')->value('customer_id'));
    }

    public function test_collection_responses_cannot_be_attached_to_another_customer(): void
    {
        $this->collectionClock('2026-11-12 10:00:00');
        $service = new \App\Services\DebtCollectionService;
        $actor = $this->user('admin');
        $id = $service->remind($this->collectionRow(), ['loaded_at' => '2026-11-12'], $this->reminderData(), $actor);
        $other = $this->collectionRow();
        $other['customer_id'] = 2;
        $this->expectException(HttpException::class);
        $service->respond($other, ['request_id' => (string) \Illuminate\Support\Str::uuid(), 'reminder_id' => $id, 'type' => 'response', 'note' => 'Phản hồi nhầm khách'], $actor);
    }

    public function test_debt_report_pagination_search_and_recovery_filter(): void
    {
        $entries = [];
        for ($i = 1; $i <= 41; $i++) {
            $entries[] = $this->entry('NEW'.$i, 'Khách nguồn '.$i, $i);
        }
        $sheets = $this->service($entries);
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $report = new \App\Services\CustomerDebtReportService;
        $collection = new \App\Services\DebtCollectionService;
        $data = $controller->index($this->request(['per_page' => 20, 'page' => 2], 'admin'), $sheets, $report, $collection)->getData();
        self::assertSame(41, $data['rows']->total());
        self::assertSame(21, $data['rows']->firstItem());
        self::assertCount(20, $data['rows']);
        $key = collect($sheets->summarize($entries))->firstWhere('code', 'NEW20')['key'];
        $this->markRecovery($sheets, $key, 'difficult');
        $data = $controller->index($this->request(['search' => 'Khách nguồn 20'], 'admin'), $sheets, $report, $collection)->getData();
        self::assertSame(0, $data['rows']->total());
        $data = $controller->index($this->request(['search' => 'Khách nguồn 20', 'recovery' => 'all'], 'admin'), $sheets, $report, $collection)->getData();
        self::assertSame(1, $data['rows']->total());
    }

    private function systemAccountingFixture(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->string('code')->nullable();
            $t->string('status')->default('completed');
            $t->bigInteger('amount_paid')->default(0);
            $t->bigInteger('collected_amount')->default(0);
        });
        Schema::create('accounting_reconciliations', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->string('status');
            $t->bigInteger('recognized_revenue');
            $t->bigInteger('paid_amount')->default(0);
            $t->timestamp('confirmed_at')->nullable();
        });
        $this->tables[] = 'accounting_reconciliations';
        Schema::create('transactions', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->unsignedBigInteger('order_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('type');
            $t->bigInteger('amount');
            $t->string('status')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });
        $this->tables[] = 'transactions';
        DB::table('orders')->where('id', 1)->update(['delivery_date' => '2026-11-13', 'created_at' => '2026-11-01 08:00:00', 'code' => 'SYS01']);
        DB::table('accounting_reconciliations')->insert(['order_id' => 1, 'status' => 'confirmed', 'recognized_revenue' => 1000, 'paid_amount' => 0, 'confirmed_at' => '2026-11-13 08:00:00']);
    }

    public function test_system_report_uses_system_orders_instead_of_sheet_purchases(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $this->systemAccountingFixture();
        $sheets = $this->service([array_replace($this->entry('CRM01', 'Chị Bình'), ['date' => '2025-03-08', 'amount' => 9000])]);
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $report = new \App\Services\CustomerDebtReportService;
        $collections = new \App\Services\DebtCollectionService;
        $data = $controller->index($this->request(['report_source' => 'system', 'scope' => 'orders', 'from' => '2026-11-01', 'to' => '2026-11-13'], 'admin'), $sheets, $report, $collections)->getData();
        self::assertSame('system', $data['reportSource']);
        self::assertSame(1, $data['rows']->total());
        self::assertSame(1000, $data['rows']->first()['sales']);
        self::assertSame(0, $data['rows']->first()['days_without_purchase']);
        $data = $controller->index($this->request(['report_source' => 'sheet', 'scope' => 'orders', 'from' => '2026-11-01', 'to' => '2026-11-13'], 'admin'), $sheets, $report, $collections)->getData();
        self::assertSame(0, $data['rows']->total());
    }

    public function test_system_report_ignores_unconfirmed_orders_and_unapproved_payments(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $this->systemAccountingFixture();
        DB::table('accounting_reconciliations')->insert(['order_id' => 2, 'status' => 'pending', 'recognized_revenue' => 9000, 'paid_amount' => 0]);
        DB::table('transactions')->insert([
            ['order_id' => 1, 'customer_id' => 1, 'type' => 'payment', 'amount' => 100, 'status' => 'approved', 'created_at' => '2026-11-13 09:00:00'],
            ['order_id' => 1, 'customer_id' => 1, 'type' => 'payment', 'amount' => 500, 'status' => 'pending_approval', 'created_at' => '2026-11-13 09:00:00'],
            ['order_id' => 1, 'customer_id' => 1, 'type' => 'refund', 'amount' => 20, 'status' => 'approved', 'created_at' => '2026-11-13 10:00:00'],
            ['order_id' => null, 'customer_id' => 1, 'type' => 'customer_debt_adjustment', 'amount' => 50, 'status' => 'approved', 'created_at' => '2026-11-13 10:00:00'],
            ['order_id' => 1, 'customer_id' => 1, 'type' => 'return_credit', 'amount' => 300, 'status' => 'approved', 'created_at' => '2026-11-13 10:00:00'],
        ]);
        $rows = (new \App\Services\SystemCustomerDebtReportService)->rows();
        $row = $rows->firstWhere('customer_id', 1);
        self::assertSame(1000, $row['sales']);
        self::assertSame(80, $row['payments']);
        self::assertSame(970, $row['balance']);
        self::assertSame(0, $rows->firstWhere('customer_id', 2)['sales']);
    }

    public function test_system_report_does_not_double_add_cash_totals_and_payment_transactions(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $this->systemAccountingFixture();
        DB::table('orders')->where('id', 1)->update(['amount_paid' => 300, 'collected_amount' => 200]);
        DB::table('accounting_reconciliations')->where('order_id', 1)->update(['paid_amount' => 250]);
        DB::table('transactions')->insert(['order_id' => 1, 'customer_id' => 1, 'type' => 'payment', 'amount' => 300, 'status' => 'approved', 'created_at' => '2026-11-13 09:00:00']);
        $row = (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1);
        self::assertSame(300, $row['payments']);
        self::assertSame(700, $row['balance']);
        self::assertFalse($row['payment_date_unknown']);
        DB::table('transactions')->update(['amount' => 100]);
        $row = (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1);
        self::assertSame(300, $row['payments']);
        self::assertTrue($row['payment_date_unknown']);
        self::assertNull($row['days_without_payment']);
    }

    public function test_collection_histories_are_isolated_by_source_and_system_receipts_use_exact_time(): void
    {
        $this->collectionClock('2026-11-13 10:00:00');
        $this->systemAccountingFixture();
        $collections = new \App\Services\DebtCollectionService;
        $actor = $this->user('admin');
        $row = (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1);
        $collections->remind($row, ['loaded_at' => now()->toIso8601String()], $this->reminderData(['target_amount' => 100]), $actor);
        $collections->remind($this->collectionRow(), ['loaded_at' => now()->toIso8601String()], $this->reminderData(['target_amount' => 100]), $actor);
        self::assertCount(1, $collections->records('system')['reminders']);
        self::assertCount(1, $collections->records('sheet')['reminders']);
        $this->collectionClock('2026-11-13 12:00:00');
        DB::table('transactions')->insert(['order_id' => 1, 'customer_id' => 1, 'type' => 'payment', 'amount' => 100, 'status' => 'approved', 'created_at' => '2026-11-13 11:00:00']);
        $row = (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1);
        $outcome = $collections->evaluate($row, $collections->records('system'));
        self::assertSame(100, $outcome['latest']['paid']);
        self::assertSame('met', $outcome['status']);
        self::assertCount(0, $collections->evaluate($row, $collections->records('sheet'))['logs']);
    }

    public function test_unitemized_system_cash_does_not_automatically_score_as_new_collection(): void
    {
        $this->collectionClock('2026-11-13 10:00:00');
        $this->systemAccountingFixture();
        DB::table('orders')->where('id', 1)->update(['collected_amount' => 200]);
        $collections = new \App\Services\DebtCollectionService;
        $row = (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1);
        $collections->remind($row, ['loaded_at' => now()->toIso8601String()], $this->reminderData(['target_amount' => 100]), $this->user('admin'));
        $this->collectionClock('2026-11-13 12:00:00');
        DB::table('orders')->where('id', 1)->update(['collected_amount' => 300]);
        $row = (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1);
        self::assertSame(300, $row['payments']);
        self::assertSame(0, $collections->evaluate($row, $collections->records('system'))['latest']['paid']);
    }

    public function test_sheet_refresh_combines_two_payment_tabs_without_accumulating_reloads(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $service = new CustomerDebtSheetService;
        $sales = [['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['13/11/2026', 'CRM01', 'Chị Bình', '1000']];
        $payments = [['Ngày', 'Mã KH', 'Khách hàng', 'Tiền thu'], ['13/11/2026', 'CRM01', 'Chị Bình', '200']];
        $bank = [['Ngày tháng', 'Code', 'Tên KH', 'Thu', 'Chi'], ['13/11/2026', 'TTBH', 'Chị Bình', '300', '']];
        $service->save($sales, $payments, 7, 'google', $bank);
        $snapshot = $service->snapshot();
        $groups = $service->summarize($snapshot['entries']);
        self::assertSame(500, array_values($groups)[0]['balance']);
        self::assertSame(['nhatkybanhang', 'Dongtienhoatdong', 'TKCNMBBDUE'], $snapshot['payment_tabs']);
        $service->save($sales, $payments, 7, 'google', $bank);
        self::assertCount(3, $service->snapshot()['entries']);
        self::assertSame(500, array_values($service->summarize($service->snapshot()['entries']))[0]['payments']);
        $snapshot = $service->snapshot();
        try {
            $service->save($sales, $payments, 7, 'google', [['Sai tiêu đề']]);
            self::fail('Bad bank data accepted');
        } catch (\RuntimeException) {
            self::assertSame($snapshot, $service->snapshot());
        }
    }

    public function test_debt_report_and_payment_list_reconcile_both_tabs_after_each_refresh(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->collectionClock('2026-11-13 12:00:00');
        $service = new CustomerDebtSheetService;
        $sales = [['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['13/11/2026', 'CRM01', 'Chị Bình', '1000']];
        $cash = [['Ngày', 'Mã KH', 'Khách hàng', 'Tiền thu'], ['12/11/2026', 'CRM01', 'Chị Bình', '200']];
        $bank = [['Ngày tháng', 'Code', 'Tên KH', 'Thu', 'Chi'], ['13/11/2026', 'TTBH', 'Chị Bình', '300', ''], ['13/11/2026', 'TTBH', '', '50', '']];
        foreach ([300, 400, 400] as $bankAmount) {
            $bank[1][3] = (string) $bankAmount;
            $service->save($sales, $cash, 7, 'google', $bank);
            $report = (new \App\Http\Controllers\CustomerDebtReportController)->index(
                $this->request(['report_source' => 'sheet', 'scope' => 'orders', 'from' => '2026-11-13', 'to' => '2026-11-13'], 'admin'),
                $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService
            )->getData();
            $payments = (new \App\Http\Controllers\CustomerPaymentController)->index(
                $this->request(['customer_id' => 1], 'admin'), $service, new \App\Services\CustomerPaymentReportService
            )->getData();
            self::assertSame(200 + $bankAmount, $report['rows']->first()['payments']);
            self::assertSame($payments['summary']['amount'], $report['rows']->first()['payments']);
            self::assertSame($payments['summary']['amount'], $report['summary']['payments']);
            self::assertSame(800 - $bankAmount, $report['rows']->first()['balance']);
            self::assertSame('2026-11-13', $report['rows']->first()['last_payment_date']);
            self::assertSame(2, $payments['rows']->total());
            self::assertSame($payments['snapshot']['loaded_at'], $report['snapshot']['loaded_at']);
        }
    }

    public function test_all_five_payment_tabs_reconcile_on_all_three_pages_and_repeat_refresh(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->collectionClock('2026-11-13 12:00:00');
        $tabs = [
            [['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['13/11/2026', 'CRM01', 'Chị Bình', '1000']],
            [['Ngày', 'Mã KH', 'Khách hàng', 'Tiền thu'], ['12/11/2026', 'CRM01', 'Chị Bình', '100']],
        ];
        foreach (array_slice(CustomerDebtSheetService::PAYMENT_TABS, 1) as $tab) {
            $tabs[] = [['Ngày tháng', 'Code', 'Tên KH', 'Thu', 'Chi'],
                ['13/11/2026', 'TTBH', 'Chị Bình', '100', ''],
                ['13/11/2026', 'TTM', 'Chị Bình', '9000', ''],
                ['13/11/2026', 'CPK', '', '', '10'],
                ['13/11/2026', 'TTBH', '', '50', '']];
        }
        $service = new class($tabs) extends CustomerDebtSheetService
        {
            public function __construct(private array $tabs) {}

            public function fetch(): array
            {
                return $this->tabs;
            }
        };
        // Both update entry points must persist the same complete snapshot.
        (new CustomerDebtMonitorController)->load($this->request(['source' => 'google'], 'admin'), $service);
        $first = $service->snapshot()['payment_syncs'][0];
        self::assertSame(9, $first['new_count']);
        (new \App\Http\Controllers\CustomerPaymentController)->refresh($this->request([], 'admin'), $service);
        self::assertSame(0, $service->snapshot()['payment_syncs'][0]['new_count']);
        self::assertSame(CustomerDebtSheetService::SETTLEMENT_TABS, $service->snapshot()['payment_tabs']);
        $monitor = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        self::assertSame(500, $monitor['customerDebts'][1]['payments']);
        self::assertSame(500, $monitor['customerDebts'][1]['balance']);
        $report = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request(['report_source' => 'sheet'], 'admin'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(500, $report['rows']->first()['payments']);
        $payments = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(['customer_id' => 1], 'admin'), $service, new \App\Services\CustomerPaymentReportService)->getData();
        self::assertSame(500, $payments['summary']['amount']);
        self::assertSame(5, $payments['rows']->total());
        foreach (CustomerDebtSheetService::PAYMENT_TABS as $tab) {
            $filtered = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(['customer_id' => 1, 'tab' => $tab], 'admin'), $service, new \App\Services\CustomerPaymentReportService)->getData();
            self::assertSame(100, $filtered['summary']['amount']);
            self::assertSame($tab, $filtered['rows']->first()['source_sheet']);
        }
        $snapshot = $service->snapshot();
        $tabs[5] = [['Sai tiêu đề']];
        try {
            $service->saveTabs($tabs, 7, 'google');
            self::fail('Malformed fifth payment tab replaced the snapshot');
        } catch (\RuntimeException) {
            self::assertSame($snapshot, $service->snapshot());
        }
    }

    public function test_inline_sales_payments_and_gifts_reconcile_without_double_counting_and_are_audited_separately(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->collectionClock('2026-11-13 12:00:00');
        $service = new CustomerDebtSheetService;
        $sales = [['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền', 'Thanh toán', 'Tặng'],
            ['13/11/2026', 'CRM01', 'Chị Bình', '1000', '200', '100'],
            ['', '', '', '', '300', '50']];
        $cash = [['Ngày', 'Mã KH', 'Khách hàng', 'Tiền thu'], ['13/11/2026', 'CRM01', 'Chị Bình', '200']];
        $service->save($sales, $cash, 7, 'google');
        $snapshot = $service->snapshot();
        $key = array_key_first($service->summarize($snapshot['entries']));
        $data = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key, 'window' => 'all'], 'admin'), $service)->getData();
        self::assertSame(1000, $data['detailSales']);
        self::assertSame(700, $data['detailPayments']);
        self::assertSame(150, $data['detailGifts']);
        self::assertSame(150, $data['closing']);
        self::assertSame(3, $data['payments']->total());
        $page = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(['customer_id' => 1], 'admin'), $service, new \App\Services\CustomerPaymentReportService)->getData();
        self::assertSame(700, $page['summary']['amount']);
        self::assertSame(150, $page['summary']['gifts']);
        self::assertSame(700, $page['summary']['today']);
        self::assertFalse($page['rows']->where('type', 'gift')->contains(fn ($r) => $r['is_recent']));
        $report = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request(['report_source' => 'sheet'], 'admin'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(700, $report['rows']->first()['payments']);
        self::assertSame(150, $report['rows']->first()['balance']);
        $again = $service->save($sales, $cash, 7, 'google');
        self::assertSame(0, $again['new_count']);
        self::assertSame(0, $again['updated_count']);
        $sales[1][5] = '120';
        $giftEdit = $service->save($sales, $cash, 7, 'google');
        self::assertSame(1, $giftEdit['updated_count']);
        self::assertSame(0, $giftEdit['delta']);
        self::assertSame(20, $giftEdit['gift_delta']);
        $sales[1][4] = '250';
        $cashEdit = $service->save($sales, $cash, 7, 'google');
        self::assertSame(1, $cashEdit['updated_count']);
        self::assertSame(50, $cashEdit['delta']);
        self::assertSame(0, $cashEdit['gift_delta']);
        $sales[1][5] = '';
        $giftRemoved = $service->save($sales, $cash, 7, 'google');
        self::assertSame(1, $giftRemoved['removed_count']);
        self::assertSame(-120, $giftRemoved['gift_delta']);
        self::assertSame(0, $giftRemoved['delta']);
        self::assertSame(750, array_values($service->summarize($service->snapshot()['entries']))[0]['payments']);
        $saved = $service->snapshot();
        $sales[1][4] = 'không hợp lệ';
        try {
            $service->save($sales, $cash, 7, 'google');
            self::fail('Malformed inline payment replaced the financial snapshot');
        } catch (\RuntimeException) {
            self::assertSame($saved, $service->snapshot());
        }
    }

    public function test_payment_tokens_distinguish_bank_tabs_and_preserve_existing_cash_flow_baselines(): void
    {
        $collections = new \App\Services\DebtCollectionService;
        $row = $this->collectionRow([['date' => now()->toDateString(), 'amount' => 200]]);
        $original = array_keys($collections->receipts($row));
        $row['entries'][1]['source_sheet'] = 'Dongtienhoatdong';
        self::assertSame($original, array_keys($collections->receipts($row)));
        $row['entries'][] = array_replace($row['entries'][1], ['source_sheet' => 'TKCNMBBDUE']);
        $receipts = $collections->receipts($row);
        self::assertCount(2, $receipts);
        self::assertSame(400, collect($receipts)->sum('amount'));
    }

    public function test_google_refresh_endpoint_passes_both_payment_tabs_to_snapshot_save(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $tabs = [
            [['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['13/11/2026', 'CRM01', 'Chị Bình', '1000']],
            [['Ngày', 'Khách hàng', 'Tiền thu'], ['13/11/2026', 'Chị Bình', '200']],
            [['Ngày tháng', 'Code', 'Tên KH', 'Thu', 'Chi'], ['13/11/2026', 'TTBH', 'Chị Bình', '300', '']],
        ];
        $service = new class($tabs) extends CustomerDebtSheetService
        {
            public function __construct(private array $tabs) {}

            public function fetch(): array
            {
                return $this->tabs;
            }
        };
        (new CustomerDebtMonitorController)->load($this->request(['source' => 'google'], 'admin'), $service);
        self::assertSame(500, array_values($service->summarize($service->snapshot()['entries']))[0]['payments']);
        self::assertContains('TKCNMBBDUE', $service->snapshot()['payment_tabs']);
    }

    public function test_system_report_recovery_tool_updates_customer_without_sheet_source_key(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $this->systemAccountingFixture();
        $row = (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1);
        (new \App\Http\Controllers\CustomerDebtReportController)->updateRecovery($this->request(['report_source' => 'system', 'customer_id' => 1, 'source_key' => $row['key'], 'recovery_status' => 'difficult', 'recovery_note' => 'Cần nhắc nhiều lần', 'recovery_version' => $row['recovery']['version']], 'admin'), $this->service([]), new \App\Services\CustomerDebtRecoveryService);
        self::assertSame('difficult', (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1)['recovery']['status']);
    }

    public function test_system_response_endpoint_rejects_a_sheet_reminder_id(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $this->systemAccountingFixture();
        $collections = new \App\Services\DebtCollectionService;
        $id = $collections->remind($this->collectionRow(), ['loaded_at' => now()->toIso8601String()], $this->reminderData(), $this->user('admin'));
        $row = (new \App\Services\SystemCustomerDebtReportService)->rows()->firstWhere('customer_id', 1);
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\CustomerDebtReportController)->respond($this->request(['report_source' => 'system', 'customer_id' => 1, 'source_key' => $row['key'], 'request_id' => (string) \Illuminate\Support\Str::uuid(), 'reminder_id' => $id, 'type' => 'response', 'note' => 'Phản hồi thuộc nguồn khác'], 'admin'), $this->service([]), $collections);
    }

    private function paymentTables(int $amount = 200, string $date = '13/11/2026'): array
    {
        return [[['Ngày', 'Mã KH', 'Khách hàng', 'NVKD', 'Tổng tiền'], ['13/11/2026', 'CRM01', 'Chị Bình', 'Huy', 1000]], [['Ngày', 'Mã KH', 'Tên KH/NCC', 'NVKD', 'Tiền thu'], [$date, 'CRM01', 'Chị Bình', 'Huy', $amount]]];
    }

    public function test_every_payment_refresh_retains_a_committed_change_list_and_unchanged_refresh_has_no_new_rows(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $service = new CustomerDebtSheetService;
        [$sales,$payments] = $this->paymentTables();
        $service->save($sales, $payments, 7, 'google');
        $first = $service->snapshot();
        $firstId = $first['payment_syncs'][0]['id'];
        self::assertSame(1, $first['payment_syncs'][0]['new_count']);
        $service->save($sales, $payments, 7, 'google');
        $second = $service->snapshot();
        self::assertCount(2, $second['payment_syncs']);
        self::assertSame(0, $second['payment_syncs'][0]['new_count']);
        self::assertCount(0, $service->paymentBatch($second['payment_syncs'][0]['id'])['changes']);
        self::assertCount(1, $service->paymentBatch($firstId)['changes']);
        self::assertSame(200, $service->paymentBatch($firstId)['changes'][0]['after']['amount']);
        [$sales,$payments] = $this->paymentTables(300);
        $service->save($sales, $payments, 7, 'google');
        $latest = $service->snapshot()['payment_syncs'][0];
        self::assertSame(1, $latest['updated_count']);
        self::assertSame(100, $latest['delta']);
        self::assertCount(1, $service->paymentBatch($firstId)['changes']);
    }

    public function test_payment_page_filters_by_sale_customer_dates_and_sums_all_pages(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $entries = [$this->entry('CRM01', 'Chị Bình')];
        for ($i = 1; $i <= 25; $i++) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $i), ['type' => 'payment', 'amount' => 100, 'date' => '2026-11-13', 'sale_name' => 'Huy', 'source_sheet' => 'Dongtienhoatdong']);
        }
        $entries[] = array_replace($this->entry('KH02', 'Anh Hải', 100), ['type' => 'payment', 'amount' => 500, 'date' => '2026-11-13', 'sale_name' => 'Hà', 'source_sheet' => 'TKCNMBBDUE']);
        $data = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(['sale' => 'user:7', 'customer_id' => 1, 'from' => '2026-11-13', 'to' => '2026-11-13', 'per_page' => 20], 'admin'), $this->service($entries), new \App\Services\CustomerPaymentReportService)->getData();
        self::assertSame(25, $data['rows']->total());
        self::assertCount(20, $data['rows']);
        self::assertSame(2500, $data['summary']['amount']);
        self::assertSame(2500, $data['summary']['today']);
        self::assertTrue($data['rows']->every(fn ($row) => $row['customer_id'] === 1 && $row['sale_key'] === 'user:7'));
    }

    public function test_new_payment_badge_depends_on_today_not_the_import_date(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $entries = [];
        foreach (['2026-11-12', '2026-11-13', '2026-11-14'] as $i => $date) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $i + 1), ['type' => 'payment', 'date' => $date, 'amount' => 100, 'first_seen_at' => '2026-11-13T10:00:00+07:00']);
        }
        $data = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(['recent' => 'today'], 'admin'), $this->service($entries), new \App\Services\CustomerPaymentReportService)->getData();
        self::assertSame(1, $data['rows']->total());
        self::assertSame('2026-11-13', $data['rows']->first()['date']);
        self::assertSame(100, $data['summary']['amount']);
    }

    public function test_unknown_payer_remains_in_payment_list_with_a_separate_unallocated_total(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $service = new class extends CustomerDebtSheetService
        {
            public function snapshot(): ?array
            {
                return ['entries' => [], 'issues' => [['type' => 'payment', 'date' => '13/11/2026', 'amount' => 500, 'row' => 3, 'note' => 'Chưa rõ người trả', 'sale_name' => 'Huy', 'source_sheet' => 'TKCNMBBDUE']]];
            }
        };
        $data = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(['sale' => 'user:7', 'unidentified' => '1'], 'admin'), $service, new \App\Services\CustomerPaymentReportService)->getData();
        self::assertSame(1, $data['rows']->total());
        self::assertNull($data['rows']->first()['customer_id']);
        self::assertTrue($data['rows']->first()['unidentified']);
        self::assertSame(500, $data['summary']['unidentified']);
        self::assertTrue($data['rows']->first()['is_recent']);
    }

    public function test_payment_page_preserves_ambiguous_customer_mapping(): void
    {
        $entries = [$this->entry('OLD1', 'Chị Bình'), $this->entry('OLD2', 'Chị Bình'), array_replace($this->entry('', 'Chị Bình', 3), ['type' => 'payment', 'amount' => 100])];
        $data = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request([], 'admin'), $this->service($entries), new \App\Services\CustomerPaymentReportService)->getData();
        self::assertSame(1, $data['rows']->total());
        self::assertNull($data['rows']->first()['customer_id']);
    }

    public function test_sales_cannot_read_all_payment_records_or_refresh_them(): void
    {
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(), $this->service([]), new \App\Services\CustomerPaymentReportService);
    }

    public function test_payment_refresh_endpoint_opens_the_new_receipt_list_for_its_batch(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $tabs = $this->paymentTables();
        $service = new class($tabs) extends CustomerDebtSheetService
        {
            public function __construct(private array $tabs) {}

            public function fetch(): array
            {
                return $this->tabs;
            }
        };
        $response = (new \App\Http\Controllers\CustomerPaymentController)->refresh($this->request([], 'admin'), $service);
        $id = $service->snapshot()['payment_syncs'][0]['id'];
        self::assertStringContainsString('batch='.$id, $response->getTargetUrl());
        self::assertStringContainsString('change=new', $response->getTargetUrl());
        $data = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(['batch' => $id, 'change' => 'new'], 'admin'), $service, new \App\Services\CustomerPaymentReportService)->getData();
        self::assertSame(1, $data['rows']->total());
        self::assertSame(1, $data['rows']->first()['customer_id']);
    }

    public function test_sales_can_only_see_their_customers_and_unique_name_fallback(): void
    {
        $view = (new CustomerDebtMonitorController)->index($this->request(), $this->service([$this->entry('OLD01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải')]));
        $data = $view->getData();
        self::assertCount(1, $data['groups']);
        self::assertSame(1, array_values($data['groups'])[0]['customer_id']);
        self::assertSame('name', array_values($data['groups'])[0]['matched_by']);
        self::assertSame(1, $data['orders']->total());
    }

    public function test_sales_cannot_open_another_customers_debt_by_key(): void
    {
        $service = $this->service([$this->entry('KH02', 'Anh Hải')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(HttpException::class);
        (new CustomerDebtMonitorController)->index($this->request(['customer' => $key]), $service);
    }

    public function test_ambiguous_source_names_are_not_mapped_to_a_sales_customer(): void
    {
        $view = (new CustomerDebtMonitorController)->index($this->request(), $this->service([$this->entry('OLD01', 'Chị Bình'), $this->entry('OLD02', 'Chị Bình')]));
        self::assertCount(0, $view->getData()['groups']);
    }

    public function test_purchase_pagination_keeps_the_full_report_balance(): void
    {
        $entries = [];
        for ($i = 1; $i <= 101; $i++) {
            $entries[] = $this->entry('KH02', 'Anh Hải', $i) + ['gift' => 10];
        }
        $service = $this->service($entries);
        $key = array_key_first($service->summarize($entries));
        $view = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key, 'window' => 'all', 'detail_page' => 2], 'admin'), $service);
        $data = $view->getData();
        self::assertSame(101, $data['purchases']->total());
        self::assertCount(1, $data['purchases']);
        self::assertSame(99990, $data['closing']);
    }

    public function test_sale_cannot_replace_the_shared_snapshot(): void
    {
        $this->expectException(HttpException::class);
        (new CustomerDebtMonitorController)->load($this->request(), $this->service([]));
    }

    public function test_reloading_replaces_snapshot_and_invalid_input_preserves_previous_data(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $service = new CustomerDebtSheetService;
        $sales = [['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['12/11/2026', 'KH01', 'Anh Bình', '1.000.000']];
        $payments = [['Ngày', 'Mã KH', 'Khách hàng', 'Thu'], ['12/11/2026', 'KH01', 'Anh Bình', '500.000']];
        $service->save($sales, $payments, 7, 'paste');
        $service->save($sales, $payments, 7, 'paste');
        $before = $service->snapshot();
        self::assertCount(2, $before['entries']);
        self::assertSame(500000, array_values($service->summarize($before['entries']))[0]['balance']);
        try {
            $service->save($sales, [['Không có tiêu đề hợp lệ']], 7, 'paste');
            self::fail('Invalid source should be rejected');
        } catch (\RuntimeException) {
            self::assertSame($before, $service->snapshot());
        }
    }

    public function test_recent_detail_subtracts_gifts_from_both_opening_and_current_period(): void
    {
        $old = $this->entry('KH02', 'Anh Hải') + ['gift' => 100];
        $old['date'] = now()->subDays(20)->toDateString();
        $current = $this->entry('KH02', 'Anh Hải', 2) + ['gift' => 200];
        $payment = $this->entry('KH02', 'Anh Hải', 3);
        $payment['type'] = 'payment';
        $payment['amount'] = 300;
        $service = $this->service([$old, $current, $payment]);
        $key = array_key_first($service->summarize([$old]));
        $data = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key, 'window' => 'recent'], 'admin'), $service)->getData();
        self::assertSame(900, $data['opening']);
        self::assertSame(200, $data['detailGifts']);
        self::assertSame(1400, $data['closing']);
    }

    public function test_debt_page_routes_are_not_shadowed_by_the_content_slug_route(): void
    {
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $routes = $this->app->make('router')->getRoutes();
        self::assertSame('customer-debt.customers', $routes->match(Request::create('/customer-debt/customers'))->getName());
        self::assertSame('customer-debt.index', $routes->match(Request::create('/customer-debt'))->getName());
        self::assertSame('customer-debt.load', $routes->match(Request::create('/customer-debt/load', 'POST'))->getName());
    }

    public function test_manual_mapping_overrides_auto_matching_and_combines_customer_alerts(): void
    {
        $service = $this->service([$this->entry('OLD01', 'Khách nguồn A'), $this->entry('KH02', 'Anh Hải')]);
        $groups = $service->summarize($service->snapshot()['entries']);
        foreach (array_keys($groups) as $key) {
            $service->mapCustomer($key, 1, 7);
        }
        $data = (new CustomerDebtMonitorController)->index($this->request(['system_customer' => 1]), $service)->getData();
        self::assertCount(2, $data['groups']);
        self::assertSame(2000, $data['customerDebts'][1]['balance']);
        self::assertSame(2000, $data['closing']);
        self::assertSame(2, $data['purchases']->total());
        self::assertSame('manual', array_values($data['groups'])[0]['matched_by']);
        self::assertSame(7, array_values($service->mappings())[0]['mapped_by']);
    }

    public function test_mapping_survives_data_reload_and_can_be_removed(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $service = new CustomerDebtSheetService;
        $sales = [['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['12/11/2026', 'OLD01', 'Khách nguồn A', '1.000.000']];
        $payments = [['Ngày', 'Mã KH', 'Khách hàng', 'Thu'], ['12/11/2026', 'OLD01', 'Khách nguồn A', '500.000']];
        $service->save($sales, $payments, 7, 'paste');
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $service->mapCustomer($key, 1, 7);
        $service->save($sales, $payments, 7, 'paste');
        self::assertSame(1, $service->mappings()[$key]['customer_id']);
        $service->unmapCustomer($key);
        self::assertSame([], $service->mappings());
    }

    public function test_deleted_manual_target_does_not_fall_back_to_another_customer(): void
    {
        $service = $this->service([$this->entry('KH02', 'Anh Hải')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $service->mapCustomer($key, 1, 7);
        DB::table('customers')->where('id', 1)->update(['deleted_at' => now()]);
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        self::assertNull($data['groups'][$key]['customer_id']);
        self::assertTrue($data['groups'][$key]['manual_mapping']);
    }

    public function test_sale_cannot_change_shared_customer_mappings(): void
    {
        $this->expectException(HttpException::class);
        (new CustomerDebtMonitorController)->map($this->request(), $this->service([]));
    }

    public function test_mapping_rejects_a_nonexistent_system_customer(): void
    {
        $request = $this->request(['source_key' => str_repeat('a', 64), 'customer_id' => 999], 'admin');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new CustomerDebtMonitorController)->map($request, $this->service([]));
    }

    public function test_mapping_rejects_a_source_not_in_current_data(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service([])->mapCustomer(str_repeat('a', 64), 1, 7);
    }

    public function test_popup_customer_list_is_paginated_and_excludes_deleted_customers(): void
    {
        for ($id = 3; $id <= 25; $id++) {
            DB::table('customers')->insert(['id' => $id, 'name' => 'Khách '.$id, 'customer_code' => 'PAGE'.$id]);
        }
        DB::table('customers')->where('id', 25)->update(['deleted_at' => now()]);
        $controller = new CustomerDebtMonitorController;
        $first = $controller->customers($this->request(['page' => 1], 'admin'))->getData(true);
        $second = $controller->customers($this->request(['page' => 2], 'admin'))->getData(true);
        self::assertCount(10, $first['customers']);
        self::assertCount(10, $second['customers']);
        self::assertSame(24, $first['pagination']['total']);
        self::assertSame(3, $first['pagination']['last_page']);
        self::assertSame(2, $second['pagination']['current_page']);
        self::assertSame([], array_intersect(array_column($first['customers'], 'id'), array_column($second['customers'], 'id')));
    }

    public function test_popup_searches_system_customer_code_name_and_phone(): void
    {
        DB::table('customers')->where('id', 1)->update(['phone' => '0901234567']);
        $controller = new CustomerDebtMonitorController;
        foreach (['CRM01', 'Chị Bình', '090123'] as $search) {
            $data = $controller->customers($this->request(['search' => $search], 'admin'))->getData(true);
            self::assertSame(1, $data['pagination']['total']);
            self::assertSame(1, $data['customers'][0]['id']);
        }
        $empty = $controller->customers($this->request(['search' => 'Không có khách này'], 'admin'))->getData(true);
        self::assertSame([], $empty['customers']);
        self::assertSame(0, $empty['pagination']['total']);
    }

    public function test_sale_cannot_browse_the_global_mapping_customer_list(): void
    {
        $this->expectException(HttpException::class);
        (new CustomerDebtMonitorController)->customers($this->request());
    }

    public function test_sale_can_quickly_update_terms_for_their_own_customer(): void
    {
        $service = $this->service([$this->entry('OLD01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $controller = new CustomerDebtMonitorController;
        $controller->updatePolicy($this->request(['source_key' => $key, 'allowed_orders' => 1, 'credit_limit' => '10.000.000', 'due_days' => 7]), $service);
        $policy = app(\App\Services\CustomerDebtPolicyService::class)->policies()['customer_1'];
        self::assertSame(1, $policy['allowed_orders']);
        self::assertSame(10000000, $policy['credit_limit']);
        self::assertSame(7, $policy['due_days']);
        self::assertSame(7, $policy['updated_by']);
        $data = $controller->index($this->request(), $service)->getData();
        self::assertTrue($data['customerDebts'][1]['alerts']['remind']);
    }

    public function test_sale_cannot_edit_another_customers_debt_terms(): void
    {
        $service = $this->service([$this->entry('KH02', 'Anh Hải')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(HttpException::class);
        (new CustomerDebtMonitorController)->updatePolicy($this->request(['source_key' => $key, 'allowed_orders' => 1]), $service);
    }

    public function test_unmatched_customer_terms_are_preserved_when_mapping_and_reloading(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $service = new CustomerDebtSheetService;
        $sales = [['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['12/11/2026', 'OLD01', 'Khách nguồn A', '1.000.000']];
        $payments = [['Ngày', 'Mã KH', 'Khách hàng', 'Thu'], ['12/11/2026', 'OLD01', 'Khách nguồn A', '500.000']];
        $service->save($sales, $payments, 7, 'paste');
        $group = array_values($service->summarize($service->snapshot()['entries']))[0];
        $policies = app(\App\Services\CustomerDebtPolicyService::class);
        $policies->save($group, ['allowed_orders' => 2, 'credit_limit' => 5000000, 'due_days' => 10], 7);
        $controller = new CustomerDebtMonitorController;
        $controller->map($this->request(['source_key' => $group['key'], 'customer_id' => 1], 'admin'), $service);
        $service->save($sales, $payments, 7, 'paste');
        self::assertSame(10, $policies->policies()['customer_1']['due_days']);
        $data = $controller->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        self::assertSame(2, $data['groups'][$group['key']]['policy']['allowed_orders']);
    }

    public function test_quick_edit_rejects_negative_limits(): void
    {
        $service = $this->service([$this->entry('OLD01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new CustomerDebtMonitorController)->updatePolicy($this->request(['source_key' => $key, 'credit_limit' => -1]), $service);
    }

    public function test_risk_filter_only_shows_customers_in_the_selected_band(): void
    {
        $old = $this->entry('OLD01', 'Khách nguồn A');
        $old['date'] = now()->subDays(31)->toDateString();
        $service = $this->service([$old, $this->entry('OLD02', 'Khách nguồn B')]);
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all', 'alert' => 'high'], 'admin'), $service)->getData();
        self::assertSame(1, $data['debts']->total());
        self::assertSame('Khách nguồn A', $data['debts']->first()['name']);
    }

    public function test_blank_quick_edit_values_remove_limits_instead_of_setting_zero(): void
    {
        $service = $this->service([$this->entry('OLD01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $controller = new CustomerDebtMonitorController;
        $controller->updatePolicy($this->request(['source_key' => $key, 'allowed_orders' => 1, 'credit_limit' => 100000, 'due_days' => 7], 'admin'), $service);
        $controller->updatePolicy($this->request(['source_key' => $key, 'allowed_orders' => '', 'credit_limit' => '', 'due_days' => '', 'policy_version' => 1], 'admin'), $service);
        $policy = app(\App\Services\CustomerDebtPolicyService::class)->policies()['customer_1'];
        self::assertNull($policy['allowed_orders']);
        self::assertNull($policy['credit_limit']);
        self::assertNull($policy['due_days']);
    }

    public function test_customer_aggregate_tracks_latest_dates_across_mapped_sources(): void
    {
        $first = $this->entry('OLD01', 'Khách nguồn A');
        $first['date'] = '2026-11-01';
        $second = $this->entry('KH02', 'Anh Hải');
        $second['date'] = '2026-11-10';
        $payment = $this->entry('OLD01', 'Khách nguồn A');
        $payment['date'] = '2026-11-09';
        $payment['type'] = 'payment';
        $payment['amount'] = 100;
        $service = $this->service([$first, $second, $payment]);
        foreach (array_keys($service->summarize($service->snapshot()['entries'])) as $key) {
            $service->mapCustomer($key, 1, 7);
        }
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        self::assertSame('2026-11-10', $data['customerDebts'][1]['last_purchase_date']);
        self::assertSame('2026-11-09', $data['customerDebts'][1]['last_payment_date']);
        self::assertSame(0, $data['customerDebts'][1]['policy']['allowed_orders']);
        self::assertSame(0, $data['customerDebts'][1]['policy']['credit_limit']);
        self::assertSame(1, $data['customerDebts'][1]['policy']['due_days']);
    }

    public function test_order_customer_panel_includes_unlinked_customers_and_combines_sources_once(): void
    {
        DB::table('orders')->insert(['id' => 3, 'customer_id' => 1, 'delivery_date' => now()->toDateString()]);
        $first = $this->entry('OLD01', 'Khách nguồn A');
        $first['date'] = now()->subDays(31)->toDateString();
        $second = $this->entry('OLD02', 'Khách nguồn B');
        $second['date'] = now()->subDays(20)->toDateString();
        $service = $this->service([$first, $second]);
        foreach (array_keys($service->summarize($service->snapshot()['entries'])) as $key) {
            $service->mapCustomer($key, 1, 7);
        }
        $data = (new CustomerDebtMonitorController)->index($this->request([], 'admin'), $service)->getData();
        self::assertSame(3, $data['orders']->total());
        self::assertSame(2, $data['debts']->total());
        $known = $data['debts']->getCollection()->firstWhere('customer_id', 1);
        self::assertTrue($known['data_available']);
        self::assertSame(2000, $known['balance']);
        self::assertSame('high', $known['alerts']['risk']);
        self::assertTrue($known['alerts']['overdue']);
        $unknown = $data['debts']->getCollection()->firstWhere('customer_id', 2);
        self::assertFalse($unknown['data_available']);
        self::assertNull($unknown['alerts']);
        self::assertArrayNotHasKey('balance', $unknown);
    }

    public function test_order_customer_panel_shows_only_customers_with_orders_in_selected_dates(): void
    {
        DB::table('orders')->where('id', 2)->update(['delivery_date' => now()->subDay()->toDateString()]);
        $data = (new CustomerDebtMonitorController)->index($this->request([], 'admin'), $this->service([]))->getData();
        self::assertSame(1, $data['debts']->total());
        self::assertSame(1, $data['debts']->first()['customer_id']);
        self::assertFalse($data['debts']->first()['data_available']);
    }

    public function test_sale_sees_own_order_customer_even_without_sheet_data(): void
    {
        $data = (new CustomerDebtMonitorController)->index($this->request(), $this->service([]))->getData();
        self::assertSame(1, $data['debts']->total());
        self::assertSame(1, $data['debts']->first()['customer_id']);
        self::assertFalse($data['debts']->first()['data_available']);
    }

    public function test_sale_column_prefers_current_customer_owner_and_uses_short_name(): void
    {
        DB::table('customers')->where('id', 1)->update(['current_owner_sale_id' => 8]);
        $entry = $this->entry('OLD01', 'Chị Bình') + ['sale_name' => 'Huy'];
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all'], 'admin'), $this->service([$entry]))->getData();
        self::assertSame('Hà', $data['debts']->first()['sale_label']);
        self::assertSame('user:8', $data['debts']->first()['sale_key']);
        self::assertSame('system', $data['debts']->first()['sale_origin']);
    }

    public function test_sale_filter_matches_both_customer_owner_and_unlinked_sheet_staff(): void
    {
        $service = $this->service([$this->entry('OLD01', 'Chị Bình'), $this->entry('UNLINKED', 'Khách chưa ghép') + ['sale_name' => 'Hà']]);
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all', 'sale' => 'user:8'], 'admin'), $service)->getData();
        self::assertSame(1, $data['debts']->total());
        self::assertSame('Khách chưa ghép', $data['debts']->first()['name']);
        self::assertSame('Hà', $data['debts']->first()['sale_label']);
        self::assertSame('sheet', $data['debts']->first()['sale_origin']);
        self::assertCount(2, $data['saleOptions']);
    }

    public function test_sheet_only_sales_can_be_filtered_without_a_staff_account(): void
    {
        $entry = $this->entry('UNLINKED', 'Khách chưa ghép') + ['sale_name' => 'Sale mới'];
        $service = $this->service([$entry]);
        $controller = new CustomerDebtMonitorController;
        $data = $controller->index($this->request(['tab' => 'all'], 'admin'), $service)->getData();
        $key = $data['saleOptions']->first()['key'];
        self::assertStringStartsWith('sheet:', $key);
        $filtered = $controller->index($this->request(['tab' => 'all', 'sale' => $key], 'admin'), $service)->getData();
        self::assertSame(1, $filtered['debts']->total());
        self::assertSame('Sale mới', $filtered['debts']->first()['sale_label']);
    }

    public function test_filtering_another_sale_does_not_expand_sales_customer_scope(): void
    {
        $service = $this->service([$this->entry('OLD01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải')]);
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all', 'sale' => 'user:8']), $service)->getData();
        self::assertSame(0, $data['debts']->total());
        self::assertSame(['user:7'], $data['saleOptions']->pluck('key')->all());
    }

    public function test_overdue_sort_is_numeric_and_applied_before_pagination(): void
    {
        $entries = [];
        for ($days = 1; $days <= 35; $days++) {
            $entry = $this->entry('SORT'.$days, 'Khách '.$days);
            $entry['date'] = now()->subDays($days + 1)->toDateString();
            $entry['amount'] = 10000 - $days;
            $entries[] = $entry;
        }
        $service = $this->service($entries);
        $controller = new CustomerDebtMonitorController;
        $descending = $controller->index($this->request(['tab' => 'all', 'sort' => 'overdue_days', 'direction' => 'desc'], 'admin'), $service)->getData()['debts'];
        self::assertSame(35, $descending->first()['alerts']['overdue_days']);
        self::assertSame(6, $descending->last()['alerts']['overdue_days']);
        self::assertSame(35, $descending->total());
        self::assertStringContainsString('sort=overdue_days', $descending->url(2));
        self::assertStringContainsString('direction=desc', $descending->url(2));
        $next = $controller->index($this->request(['tab' => 'all', 'sort' => 'overdue_days', 'direction' => 'desc', 'debt_page' => 2], 'admin'), $service)->getData()['debts'];
        self::assertSame([5, 4, 3, 2, 1], $next->getCollection()->map(fn ($row) => $row['alerts']['overdue_days'])->all());
        $ascending = $controller->index($this->request(['tab' => 'all', 'sort' => 'overdue_days', 'direction' => 'asc'], 'admin'), $service)->getData()['debts'];
        self::assertSame(1, $ascending->first()['alerts']['overdue_days']);
    }

    public function test_overdue_sort_keeps_customer_sale_filter_and_access_scope(): void
    {
        $first = $this->entry('OLD01', 'Chị Bình');
        $first['date'] = now()->subDays(11)->toDateString();
        $other = $this->entry('KH02', 'Anh Hải');
        $other['date'] = now()->subDays(101)->toDateString();
        $service = $this->service([$first, $other]);
        $controller = new CustomerDebtMonitorController;
        $data = $controller->index($this->request(['tab' => 'all', 'sort' => 'overdue_days', 'sale' => 'user:7'], 'admin'), $service)->getData();
        self::assertSame(1, $data['debts']->total());
        self::assertSame(10, $data['debts']->first()['alerts']['overdue_days']);
        $sale = $controller->index($this->request(['tab' => 'all', 'sort' => 'overdue_days']), $service)->getData();
        self::assertSame(1, $sale['debts']->total());
        self::assertSame(1, $sale['debts']->first()['customer_id']);
    }

    public function test_detail_report_date_range_includes_boundaries_and_opening_debt(): void
    {
        $sale = function ($date, $amount, $gift = 0) {
            $row = $this->entry('KH02', 'Anh Hải');
            $row['date'] = $date;
            $row['amount'] = $amount;
            $row['gift'] = $gift;

            return $row;
        };
        $payment = function ($date, $amount) use ($sale) {
            $row = $sale($date, $amount);
            $row['type'] = 'payment';

            return $row;
        };
        $entries = [$sale('2026-10-01', 1000, 100), $payment('2026-10-02', 100), $sale('2026-11-01', 1000, 200), $payment('2026-11-05', 500), $sale('2026-11-10', 700), $payment('2026-11-11', 1000)];
        $service = $this->service($entries);
        $key = array_key_first($service->summarize($entries));
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all', 'customer' => $key, 'report_from' => '2026-11-01', 'report_to' => '2026-11-10'], 'admin'), $service)->getData();
        self::assertSame(800, $data['opening']);
        self::assertSame(1700, $data['detailSales']);
        self::assertSame(200, $data['detailGifts']);
        self::assertSame(500, $data['detailPayments']);
        self::assertSame(1800, $data['closing']);
        self::assertSame(2, $data['purchases']->total());
        self::assertSame(1, $data['payments']->total());
        self::assertSame('2026-11-10', $data['reportLastPurchase']);
        self::assertSame('2026-11-05', $data['reportLastPayment']);
        self::assertSame(0, $data['reportAlerts']['overdue_days']);
        self::assertSame('high', $data['reportAlerts']['risk']);
    }

    public function test_purchase_and_payment_pagination_are_independent(): void
    {
        $entries = [];
        for ($row = 1; $row <= 101; $row++) {
            $entries[] = $this->entry('KH02', 'Anh Hải', $row);
            $payment = $this->entry('KH02', 'Anh Hải', $row);
            $payment['type'] = 'payment';
            $payment['amount'] = 100;
            $entries[] = $payment;
        }
        $service = $this->service($entries);
        $key = array_key_first($service->summarize($entries));
        $data = (new CustomerDebtMonitorController)->index($this->request(['tab' => 'all', 'customer' => $key, 'window' => 'all', 'purchase_page' => 2, 'payment_page' => 1], 'admin'), $service)->getData();
        self::assertCount(1, $data['purchases']);
        self::assertCount(100, $data['payments']);
        self::assertSame(101, $data['purchases']->total());
        self::assertSame(101, $data['payments']->total());
        self::assertSame(90900, $data['closing']);
        self::assertStringContainsString('purchase_page=2', $data['payments']->url(2));
    }

    public function test_detail_payments_show_latest_first_across_all_five_tabs_without_losing_history(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $entries = [$this->entry('CRM01', 'Chị Bình')];
        for ($i = 1; $i <= 101; $i++) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $i), ['type' => 'payment', 'date' => '2025-01-01', 'amount' => 1, 'source_sheet' => 'Dongtienhoatdong']);
        }
        foreach (CustomerDebtSheetService::PAYMENT_TABS as $i => $tab) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', 200 + $i), ['type' => 'payment', 'date' => sprintf('2026-11-%02d', 5 + $i), 'amount' => 100, 'source_sheet' => $tab]);
        }
        $service = $this->service($entries);
        $key = array_key_first($service->summarize($entries));
        $params = ['customer' => $key, 'window' => 'all', 'tab' => 'all'];
        $first = (new CustomerDebtMonitorController)->index($this->request($params, 'admin'), $service)->getData();
        self::assertSame('2026-11-09', $first['reportLastPayment']);
        self::assertSame($first['reportLastPayment'], $first['payments']->first()['date']);
        self::assertSame('TKCTVPB', $first['payments']->first()['source_sheet']);
        self::assertCount(5, $first['payments']->take(5)->pluck('source_sheet')->unique());
        self::assertSame(106, $first['payments']->total());
        self::assertSame(601, $first['detailPayments']);
        $last = (new CustomerDebtMonitorController)->index($this->request($params + ['payment_page' => 2], 'admin'), $service)->getData();
        self::assertCount(6, $last['payments']);
        self::assertSame(601, $last['detailPayments']);
        self::assertSame(106, $first['payments']->concat($last['payments'])->unique(fn ($e) => $e['source_sheet'].'|'.$e['row'])->count());
    }

    public function test_detail_report_rejects_reversed_date_range(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new CustomerDebtMonitorController)->index($this->request(['report_from' => '2026-11-10', 'report_to' => '2026-11-01'], 'admin'), $this->service([]));
    }

    public function test_empty_report_period_retains_opening_balance_and_latest_known_dates(): void
    {
        $sale = $this->entry('KH02', 'Anh Hải');
        $sale['date'] = '2026-11-01';
        $payment = $sale;
        $payment['date'] = '2026-11-03';
        $payment['type'] = 'payment';
        $payment['amount'] = 200;
        $future = $sale;
        $future['date'] = '2026-11-13';
        $service = $this->service([$sale, $payment, $future]);
        $key = array_key_first($service->summarize([$sale]));
        $data = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key, 'report_from' => '2026-11-10', 'report_to' => '2026-11-12'], 'admin'), $service)->getData();
        self::assertSame(0, $data['purchases']->total());
        self::assertSame(0, $data['payments']->total());
        self::assertSame(800, $data['opening']);
        self::assertSame(800, $data['closing']);
        self::assertSame('2026-11-01', $data['reportLastPurchase']);
        self::assertSame('2026-11-03', $data['reportLastPayment']);
    }

    public function test_source_detail_combines_all_sources_of_the_mapped_system_customer(): void
    {
        $sale = $this->entry('OLD01', 'Tên mua hàng');
        $sale['date'] = '2026-11-01';
        $payment = $this->entry('OLD02', 'Tên thanh toán');
        $payment['date'] = '2026-11-03';
        $payment['type'] = 'payment';
        $payment['amount'] = 300;
        $service = $this->service([$sale, $payment]);
        $groups = $service->summarize([$sale, $payment]);
        foreach (array_keys($groups) as $key) {
            $service->mapCustomer($key, 1, 7);
        }
        $key = array_key_first($service->summarize([$sale]));
        $data = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key, 'window' => 'all'], 'admin'), $service)->getData();
        self::assertSame('Chị Bình', $data['selected']['name']);
        self::assertSame(1000, $data['detailSales']);
        self::assertSame(300, $data['detailPayments']);
        self::assertSame(700, $data['closing']);
        self::assertSame(1, $data['purchases']->total());
        self::assertSame(1, $data['payments']->total());
    }

    private function policyUpdate(array $params = [], string $role = 'sale', int $customerId = 1): array
    {
        $request = $this->request($params + ['allowed_orders' => 2, 'credit_limit' => '10.000.000', 'due_days' => 7], $role);

        return (new \App\Http\Controllers\CustomerDebtPolicyController)->update($request, \App\Models\Customer::findOrFail($customerId), app(\App\Services\CustomerDebtPolicyService::class))->getData(true);
    }

    public function test_customer_card_staff_save_is_once_for_all_staff_roles(): void
    {
        $first = $this->policyUpdate();
        self::assertTrue($first['policy']['staff_edit_locked']);
        self::assertSame(1, $first['policy']['edit_version']);
        self::assertSame(10000000, $first['policy']['credit_limit']);
        foreach (['sale', 'leader', 'manager'] as $role) {
            try {
                $this->policyUpdate(['policy_version' => 1, 'allowed_orders' => 9], $role);
                self::fail('Locked policy accepted');
            } catch (\Illuminate\Validation\ValidationException $e) {
                self::assertArrayHasKey('policy', $e->errors());
            }
        }
        self::assertSame(2, app(\App\Services\CustomerDebtPolicyService::class)->policies()['customer_1']['allowed_orders']);
    }

    public function test_admin_unlock_allows_only_one_further_save_and_does_not_bank_credits(): void
    {
        $this->policyUpdate();
        $controller = new \App\Http\Controllers\CustomerDebtPolicyController;
        $service = app(\App\Services\CustomerDebtPolicyService::class);
        $customer = \App\Models\Customer::findOrFail(1);
        for ($cycle = 0; $cycle < 2; $cycle++) {
            $unlocked = $controller->unlock($this->request(['policy_version' => $service->policies()['customer_1']['edit_version']], 'admin'), $customer, $service)->getData(true)['policy'];
            $again = $controller->unlock($this->request(['policy_version' => $service->policies()['customer_1']['edit_version']], 'admin'), $customer, $service)->getData(true)['policy'];
            self::assertFalse($again['staff_edit_locked']);
            self::assertSame($unlocked['edit_version'], $again['edit_version']);
            $saved = $this->policyUpdate(['policy_version' => $again['edit_version']], 'leader')['policy'];
            self::assertTrue($saved['staff_edit_locked']);
            self::assertSame($cycle + 2, $saved['staff_edit_count']);
        }
        $audits = DB::table('settings')->where('key', 'like', 'customer_debt_policy_audit_%')->count();
        self::assertSame(5, $audits);
    }

    public function test_non_admin_cannot_unlock_even_through_service(): void
    {
        $this->policyUpdate();
        $service = app(\App\Services\CustomerDebtPolicyService::class);
        foreach (['sale', 'leader', 'manager', 'accounting'] as $role) {
            try {
                $service->unlock(['customer_id' => 1], $this->user($role));
                self::fail('Non-admin unlocked');
            } catch (HttpException $e) {
                self::assertSame(403, $e->getStatusCode());
            }
        }
        self::assertTrue($service->policies()['customer_1']['staff_edit_locked']);
    }

    public function test_stale_tab_cannot_consume_an_admin_reopened_edit_slot(): void
    {
        $this->policyUpdate();
        $service = app(\App\Services\CustomerDebtPolicyService::class);
        $service->unlock(['customer_id' => 1], $this->user('admin'));
        try {
            $this->policyUpdate(['policy_version' => 0]);
            self::fail('Stale version accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            self::assertArrayHasKey('policy_version', $e->errors());
        }
        self::assertFalse($service->policies()['customer_1']['staff_edit_locked']);
        self::assertTrue($this->policyUpdate(['policy_version' => 2])['policy']['staff_edit_locked']);
    }

    public function test_card_lock_cannot_be_bypassed_on_the_sheet_page(): void
    {
        $this->policyUpdate();
        $service = $this->service([$this->entry('OLD01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new CustomerDebtMonitorController)->updatePolicy($this->request(['source_key' => $key, 'policy_version' => 1, 'allowed_orders' => 99]), $service);
    }

    public function test_customer_card_editor_cannot_modify_another_sales_customer(): void
    {
        $this->expectException(HttpException::class);
        $this->policyUpdate([], 'sale', 2);
    }

    public function test_customer_card_edit_rejects_bad_values_without_consuming_the_slot(): void
    {
        try {
            $this->policyUpdate(['credit_limit' => -1]);
            self::fail('Negative limit accepted');
        } catch (\Illuminate\Validation\ValidationException $e) {
            self::assertArrayHasKey('credit_limit', $e->errors());
        }
        $service = app(\App\Services\CustomerDebtPolicyService::class);
        self::assertFalse($service->effective(['customer_id' => 1], $service->policies())['staff_edit_locked']);
        self::assertTrue($this->policyUpdate()['policy']['staff_edit_locked']);
    }

    public function test_admin_can_edit_locked_settings_without_granting_staff_an_extra_edit(): void
    {
        $this->policyUpdate();
        $changed = $this->policyUpdate(['policy_version' => 1, 'allowed_orders' => 5], 'admin')['policy'];
        self::assertSame(5, $changed['allowed_orders']);
        self::assertTrue($changed['staff_edit_locked']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->policyUpdate(['policy_version' => 2]);
    }

    public function test_customer_policy_rechecks_ownership_under_the_write_lock(): void
    {
        $stale = \App\Models\Customer::findOrFail(1);
        DB::table('customers')->where('id', 1)->update(['current_owner_sale_id' => 8]);
        $request = $this->request(['allowed_orders' => 2, 'credit_limit' => 100000, 'due_days' => 7]);
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\CustomerDebtPolicyController)->update($request, $stale, app(\App\Services\CustomerDebtPolicyService::class));
    }

    public function test_admin_can_configure_unmatched_sheet_source_with_the_same_lock_rules(): void
    {
        $source = $this->service([$this->entry('UNMATCHED', 'Khách nguồn chưa ghép')]);
        $key = array_key_first($source->summarize($source->snapshot()['entries']));
        $controller = new CustomerDebtMonitorController;
        $controller->updatePolicy($this->request(['source_key' => $key, 'due_days' => 5], 'admin'), $source);
        $policies = app(\App\Services\CustomerDebtPolicyService::class);
        self::assertTrue($policies->policies()['source_'.$key]['staff_edit_locked']);
        $controller->unlockPolicy($this->request(['source_key' => $key, 'policy_version' => 1], 'admin'), $source);
        self::assertFalse($policies->policies()['source_'.$key]['staff_edit_locked']);
    }

    public function test_stale_admin_unlock_cannot_reopen_a_slot_after_staff_have_used_it(): void
    {
        $this->policyUpdate();
        $service = app(\App\Services\CustomerDebtPolicyService::class);
        $controller = new \App\Http\Controllers\CustomerDebtPolicyController;
        $customer = \App\Models\Customer::findOrFail(1);
        $controller->unlock($this->request(['policy_version' => 1], 'admin'), $customer, $service);
        $this->policyUpdate(['policy_version' => 2]);
        try {
            $controller->unlock($this->request(['policy_version' => 1], 'admin'), $customer, $service);
            self::fail('Stale unlock reopened a new slot');
        } catch (\Illuminate\Validation\ValidationException $e) {
            self::assertArrayHasKey('policy_version', $e->errors());
        }
        self::assertTrue($service->policies()['customer_1']['staff_edit_locked']);
        self::assertSame(3, $service->policies()['customer_1']['edit_version']);
    }
}
