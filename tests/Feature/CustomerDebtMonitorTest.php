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
            $t->string('company_name')->nullable();
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
        $request = Request::create('/customer-debt', 'GET', $params + ['policy_version' => 0, 'min_debt' => 0]);
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

    public function test_google_customer_sync_applies_only_selected_source_and_retry_does_not_apply_others(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.updated: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter($this->customerSheetValues([
            ['CRM01', 'Chị Bình mới', '', '1000'], ['NEW01', 'Khách mới', '', '1000'],
        ]));
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync');
        $key = collect($importer->plan($importer->snapshot($token, $actor)))->firstWhere('code', 'CRM01')['key'];
        $counts = $importer->commit($token, $actor, true, 25, $key);
        self::assertSame(1, $counts['updated']);
        self::assertSame(0, $counts['created']);
        self::assertSame('Chị Bình mới', DB::table('customers')->where('id', 1)->value('name'));
        self::assertFalse(DB::table('customers')->where('customer_code', 'NEW01')->exists());
        $again = $importer->commit($token, $actor, true, 25, $key);
        self::assertSame(0, $again['updated']);
        self::assertSame(0, $again['created']);
        self::assertCount(1, collect($importer->plan($importer->snapshot($token, $actor)))->where('status', 'create'));
    }

    public function test_google_customer_sync_includes_contact_details_and_delivery_time_and_preserves_blank_values(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.updated: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter([
            ['Ngày', 'Mã KH', 'Khách hàng', 'NVKD', 'Tổng tiền', 'SĐT', 'Địa chỉ', 'Giờ giao'],
            ['12/11/2026', 'CRM01', 'Chị Bình', '', '1000', '0901234567', '123 Đường A', '7:30'],
            ['13/11/2026', 'CRM01', 'Chị Bình', '', '1000', '', '', ''],
        ]);
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync');
        $row = collect($importer->plan($importer->snapshot($token, $actor)))->firstWhere('code', 'CRM01');
        self::assertSame('07:30', $row['delivery_time']);
        self::assertSame('0901234567', $row['phone']);
        self::assertSame('123 Đường A', $row['address']);
        self::assertSame('07:30', $row['changes']['delivery_time']['after']);
        self::assertSame(1, $importer->commit($token, $actor, true)['updated']);
        $customer = DB::table('customers')->where('id', 1)->first();
        self::assertSame('07:30', $customer->delivery_time);
        self::assertSame('0901234567', $customer->phone);
        self::assertSame('123 Đường A', $customer->address);
        $empty = $this->sheetCustomerImporter([
            ['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền', 'Giờ giao'],
            ['14/11/2026', 'CRM01', 'Chị Bình', '1000', ''],
        ]);
        $next = $empty->load($actor, 'sync');
        $empty->commit($next, $actor, true);
        self::assertSame('07:30', DB::table('customers')->where('id', 1)->value('delivery_time'));
    }

    public function test_google_customer_sync_rejects_invalid_delivery_time(): void
    {
        $importer = $this->sheetCustomerImporter([
            ['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền', 'Giờ giao'],
            ['12/11/2026', 'CRM01', 'Chị Bình', '1000', '25:30'],
        ]);
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync');
        self::assertSame('conflict', collect($importer->plan($importer->snapshot($token, $actor)))->first()['status']);
        self::assertSame(0, $importer->commit($token, $actor, true)['updated']);
        self::assertNull(DB::table('customers')->where('id', 1)->value('delivery_time'));
    }

    public function test_google_customer_sync_keeps_latest_nonempty_contact_information(): void
    {
        \Illuminate\Support\Facades\Event::fake(['eloquent.updated: App\Models\Customer']);
        $importer = $this->sheetCustomerImporter([
            ['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền', 'SĐT', 'Địa chỉ'],
            ['01/11/2026', 'CRM01', 'Chị Bình', '1000', '0901111111', 'Địa chỉ mới'],
            ['12/11/2026', 'CRM01', 'Chị Bình', '1000', '', ''],
        ]);
        $actor = $this->user('admin');
        $token = $importer->load($actor, 'sync');
        $row = array_values($importer->plan($importer->snapshot($token, $actor)))[0];
        self::assertSame('Chị Bình', $row['new_name']);
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

    public function test_reused_code_is_separate_on_all_four_debt_pages_and_stale_payment_keys_are_reassigned(): void
    {
        $legacy = hash('sha256', 'code:CRM01');
        $entries = [
            $this->entry('CRM01', 'Chị Bình', 1),
            array_replace($this->entry('CRM01', 'Công ty khác', 2), ['amount' => 2000]),
            array_replace($this->entry('', 'Chị Bình', 3), ['type' => 'payment', 'amount' => 100, 'source_sheet' => 'TKCTVPB', 'customer_source_key' => $legacy]),
            array_replace($this->entry('', 'Công ty khác', 4), ['type' => 'payment', 'amount' => 200, 'source_sheet' => 'TKCTVPB', 'customer_source_key' => $legacy]),
        ];
        $service = $this->service($entries);
        $context = (new CustomerDebtMonitorController)->debtContext($this->request([], 'admin'), $service);
        self::assertCount(2, $context['groups']);
        $redirect = (new CustomerDebtMonitorController)->index($this->request(['customer' => $legacy], 'admin'), $service);
        self::assertStringNotContainsString('customer='.$legacy, $redirect->getTargetUrl());
        self::assertStringContainsString('#debt-detail', $redirect->getTargetUrl());
        $first = collect($context['groups'])->firstWhere('name', 'Chị Bình');
        $second = collect($context['groups'])->firstWhere('name', 'Công ty khác');
        self::assertSame(1, $first['customer_id']);
        self::assertNull($second['customer_id']);
        foreach ([$first, $second] as $group) {
            $detail = (new CustomerDebtMonitorController)->index($this->request(['customer' => $group['key'], 'window' => 'all'], 'admin'), $service)->getData();
            self::assertSame($group['balance'], $detail['closing']);
        }
        $report = new \App\Services\CustomerDebtReportService;
        $data = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request([], 'admin'), $service, $report, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(2, $data['rows']->total());
        self::assertSame(2700, $data['summary']['balance']);
        $terms = (new \App\Http\Controllers\CustomerDebtNegotiationController)->index($this->request([], 'admin'), $service, $report)->getData();
        self::assertSame(2700, $terms['summary']['balance']);
        foreach ([$first['key'] => 100, $second['key'] => 200] as $key => $amount) {
            $payments = (new \App\Http\Controllers\CustomerPaymentController)->index($this->request(['customer_source_key' => $key], 'admin'), $service, new \App\Services\CustomerPaymentReportService)->getData();
            self::assertSame(1, $payments['rows']->total());
            self::assertSame($amount, $payments['summary']['amount']);
        }
        $batch = ['changes' => [['kind' => 'removed', 'before' => $entries[3], 'after' => null, 'delta' => -200]]];
        $historical = (new \App\Services\CustomerPaymentReportService)->rows($context, $batch)->first();
        self::assertSame($second['key'], $historical['source_key']);
        self::assertNull($historical['customer_id']);
    }

    public function test_company_name_and_phone_corroborate_customer_matching_instead_of_code_alone(): void
    {
        DB::table('customers')->where('id', 1)->update(['company_name' => 'Công ty Bình', 'phone' => '0901234567']);
        $service = $this->service([
            $this->entry('CRM01', 'Công ty Bình', 1) + ['phone' => '+84 901 234 567'],
            $this->entry('CRM01', 'Công ty khác', 2) + ['phone' => '0907654321'],
        ]);
        $context = (new CustomerDebtMonitorController)->debtContext($this->request([], 'admin'), $service);
        self::assertSame(1, collect($context['groups'])->firstWhere('name', 'Công ty Bình')['customer_id']);
        self::assertNull(collect($context['groups'])->firstWhere('name', 'Công ty khác')['customer_id']);
    }

    public function test_phone_distinguishes_same_name_and_code_without_merging_back_to_one_crm_customer(): void
    {
        DB::table('customers')->where('id', 1)->update(['phone' => '0901234567']);
        $entries = [
            $this->entry('CRM01', 'Chị Bình', 1) + ['phone' => '+84 901 234 567'],
            $this->entry('CRM01', 'Chị Bình', 2) + ['phone' => '0907654321'],
        ];
        $context = (new CustomerDebtMonitorController)->debtContext($this->request([], 'admin'), $this->service($entries));
        self::assertCount(2, $context['groups']);
        self::assertSame(1, collect($context['groups'])->firstWhere('phone', '0901234567')['customer_id']);
        self::assertNull(collect($context['groups'])->firstWhere('phone', '0907654321')['customer_id']);
        self::assertSame(1000, $context['customerDebts'][1]['balance']);
    }

    public function test_accounting_debt_table_includes_other_sales_and_unmapped_sources(): void
    {
        $service = $this->service([
            $this->entry('CRM01', 'Chị Bình'),
            $this->entry('KH02', 'Anh Hải', 2),
            $this->entry('NEW01', 'Khách chưa ghép', 3),
        ]);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->accountingIndex($this->request(['range' => 'all'], 'accountant'), $service, new CustomerDebtMonitorController)->getData();
        self::assertTrue($data['accountingDebt']);
        self::assertTrue($data['canLoad']);
        self::assertSame(3, $data['debts']->total());
        self::assertSame(3000, $data['summary']['balance']);
    }

    public function test_accounting_sale_filter_applies_to_rows_and_totals_and_keeps_all_sale_options(): void
    {
        $service = $this->service([
            $this->entry('CRM01', 'Chị Bình'),
            $this->entry('KH02', 'Anh Hải', 2),
            $this->entry('NEW01', 'Khách chưa ghép', 3),
        ]);
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        foreach (['user:7' => 'Chị Bình', 'user:8' => 'Anh Hải', 'unassigned' => 'Khách chưa ghép'] as $sale => $name) {
            $data = $controller->accountingIndex($this->request(['range' => 'all', 'sale' => $sale], 'accountant'), $service, new CustomerDebtMonitorController)->getData();
            self::assertSame([$name], $data['debts']->pluck('name')->all());
            self::assertSame(1000, $data['summary']['balance']);
            self::assertCount(3, $data['saleOptions']);
        }
    }

    public function test_accounting_can_update_locked_payment_cycle_and_preserves_recovery(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $groups = $service->summarize($service->snapshot()['entries']);
        $group = reset($groups);
        $policies = new \App\Services\CustomerDebtPolicyService;
        $policies->save(['customer_id' => 1], ['allowed_orders' => 1, 'credit_limit' => 5000, 'due_days' => 1], 7, true, 0);
        $request = $this->request([
            'source_key' => $group['key'], 'policy_version' => 1,
            'payment_mode' => 'cycle', 'cycle_type' => 'monthly', 'cycle_close_monthday' => 15, 'cycle_monthday' => 20,
            'allowed_orders' => 3, 'credit_limit' => 10000, 'due_days' => 2,
        ], 'accountant');
        (new \App\Http\Controllers\AccountingDashboardController)->customerDebtPolicyUpdate($request, $service, new CustomerDebtMonitorController);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->accountingIndex($this->request(['payment_mode' => 'cycle'], 'accountant'), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(1, $data['debts']->total());
        $row = $data['debts']->first();
        self::assertSame(20, $row['policy']['cycle_monthday']);
        self::assertSame(2, $row['policy']['edit_version']);
        self::assertSame('normal', $row['recovery']['status']);
    }

    public function test_saved_vouchers_roll_forward_unpaid_debt_and_require_proof_for_full_payment(): void
    {
        $this->collectionClock('2026-11-20 12:00:00');
        \Illuminate\Support\Facades\Storage::fake('local');
        $sheets = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-19', 'amount' => 1000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-20', 'amount' => 300]),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-20', 'type' => 'payment', 'amount' => 400]),
        ]);
        $controller = new CustomerDebtMonitorController;
        $vouchers = app(\App\Services\CustomerDebtVouchers::class);
        $query = ['system_customer' => 1, 'debt_section' => 'vouchers', 'window' => 'all'];
        $register = fn () => $controller->index($this->request($query, 'accountant'), $sheets)->getData()['voucherRegister'];
        $save = function (array $data, array $files = []) use ($controller, $query, $sheets, $vouchers, $register) {
            $request = $this->request($query + $data + ['revision' => $register()['revision']], 'accountant');
            if ($files) {
                $request->files->set('documents', $files);
            }

            return $controller->saveVoucher($request, $sheets, $vouchers);
        };

        $save(['type' => 'payment-request', 'through' => '2026-11-19', 'paid' => 400, 'number' => 'DN-001']);
        $first = $register()['latest'];
        self::assertSame('partial', $first['status']);
        self::assertSame(600, $first['remaining']);

        $response = $save(['type' => 'receipt', 'through' => '2026-11-20', 'paid' => 0]);
        self::assertStringContainsString('debt_section=vouchers', $response->getTargetUrl());
        $second = $register()['latest'];
        self::assertSame('PT-0002', $second['number']);
        self::assertSame(600, $second['carry']);
        self::assertSame(300, $second['new_amount']);
        self::assertSame(900, $second['total']);
        self::assertSame('unpaid', $second['status']);

        $save(['voucher_id' => $first['id'], 'new_amount' => 1000, 'paid' => 500]);
        self::assertSame(800, $register()['latest']['total']);

        $payment = ['voucher_id' => $second['id'], 'new_amount' => 300, 'paid' => 800];
        try {
            $save($payment);
            self::fail('Full payment was accepted without proof');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            self::assertArrayHasKey('documents', $exception->errors());
        }
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('chung-tu.pdf', "%PDF-1.4\n");
        $save($payment, [$file]);
        $settled = $register()['latest'];
        self::assertSame('paid', $settled['status']);
        self::assertSame(0, $settled['remaining']);
        self::assertCount(1, $settled['documents']);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($settled['documents'][0]['path']);
        $download = $controller->downloadVoucherDocument($this->request($query, 'accountant'), $settled['id'], $settled['documents'][0]['id'], $sheets, $vouchers);
        self::assertStringContainsString('chung-tu.pdf', $download->headers->get('Content-Disposition'));
    }

    public function test_voucher_can_select_purchase_days_and_preserves_unselected_older_debt(): void
    {
        $this->collectionClock('2026-11-20 12:00:00');
        $sheets = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-18', 'amount' => 1200]),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-19', 'amount' => 700]),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-19', 'amount' => 300]),
            array_replace($this->entry('CRM01', 'Chị Bình', 4), ['date' => '2026-11-19', 'type' => 'payment', 'amount' => 200]),
        ]);
        $controller = new CustomerDebtMonitorController;
        $vouchers = app(\App\Services\CustomerDebtVouchers::class);
        $query = ['system_customer' => 1, 'debt_section' => 'vouchers', 'window' => 'all'];
        $register = fn (array $filters = []) => $controller->index($this->request($query + $filters, 'accountant'), $sheets)->getData()['voucherRegister'];
        $save = fn (array $dates) => $controller->saveVoucher($this->request($query + [
            'revision' => $register()['revision'], 'through' => '2026-11-20', 'type' => 'receipt',
            'paid' => 0, 'purchase_dates' => $dates, 'new_amount' => 10,
        ], 'accountant'), $sheets, $vouchers);

        $before = $register(['voucher_purchases_size' => 50, 'voucher_selected' => ['2026-11-19']]);
        self::assertSame(50, $before['purchases']->perPage());
        self::assertSame(2, $before['purchases']->total());
        self::assertSame(2, $before['purchases']->getCollection()->first()['count']);
        self::assertSame(['2026-11-19' => 1000], $before['selectedPurchases']->all());

        $save(['2026-11-19']);
        $first = $register()['latest'];
        self::assertSame(['2026-11-19' => 1000], $first['purchase_days']);
        self::assertSame(1000, $first['new_amount']);
        self::assertSame(1000, $register()['suggested']);
        try {
            $save(['2026-11-19']);
            self::fail('The same purchase day could be added twice');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            self::assertArrayHasKey('purchase_dates', $exception->errors());
        }

        $save(['2026-11-18']);
        $second = $register()['latest'];
        self::assertSame(['2026-11-18' => 1000], $second['purchase_days']);
        self::assertSame(1000, $second['carry']);
        self::assertSame(2000, $second['total']);
    }

    public function test_debt_vouchers_can_include_all_delivery_dates_beyond_the_manual_limit(): void
    {
        $this->collectionClock('2026-11-19 10:00:00');
        $entries = [];
        foreach (range(0, 120) as $day) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $day + 1), [
                'date' => \Carbon\CarbonImmutable::parse('2026-01-01')->addDays($day)->toDateString(),
            ]);
        }
        $sheets = $this->service($entries);
        $controller = new CustomerDebtMonitorController;
        foreach (['payment-request', 'receipt'] as $presentation) {
            $request = $this->request([
                'customer' => hash('sha256', 'code:CRM01'), 'presentation' => $presentation,
                'report_from' => '2026-01-02', 'report_to' => '2026-11-19', 'voucher_all' => '1',
            ], 'admin');
            $voucher = $controller->index($request, $sheets)->getData();
            self::assertCount(120, $voucher['lines']);
            self::assertFalse($voucher['lines']->has('2026-01-01'));
            self::assertSame(120000, $voucher['total']);
        }
    }

    public function test_debt_vouchers_use_selected_delivery_dates_without_posting_a_payment(): void
    {
        $this->collectionClock('2026-11-19 10:00:00');
        $sheets = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-13', 'amount' => 3000, 'gift' => 500]),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-13', 'amount' => 2000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-14', 'amount' => 7000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 4), ['date' => '2026-11-15', 'type' => 'payment', 'amount' => 1000]),
        ]);
        $controller = new CustomerDebtMonitorController;
        $query = ['customer' => hash('sha256', 'code:CRM01'), 'window' => 'all'];
        $preview = $controller->index($this->request($query + ['presentation' => 'payment-request'], 'admin'), $sheets)->getData();
        self::assertSame(['2026-11-13' => 3500, '2026-11-14' => 7000], $preview['deliveries']->all());
        self::assertTrue($preview['lines']->isEmpty());

        $request = $this->request($query + ['presentation' => 'receipt', 'voucher_dates' => ['2026-11-13'], 'voucher_method' => 'transfer', 'voucher_number' => 'PT-01'], 'admin');
        $receipt = $controller->index($request, $sheets)->getData();
        self::assertSame(['2026-11-13' => 3500], $receipt['lines']->all());
        self::assertSame(4500, $receipt['deliveryTotals']['2026-11-13']);
        self::assertSame(3500, $receipt['total']);
        $paymentRequest = $controller->index($this->request($query + [
            'presentation' => 'payment-request', 'voucher_dates' => ['2026-11-13'],
        ], 'admin'), $sheets)->getData();
        self::assertSame($receipt['lines']->all(), $paymentRequest['lines']->all());
        self::assertSame($receipt['total'], $paymentRequest['total']);
        self::assertSame('PT-01', $receipt['number']);
        self::assertSame('Chuyển khoản', $receipt['method']);
        self::assertCount(4, $sheets->snapshot()['entries']);
        self::assertSame('Mười bảy triệu tám trăm bảy mươi lăm nghìn đồng.', \App\Support\VietnameseMoneyWords::format(17875000));

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->index($this->request($query + ['presentation' => 'payment-request', 'voucher_dates' => ['2026-11-15']], 'admin'), $sheets);
    }

    public function test_report_lists_only_periodic_customers_and_shows_the_current_cycle_dates(): void
    {
        $this->collectionClock('2026-11-19 10:00:00');
        $sheets = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình'), ['date' => '2026-11-17']),
            $this->entry('KH02', 'Anh Hải', 2),
        ]);
        $key = hash('sha256', 'code:CRM01');
        (new CustomerDebtMonitorController)->updatePolicy($this->request([
            'source_key' => $key, 'payment_mode' => 'cycle', 'cycle_type' => 'weekly',
            'cycle_close_weekday' => 2, 'cycle_weekday' => 5,
        ], 'admin'), $sheets);

        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $report = fn (array $filters) => $controller->index($this->request($filters, 'admin'), $sheets, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(2, $report([])['rows']->total());
        $periodic = $report(['payment_mode' => 'cycle']);
        self::assertSame(1, $periodic['summary']['customers']);
        $row = $periodic['rows']->first();
        self::assertSame(1, $row['customer_id']);
        self::assertSame('2026-11-17', $row['alerts']['cycle_close_date']);
        self::assertSame('2026-11-20', $row['alerts']['due_date']);
    }

    public function test_sale_payment_cycle_locks_and_admin_unlock_grants_exactly_one_update(): void
    {
        $controller = new \App\Http\Controllers\CustomerDebtPolicyController;
        $customer = \App\Models\Customer::findOrFail(1);
        $policies = new \App\Services\CustomerDebtPolicyService;
        $data = ['payment_mode' => 'cycle', 'cycle_type' => 'weekly', 'cycle_close_weekday' => 2, 'cycle_weekday' => 5, 'credit_limit' => 10000, 'policy_version' => 0];
        $result = $controller->update($this->request($data), $customer, $policies)->getData(true);
        self::assertTrue($result['policy']['staff_edit_locked']);
        self::assertSame(2, $result['policy']['cycle_close_weekday']);
        try {
            $controller->update($this->request(array_replace($data, ['policy_version' => 1])), $customer, $policies);
            self::fail('Second sale save must be locked');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertSame(1, $policies->effective(['customer_id' => 1], $policies->policies())['edit_version']);
        }
        $controller->unlock($this->request(['policy_version' => 1], 'admin'), $customer, $policies);
        $result = $controller->update($this->request(array_replace($data, ['payment_mode' => 'default', 'policy_version' => 2])), $customer, $policies)->getData(true);
        self::assertTrue($result['policy']['staff_edit_locked']);
        self::assertTrue($result['policy']['rolling_only']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->update($this->request(array_replace($data, ['policy_version' => 3])), $customer, $policies);
    }

    public function test_accounting_classification_and_recovery_filters_intersect_before_totals(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải', 2), $this->entry('NEW01', 'Khách định kỳ', 3)]);
        $policies = new \App\Services\CustomerDebtPolicyService;
        $policies->save(['customer_id' => 2], ['allowed_orders' => 2], 7, true, 0);
        $groups = $service->summarize($service->snapshot()['entries']);
        $cycle = collect($groups)->firstWhere('code', 'NEW01');
        $policies->save($cycle, ['payment_mode' => 'cycle', 'cycle_type' => 'weekly', 'cycle_weekday' => 5], 7, true, 0);
        $recovery = new \App\Services\CustomerDebtRecoveryService;
        $group = ['customer_id' => 2];
        $before = $recovery->effective($group, $recovery->records());
        $recovery->save($group, ['recovery_status' => 'difficult', 'recovery_version' => $before['version']], $this->user('admin'));
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        foreach ([['all', 'all', 3], ['default', 'all', 1], ['multi', 'all', 1], ['multi', 'difficult', 1], ['multi', 'normal', 0], ['cycle', 'normal', 1], ['cycle', 'difficult', 0]] as [$classification, $status, $count]) {
            $data = $controller->accountingIndex($this->request(['range' => 'all', 'payment_class' => $classification, 'recovery' => $status], 'accountant'), $service, new CustomerDebtMonitorController)->getData();
            self::assertSame($count, $data['debts']->total());
            self::assertSame($count * 1000, $data['summary']['balance']);
        }
        $data = $controller->accountingIndex($this->request([], 'accountant'), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(3, $data['debts']->total());
    }

    public function test_sale_cannot_open_accounting_debt_table(): void
    {
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\MyCustomerDebtController)->accountingIndex($this->request(), $this->service([]), new CustomerDebtMonitorController);
    }

    public function test_sale_debt_table_excludes_unmapped_sources_even_when_the_sheet_names_the_sale(): void
    {
        $service = $this->service([
            $this->entry('CRM01', 'Chị Bình'),
            $this->entry('KH02', 'Anh Hải', 2),
            $this->entry('NEW01', 'Khách nguồn của Huy', 3) + ['sale_name' => 'Huy'],
            $this->entry('NEW02', 'Khách nguồn của Hà', 4) + ['sale_name' => 'Hà'],
        ]);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request(), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(1, $data['debts']->total());
        self::assertSame(1000, $data['summary']['balance']);
        self::assertSame(['Chị Bình'], $data['debts']->pluck('name')->sort()->values()->all());
        self::assertCount(1, $data['customerOptions']);
    }

    public function test_sale_debt_date_filter_keeps_full_balance_and_selected_customer_reconciliation(): void
    {
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-01', 'amount' => 1000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-12', 'amount' => 2000]),
            array_replace($this->entry('', 'Chị Bình', 3), ['date' => '2026-11-13', 'type' => 'payment', 'amount' => 500]),
        ];
        $service = $this->service($entries);
        $key = array_key_first($service->summarize($entries));
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        $view = $controller->index($this->request([
            'from' => '2026-11-10', 'to' => '2026-11-16', 'customer' => $key,
            'report_from' => '2026-11-10', 'report_to' => '2026-11-16',
        ]), $service, new CustomerDebtMonitorController);
        self::assertSame('site.my_customer_debt_detail', $view->name());
        $data = $view->getData();
        self::assertSame(2500, $data['selected']['balance']);
        self::assertFalse($data['canLoad']);
        self::assertNull($data['reminderHistory']);
        self::assertSame(route('customers.debt-policy.update', 1), $data['detailPolicyAction']);
        self::assertSame(1000, $data['opening']);
        self::assertSame(2000, $data['detailSales']);
        self::assertSame(500, $data['detailPayments']);
        self::assertSame(2500, $data['closing']);
        self::assertSame(1, $data['purchases']->total());
    }

    public function test_sale_can_print_a_voucher_from_their_own_debt_detail(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        $view = $controller->index($this->request([
            'customer' => $key, 'presentation' => 'receipt', 'voucher_all' => '1',
        ]), $service, new CustomerDebtMonitorController);
        self::assertSame('customer-debt.voucher', $view->name());
        self::assertSame(1000, $view->getData()['total']);
    }

    public function test_sale_cannot_open_another_sales_customer_on_the_new_debt_page(): void
    {
        $service = $this->service([$this->entry('KH02', 'Anh Hải')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request(['customer' => $key]), $service, new CustomerDebtMonitorController);
    }

    public function test_sale_cannot_open_another_sales_unmapped_source_via_customer_statement(): void
    {
        $service = $this->service([$this->entry('NEW02', 'Khách nguồn của Hà') + ['sale_name' => 'Hà']]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request(['customer' => $key, 'presentation' => 'customer']), $service, new CustomerDebtMonitorController);
    }

    public function test_personal_debt_is_scoped_even_when_user_has_both_sale_and_finance_roles(): void
    {
        $request = $this->request(['sale' => 'user:8', 'own_customer_debt' => false, 'report_source' => 'system']);
        $user = $this->user('sale');
        $user->setRelation('roles', collect([new Role(['name' => 'sale']), new Role(['name' => 'account'])]));
        $request->setUserResolver(fn () => $user);
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải', 2)]);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->index($request, $service, new CustomerDebtMonitorController)->getData();
        self::assertFalse($data['canLoad']);
        self::assertSame(['Chị Bình'], $data['debts']->pluck('name')->all());
        self::assertSame(1000, $data['summary']['balance']);
    }

    public function test_ownership_transfer_revokes_old_sale_access_and_grants_current_owner_only(): void
    {
        DB::table('customers')->where('id', 1)->update(['current_owner_sale_id' => 8]);
        $service = $this->service([$this->entry('CRM01', 'Chị Bình') + ['sale_name' => 'Huy']]);
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        $old = $controller->index($this->request(), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(0, $old['debts']->total());
        $request = $this->request(); $owner = $this->user('sale'); $owner->id = 8;
        $request->setUserResolver(fn () => $owner);
        $current = $controller->index($request, $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(['Chị Bình'], $current['debts']->pluck('name')->all());
    }

    public function test_debt_endpoints_reject_foreign_ids_keys_printouts_and_legacy_links(): void
    {
        $entries = [$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải', 2)];
        $service = $this->service($entries);
        $groups = $service->summarize($entries);
        $own = collect($groups)->firstWhere('code', 'CRM01')['key'];
        $foreign = collect($groups)->firstWhere('code', 'KH02')['key'];
        foreach ([['customer' => $foreign], ['system_customer' => 2], ['customer' => $own, 'system_customer' => 2],
            ['customer' => $foreign, 'presentation' => 'customer'], ['system_customer' => 2, 'presentation' => 'customer'],
            ['customer' => $foreign, 'sale' => 'user:8', 'balance' => 'all', 'recovery' => 'all']] as $query) {
            foreach ([false, true] as $personal) {
                try {
                    $request = $this->request($query);
                    $personal
                        ? (new \App\Http\Controllers\MyCustomerDebtController)->index($request, $service, new CustomerDebtMonitorController)
                        : (new CustomerDebtMonitorController)->index($request, $service);
                    self::fail('Foreign customer was accessible: '.json_encode($query));
                } catch (HttpException $e) {
                    self::assertSame(404, $e->getStatusCode());
                }
            }
        }
    }

    public function test_sale_receives_no_other_customer_models_source_rows_or_unidentified_payment_notes(): void
    {
        $service = new class([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải', 2)]) extends CustomerDebtSheetService {
            public function __construct(private array $entries) {}
            public function snapshot(): ?array {
                return ['entries' => $this->entries, 'issues' => [['amount' => 999, 'note' => 'PRIVATE_OTHER_CUSTOMER_PAYMENT']], 'payment_syncs' => [['count' => 999]], 'loaded_by' => 123];
            }
        };
        $context = (new CustomerDebtMonitorController)->debtContext($this->request(), $service);
        self::assertSame([1], $context['customers']->pluck('id')->all());
        self::assertCount(1, $context['snapshot']['entries']);
        self::assertSame([], $context['snapshot']['issues']);
        self::assertArrayNotHasKey('payment_syncs', $context['snapshot']);
        self::assertArrayNotHasKey('loaded_by', $context['snapshot']);
        self::assertStringNotContainsString('PRIVATE_OTHER_CUSTOMER_PAYMENT', json_encode($context));
        self::assertStringNotContainsString('Anh Hải', json_encode($context));
    }

    public function test_same_name_with_mismatched_code_is_not_evidence_of_owned_debt(): void
    {
        $service = $this->service([$this->entry('WRONGCODE', 'Chị Bình') + ['sale_name' => 'Huy']]);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request(), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(0, $data['debts']->total());
        self::assertCount(0, $data['customerOptions']);
    }

    public function test_matching_phone_can_verify_owned_customer_when_source_code_is_missing(): void
    {
        DB::table('customers')->where('id', 1)->update(['phone' => '0901234567']);
        $service = $this->service([$this->entry('', 'Chị Bình') + ['phone' => '+84 901 234 567']]);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request(), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(1, $data['debts']->total());
    }

    public function test_personal_debt_route_requires_authentication_sales_role_and_disables_response_caching(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('pages.my_customer_debt');
        self::assertContains('auth', $route->gatherMiddleware());
        self::assertContains('cache.headers:private;no_store;max_age=0', $route->gatherMiddleware());
        $response = (new \Illuminate\Http\Middleware\SetCacheHeaders)->handle($this->request(), fn () => new \Illuminate\Http\Response('personal ledger'), 'private;no_store;max_age=0');
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request([], 'shipper'), $this->service([]), new CustomerDebtMonitorController);
    }

    public function test_ownership_transfer_invalidates_existing_customer_links_and_printouts_immediately(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        self::assertSame(1000, $controller->index($this->request(['customer' => $key]), $service, new CustomerDebtMonitorController)->getData()['closing']);
        DB::table('customers')->where('id', 1)->update(['current_owner_sale_id' => 8]);
        foreach ([['customer' => $key], ['customer' => $key, 'presentation' => 'customer'], ['system_customer' => 1]] as $query) {
            try {
                $controller->index($this->request($query), $service, new CustomerDebtMonitorController);
                self::fail('Former owner could still access reassigned customer');
            } catch (HttpException $e) {
                self::assertSame(404, $e->getStatusCode());
            }
        }
    }

    public function test_released_and_deleted_customers_are_not_visible_to_the_previous_sale(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        DB::table('customers')->where('id', 1)->update(['customer_status' => 'free']);
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        self::assertSame(0, $controller->index($this->request(), $service, new CustomerDebtMonitorController)->getData()['debts']->total());
        DB::table('customers')->where('id', 1)->update(['customer_status' => 'managed', 'deleted_at' => now()]);
        self::assertSame(0, $controller->index($this->request(), $service, new CustomerDebtMonitorController)->getData()['debts']->total());
    }

    public function test_leader_cannot_use_personal_debt_menu_to_browse_another_sales_customer(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải', 2)]);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request([], 'leader'), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(['Chị Bình'], $data['debts']->pluck('name')->all());
        self::assertSame(1000, $data['summary']['balance']);
    }

    public function test_sale_report_separates_lifetime_and_period_values_including_gifts_and_adjustments(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-16 12:00:00');
        \Carbon\CarbonImmutable::setTestNow('2026-11-16 12:00:00');
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-01', 'amount' => 1000]),
            array_replace($this->entry('', 'Chị Bình', 2), ['date' => '2026-11-02', 'type' => 'payment', 'amount' => 100]),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-10', 'amount' => 2000, 'gift' => 100]),
            array_replace($this->entry('', 'Chị Bình', 4), ['date' => '2026-11-16', 'type' => 'payment', 'amount' => 500]),
            $this->entry('KH02', 'Anh Hải', 5),
        ];
        $service = $this->service($entries);
        $key = collect($service->summarize($entries))->firstWhere('code', 'CRM01')['key'];
        $this->adjust($service, $key, 2330);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request(['from' => '2026-11-10', 'to' => '2026-11-16']), $service, new CustomerDebtMonitorController)->getData();
        $row = $data['debts']->first();
        self::assertSame(3000, $row['sales']);
        self::assertSame(600, $row['payments']);
        self::assertSame(2330, $row['balance']);
        self::assertSame(2000, $row['period_sales']);
        self::assertSame(500, $row['period_payments']);
        self::assertSame(100, $row['period_gifts']);
        self::assertSame(30, $row['period_adjustments']);
        self::assertSame(1430, $row['period_increase']);
        self::assertSame(1430, $data['summary']['period_increase']);
    }

    public function test_sale_report_includes_payment_only_periods_unless_purchase_scope_selected(): void
    {
        $service = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-01', 'amount' => 1000]),
            array_replace($this->entry('', 'Chị Bình', 2), ['date' => '2026-11-12', 'type' => 'payment', 'amount' => 300]),
        ]);
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        $query = ['from' => '2026-11-10', 'to' => '2026-11-16'];
        $all = $controller->index($this->request($query), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(1, $all['debts']->total());
        self::assertSame(0, $all['summary']['period_sales']);
        self::assertSame(300, $all['summary']['period_payments']);
        self::assertSame(-300, $all['summary']['period_increase']);
        $purchases = $controller->index($this->request($query + ['scope' => 'orders']), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(0, $purchases['debts']->total());
    }

    public function test_sale_report_search_pagination_and_branding_preserve_scoped_totals(): void
    {
        $entries = [];
        for ($i = 10; $i < 35; $i++) {
            $name = 'Khách Huy '.$i;
            DB::table('customers')->insert(['id' => $i, 'name' => $name, 'customer_code' => 'OWN'.$i, 'assigned_to' => 7, 'current_owner_sale_id' => 7]);
            $entries[] = $this->entry('OWN'.$i, $name, $i);
        }
        $entries[] = $this->entry('KH02', 'Anh Hải', 99);
        DB::table('settings')->insert([
            ['key' => 'brand_name', 'value' => 'Thương hiệu thử'], ['key' => 'logo', 'value' => '123'],
            ['key' => 'slogan', 'value' => 'Khẩu hiệu thử'], ['key' => 'google_api_secret', 'value' => 'PRIVATE_SECRET'],
        ]);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request([
            'range' => 'all', 'search' => 'Khách Huy', 'per_page' => 20, 'page' => 2, 'sort' => 'period_sales', 'direction' => 'asc',
        ]), $this->service($entries), new CustomerDebtMonitorController)->getData();
        self::assertSame(25, $data['debts']->total());
        self::assertCount(5, $data['debts']);
        self::assertSame(25000, $data['summary']['sales']);
        self::assertSame(25000, $data['summary']['balance']);
        self::assertSame(25000, $data['summary']['period_sales']);
        self::assertStringContainsString('per_page=20', $data['debts']->url(1));
        self::assertStringContainsString('sort=period_sales', $data['debts']->url(1));
        self::assertSame('Thương hiệu thử', $data['settings']['brand_name']->value);
        self::assertSame('Khẩu hiệu thử', $data['settings']['slogan']->value);
        self::assertSame('123', $data['settings']['logo']->value);
        self::assertFalse($data['settings']->has('google_api_secret'));
    }

    public function test_sale_report_credit_filter_keeps_signed_table_totals(): void
    {
        $service = $this->service([
            $this->entry('CRM01', 'Chị Bình', 1),
            array_replace($this->entry('', 'Chị Bình', 2), ['type' => 'payment', 'amount' => 1300]),
        ]);
        $data = (new \App\Http\Controllers\MyCustomerDebtController)->index($this->request(['balance' => 'credit']), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(-300, $data['debts']->first()['balance']);
        self::assertSame(-300, $data['summary']['balance_net']);
        self::assertSame(0, $data['summary']['balance']);
    }

    public function test_sale_report_defaults_to_yesterday_and_all_history_remains_available(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-16 12:00:00');
        \Carbon\CarbonImmutable::setTestNow('2026-11-16 12:00:00');
        $service = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-15', 'amount' => 1000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-16', 'amount' => 2000]),
        ]);
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        $request = $this->request();
        $data = $controller->index($request, $service, new CustomerDebtMonitorController)->getData();
        self::assertSame('2026-11-15', $data['periodFrom']);
        self::assertSame('2026-11-15', $data['periodTo']);
        self::assertSame('2026-11-15', $request->input('from'));
        self::assertSame(1000, $data['summary']['period_sales']);
        self::assertSame(3000, $data['summary']['sales']);
        $all = $controller->index($this->request(['range' => 'all']), $service, new CustomerDebtMonitorController)->getData();
        self::assertSame(3000, $all['summary']['period_sales']);
        self::assertSame('2026-11-16', $all['periodTo']);
    }

    public function test_sale_stars_move_customer_from_later_page_to_top_and_can_be_cleared(): void
    {
        $entries = [];
        for ($id = 10; $id < 32; $id++) {
            DB::table('customers')->insert(['id' => $id, 'name' => 'Khách '.$id, 'customer_code' => 'OWN'.$id, 'assigned_to' => 7, 'current_owner_sale_id' => 7]);
            $entries[] = array_replace($this->entry('OWN'.$id, 'Khách '.$id, $id), ['amount' => $id === 31 ? 1 : 1000]);
        }
        $service = $this->service($entries);
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        $priorities = new \App\Services\CustomerDebtPriorityService;
        $load = fn ($query = []) => $controller->index($this->request($query + ['per_page' => 20]), $service, new CustomerDebtMonitorController)->getData();
        $target = $load(['page' => 2])['debts']->firstWhere('customer_id', 31);
        self::assertNotNull($target);
        $controller->updatePriority($this->request(['customer_id' => 31, 'source_key' => $target['key'], 'stars' => 5, 'priority_version' => $target['priority']['version']]), $service, new CustomerDebtMonitorController, $priorities);
        $updated = $load();
        self::assertSame(31, $updated['debts']->first()['customer_id']);
        self::assertSame(5, $updated['debts']->first()['priority']['stars']);
        self::assertSame(0, $priorities->effective($target, $priorities->records())['stars']);
        $otherSale = $this->user('sale'); $otherSale->id = 8;
        self::assertSame(0, $priorities->effective($target, $priorities->personalRecords($otherSale))['stars']);
        $controller->updatePriority($this->request(['customer_id' => 31, 'source_key' => $target['key'], 'stars' => 0, 'priority_version' => $updated['debts']->first()['priority']['version']]), $service, new CustomerDebtMonitorController, $priorities);
        self::assertNotSame(31, $load()['debts']->first()['customer_id']);
        self::assertSame(0, $load(['page' => 2])['debts']->firstWhere('customer_id', 31)['priority']['stars']);
    }

    public function test_sale_star_update_rejects_foreign_customer_and_rechecks_transferred_ownership(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải', 2)]);
        $groups = $service->summarize($service->snapshot()['entries']);
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        $priorities = new \App\Services\CustomerDebtPriorityService;
        $owned = $controller->index($this->request(), $service, new CustomerDebtMonitorController)->getData()['debts']->first();
        $foreign = collect($groups)->firstWhere('code', 'KH02');
        foreach ([['customer_id' => 2, 'source_key' => $foreign['key']], ['customer_id' => 2, 'source_key' => $owned['key']]] as $target) {
            try {
                $controller->updatePriority($this->request($target + ['stars' => 5, 'priority_version' => $owned['priority']['version']]), $service, new CustomerDebtMonitorController, $priorities);
                self::fail('Foreign priority was modified');
            } catch (HttpException $e) { self::assertSame(404, $e->getStatusCode()); }
        }
        DB::table('customers')->where('id', 1)->update(['current_owner_sale_id' => 8]);
        try {
            $controller->updatePriority($this->request(['customer_id' => 1, 'source_key' => $owned['key'], 'stars' => 5, 'priority_version' => $owned['priority']['version']]), $service, new CustomerDebtMonitorController, $priorities);
            self::fail('Former owner modified priority');
        } catch (HttpException $e) { self::assertSame(404, $e->getStatusCode()); }
        self::assertSame([], $priorities->personalRecords($this->user('sale')));
    }

    public function test_sale_stars_reject_stale_updates_and_invalid_rating(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $controller = new \App\Http\Controllers\MyCustomerDebtController;
        $priorities = new \App\Services\CustomerDebtPriorityService;
        $row = $controller->index($this->request(), $service, new CustomerDebtMonitorController)->getData()['debts']->first();
        $target = ['customer_id' => 1, 'source_key' => $row['key'], 'priority_version' => $row['priority']['version']];
        $controller->updatePriority($this->request($target + ['stars' => 3]), $service, new CustomerDebtMonitorController, $priorities);
        foreach ([0, 6] as $stars) {
            try {
                $controller->updatePriority($this->request($target + ['stars' => $stars]), $service, new CustomerDebtMonitorController, $priorities);
                self::fail('Invalid or stale stars accepted');
            } catch (\Illuminate\Validation\ValidationException $e) { self::assertNotEmpty($e->errors()); }
        }
        self::assertSame(3, $priorities->effective($row, $priorities->personalRecords($this->user('sale')))['stars']);
    }

    public function test_sale_refresh_uses_configured_google_source_and_returns_no_global_financial_data(): void
    {
        $sheets = $this->createMock(CustomerDebtSheetService::class);
        $tabs = [[], [], [], [], [], []];
        $sheets->expects(self::once())->method('fetch')->willReturn($tabs);
        $sheets->expects(self::once())->method('saveTabs')->with($tabs, 7, 'google')->willReturn(['id' => 'PRIVATE_BATCH_ID', 'amount' => 999999]);
        $request = $this->request(['source' => 'paste', 'sales_text' => 'UNTRUSTED', 'customer_id' => 2]);
        $request->headers->set('Accept', 'application/json');
        $response = (new \App\Http\Controllers\MyCustomerDebtController)->refresh($request, $sheets);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['message' => 'Đã cập nhật dữ liệu từ Google Sheet.'], $response->getData(true));
    }

    public function test_sale_refresh_failure_keeps_existing_data_and_hides_internal_error_details(): void
    {
        $sheets = $this->createMock(CustomerDebtSheetService::class);
        $sheets->expects(self::once())->method('fetch')->willThrowException(new \RuntimeException('PRIVATE_SOURCE_DETAIL'));
        $sheets->expects(self::never())->method('saveTabs');
        $request = $this->request(); $request->headers->set('Accept', 'application/json');
        $response = (new \App\Http\Controllers\MyCustomerDebtController)->refresh($request, $sheets);
        self::assertSame(503, $response->getStatusCode());
        self::assertStringNotContainsString('PRIVATE_SOURCE_DETAIL', $response->getContent());
    }

    public function test_sale_refresh_route_is_protected_and_other_roles_cannot_trigger_sync(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('pages.my_customer_debt.refresh');
        self::assertSame(['POST'], $route->methods());
        self::assertContains('auth', $route->gatherMiddleware());
        self::assertContains('web', $route->gatherMiddleware());
        self::assertContains('throttle:2,1', $route->gatherMiddleware());
        $sheets = $this->createMock(CustomerDebtSheetService::class);
        $sheets->expects(self::never())->method('fetch');
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\MyCustomerDebtController)->refresh($this->request([], 'shipper'), $sheets);
    }

    public function test_report_summary_separates_lifetime_and_period_totals_before_pagination(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $entries = [];
        for ($i = 1; $i <= 25; $i++) {
            $entries[] = array_replace($this->entry('STAT'.$i, 'Khách thống kê '.$i, 1), ['date' => '2026-10-01']);
            $entries[] = array_replace($this->entry('STAT'.$i, 'Khách thống kê '.$i, 2), ['date' => '2026-11-13', 'amount' => 200, 'gift' => 20]);
            $entries[] = array_replace($this->entry('STAT'.$i, 'Khách thống kê '.$i, 3), ['date' => '2026-11-01', 'type' => 'payment', 'amount' => 500]);
        }
        $entries[] = array_replace($this->entry('OTHER', 'Khách không mua trong kỳ'), ['date' => '2026-10-01', 'amount' => 9000]);
        $data = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request(['scope' => 'orders', 'from' => '2026-10-26', 'to' => '2026-11-13', 'per_page' => 20, 'page' => 2], 'admin'), $this->service($entries), new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertCount(5, $data['rows']);
        self::assertSame(25, $data['summary']['customers']);
        self::assertSame(30000, $data['summary']['sales']);
        self::assertSame(17000, $data['summary']['balance']);
        self::assertSame(5000, $data['period']['sales']);
        self::assertSame(500, $data['period']['gifts']);
        self::assertSame(12500, $data['period']['payments']);
        self::assertSame(0, $data['period']['increase']);
        self::assertTrue($data['hasPeriodFilter']);
        self::assertTrue($data['rows']->every(fn ($row) => $row['period_increase'] === 0));
        self::assertSame('2026-10-26', $data['period']['from']);
        self::assertSame('2026-11-13', $data['period']['to']);
    }

    public function test_report_period_payment_dates_are_inclusive_and_independent_of_purchase_scope(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $entries = [array_replace($this->entry('CRM01', 'Chị Bình'), ['date' => '2026-10-01', 'amount' => 1000])];
        foreach (['2026-10-25' => 10, '2026-10-26' => 20, '2026-11-13' => 30, '2026-11-14' => 40] as $date => $amount) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $amount), ['type' => 'payment', 'date' => $date, 'amount' => $amount]);
        }
        $data = (new \App\Http\Controllers\CustomerDebtReportController)->index($this->request(['scope' => 'all', 'from' => '2026-10-26', 'to' => '2026-11-13'], 'admin'), $this->service($entries), new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(1, $data['summary']['customers']);
        self::assertSame(1000, $data['summary']['sales']);
        self::assertSame(0, $data['period']['sales']);
        self::assertSame(50, $data['period']['payments']);
        self::assertSame(0, $data['period']['increase']);
        self::assertSame(0, $data['rows']->first()['period_increase']);
    }

    public function test_sheet_report_applies_opening_credit_to_new_debt(): void
    {
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-01', 'amount' => 1659631000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-02', 'type' => 'payment', 'amount' => 1700000000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-18', 'amount' => 42735000]),
        ];
        $request = $this->request(['scope' => 'orders', 'from' => '2026-11-10', 'to' => '2026-11-18'], 'admin');
        $data = (new \App\Http\Controllers\CustomerDebtReportController)->index($request, $this->service($entries), new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();

        self::assertSame(2366000, $data['rows']->first()['period_increase']);
        self::assertSame(2366000, $data['period']['increase']);
        self::assertSame(0, $data['period']['payments']);
    }

    public function test_negotiation_deduplicates_aliases_and_includes_customer_without_sheet_history(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('OLD02', 'Tên nguồn khác')]);
        foreach (array_keys($service->summarize($service->snapshot()['entries'])) as $key) {
            $service->mapCustomer($key, 1, 7);
        }
        $policies = new \App\Services\CustomerDebtPolicyService;
        $policies->save(['customer_id' => 1], ['allowed_orders' => 2, 'credit_limit' => 10000, 'due_days' => 7], 7, true, 0);
        $policies->save(['customer_id' => 2], ['allowed_orders' => 1, 'credit_limit' => 5000, 'due_days' => 14], 7, true, 0);
        $data = (new \App\Http\Controllers\CustomerDebtNegotiationController)->index($this->request([], 'admin'), $service, new \App\Services\CustomerDebtReportService)->getData();
        self::assertSame(2, $data['rows']->total());
        self::assertSame(15000, $data['summary']['max_debt']);
        self::assertSame(2, $data['summary']['configured']);
        self::assertSame(2, $data['rows']->firstWhere('customer_id', 1)['policy']['allowed_orders']);
        self::assertSame(7, $data['rows']->firstWhere('customer_id', 1)['policy']['due_days']);
        self::assertSame(5000, $data['rows']->firstWhere('customer_id', 2)['max_debt']);
        self::assertFalse($data['rows']->firstWhere('customer_id', 2)['data_available']);
    }

    public function test_negotiation_totals_cover_all_pages_and_filters_apply_before_summing(): void
    {
        $entries = [];
        for ($i = 1; $i <= 25; $i++) {
            $entries[] = $this->entry('TERM'.$i, 'Khách đàm phán '.$i);
        }
        $service = $this->service($entries);
        $groups = $service->summarize($entries);
        $group = reset($groups);
        (new \App\Services\CustomerDebtPolicyService)->save($group, ['allowed_orders' => 3, 'credit_limit' => 12345, 'due_days' => 10], 7, true, 0);
        $controller = new \App\Http\Controllers\CustomerDebtNegotiationController;
        $data = $controller->index($this->request(['per_page' => 20, 'page' => 2], 'admin'), $service, new \App\Services\CustomerDebtReportService)->getData();
        self::assertSame(27, $data['summary']['customers']);
        self::assertCount(7, $data['rows']);
        self::assertSame(12345, $data['summary']['max_debt']);
        $configured = $controller->index($this->request(['terms' => 'configured'], 'admin'), $service, new \App\Services\CustomerDebtReportService)->getData();
        self::assertSame(1, $configured['rows']->total());
        self::assertSame(12345, $configured['summary']['max_debt']);
        $defaults = $controller->index($this->request(['terms' => 'default'], 'admin'), $service, new \App\Services\CustomerDebtReportService)->getData();
        self::assertSame(26, $defaults['rows']->total());
        self::assertSame(0, $defaults['summary']['max_debt']);
        self::assertTrue($defaults['rows']->every(fn ($r) => $r['policy']['allowed_orders'] === 1 && $r['policy']['due_days'] === 1));
    }

    public function test_debt_report_displays_agreed_ceiling_and_days_for_customers_without_source_rows(): void
    {
        (new \App\Services\CustomerDebtPolicyService)->save(['customer_id' => 1], ['allowed_orders' => 2, 'credit_limit' => 100000000, 'due_days' => 4], 7, true, 0);
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $data = $controller->index($this->request(['balance' => 'all'], 'admin'), $this->service([]), new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(100000000, $data['rows']->firstWhere('customer_id', 1)['policy']['credit_limit']);
        self::assertSame(4, $data['rows']->firstWhere('customer_id', 1)['policy']['due_days']);
        self::assertSame(0, $data['rows']->firstWhere('customer_id', 2)['policy']['credit_limit']);
        self::assertSame(1, $data['rows']->firstWhere('customer_id', 2)['policy']['due_days']);
        self::assertNull($data['rows']->firstWhere('customer_id', 1)['period_increase']);
        self::assertFalse($data['hasPeriodFilter']);
    }

    public function test_sales_cannot_view_global_negotiated_limits(): void
    {
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\CustomerDebtNegotiationController)->index($this->request(), $this->service([]), new \App\Services\CustomerDebtReportService);
    }

    public function test_priority_stars_persist_and_sort_before_selected_report_column(): void
    {
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), array_replace($this->entry('KH02', 'Anh Hải'), ['amount' => 2000])]);
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $priorities = new \App\Services\CustomerDebtPriorityService;
        $report = fn () => $controller->index($this->request(['sort' => 'balance', 'direction' => 'asc'], 'admin'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        $initial = $report();
        $version = $initial['rows']->firstWhere('customer_id', 2)['priority']['version'];
        $controller->updatePriority($this->request(['customer_id' => 2, 'stars' => 5, 'priority_version' => $version], 'accountant'), $service, $priorities);
        $starred = $report();
        self::assertSame(2, $starred['rows']->first()['customer_id']);
        self::assertSame(5, $starred['rows']->first()['priority']['stars']);
        $controller->updatePriority($this->request(['customer_id' => 2, 'stars' => 0, 'priority_version' => $starred['rows']->first()['priority']['version']], 'admin'), $service, $priorities);
        self::assertSame(1, $report()['rows']->first()['customer_id']);
        self::assertSame(0, $report()['rows']->firstWhere('customer_id', 2)['priority']['stars']);
    }

    public function test_report_sorts_todays_reminders_above_starred_customers(): void
    {
        $this->collectionClock('2026-11-17 14:00:00');
        $sheets = $this->service([$this->entry('CRM01', 'Chị Bình', 1), $this->entry('KH02', 'Anh Hải', 2)]);
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $collections = new \App\Services\DebtCollectionService;
        $priorities = new \App\Services\CustomerDebtPriorityService;
        $report = fn () => $controller->index($this->request([], 'admin'), $sheets, new \App\Services\CustomerDebtReportService, $collections)->getData()['rows'];
        $version = $report()->firstWhere('customer_id', 1)['priority']['version'];
        $controller->updatePriority($this->request(['customer_id' => 1, 'stars' => 5, 'priority_version' => $version], 'admin'), $sheets, $priorities);
        $controller->remind($this->request($this->reminderData(['customer_id' => 1, 'target_amount' => 500]), 'admin'), $sheets, $collections);

        $this->collectionClock('2026-11-18 14:00:00');
        $controller->remind($this->request($this->reminderData(['customer_id' => 2, 'target_amount' => 500]), 'admin'), $sheets, $collections);
        $rows = $report();
        self::assertSame([2, 1], $rows->pluck('customer_id')->all());
        self::assertSame([true, false], $rows->pluck('reminded_today')->all());

        $this->collectionClock('2026-11-19 14:00:00');
        self::assertSame([1, 2], $report()->pluck('customer_id')->all());
    }

    public function test_source_priority_moves_a_later_page_customer_to_top_and_survives_mapping(): void
    {
        $entries = [];
        for ($i = 1; $i <= 25; $i++) {
            $entries[] = $this->entry('PIN'.$i, 'Khách ưu tiên '.$i);
        }
        $service = $this->service($entries);
        $group = array_values($service->summarize($entries))[24];
        $priorities = new \App\Services\CustomerDebtPriorityService;
        $priorities->save($group, 4, $priorities->effective($group, [])['version'], $this->user('admin'));
        $controller = new \App\Http\Controllers\CustomerDebtReportController;
        $data = $controller->index($this->request(['per_page' => 20], 'admin'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame($group['key'], $data['rows']->first()['key']);
        self::assertSame(4, $data['rows']->first()['priority']['stars']);
        self::assertCount(20, $data['rows']);
        $service->mapCustomer($group['key'], 1, 7);
        $mapped = $controller->index($this->request([], 'admin'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(1, $mapped['rows']->first()['customer_id']);
        self::assertSame(4, $mapped['rows']->first()['priority']['stars']);
        $row = $mapped['rows']->first();
        $controller->updatePriority($this->request(['customer_id' => 1, 'stars' => 0, 'priority_version' => $row['priority']['version']], 'admin'), $service, $priorities);
        $cleared = $controller->index($this->request([], 'admin'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(0, $cleared['rows']->firstWhere('customer_id', 1)['priority']['stars']);
    }

    public function test_priority_rejects_stale_changes_and_leaves_the_latest_value(): void
    {
        $priorities = new \App\Services\CustomerDebtPriorityService;
        $row = ['customer_id' => 1, 'source_keys' => []];
        $version = $priorities->effective($row, [])['version'];
        $priorities->save($row, 3, $version, $this->user('admin'));
        try {
            $priorities->save($row, 1, $version, $this->user('admin'));
            self::fail('Stale priority overwrote latest value');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertSame(3, $priorities->effective($row, $priorities->records())['stars']);
        }
    }

    public function test_sales_cannot_change_report_follow_up_priority(): void
    {
        $this->expectException(HttpException::class);
        (new \App\Http\Controllers\CustomerDebtReportController)->updatePriority($this->request(['customer_id' => 1, 'stars' => 5, 'priority_version' => str_repeat('0', 64)]), $this->service([]), new \App\Services\CustomerDebtPriorityService);
    }

    public function test_cycle_statements_carry_balances_and_keep_closed_documents_frozen(): void
    {
        $this->collectionClock('2026-11-23 12:00:00');
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-12', 'amount' => 16000000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-18', 'type' => 'payment', 'amount' => 15000000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-19', 'amount' => 26000000]),
        ];
        $sheets = $this->service($entries);
        $policies = app(\App\Services\CustomerDebtPolicyService::class);
        $policies->save(['customer_id' => 1], [
            'payment_mode' => 'cycle', 'cycle_type' => 'weekly', 'cycle_close_weekday' => 1, 'cycle_weekday' => 5,
        ], 7, true, 0);
        $controller = new CustomerDebtMonitorController;
        $cycles = app(\App\Services\CustomerDebtCycleStatementService::class);
        $query = ['system_customer' => 1, 'window' => 'all'];
        $first = $controller->index($this->request($query + ['cycle_date' => '2026-11-16'], 'accountant'), $sheets)->getData()['cyclePreview'];
        self::assertSame('2026-11-10', $first['from']);
        self::assertSame('2026-11-20', $first['due']);
        self::assertSame(16000000, $first['closing']);
        $firstResponse = $controller->closeCycle($this->request($query + [
            'cycle_date' => $first['to'], 'revision' => $first['revision'],
        ], 'accountant'), $sheets, $cycles);
        self::assertSame(303, $firstResponse->getStatusCode());

        $next = $controller->index($this->request($query, 'accountant'), $sheets)->getData()['cyclePreview'];
        self::assertSame('2026-11-17', $next['from']);
        self::assertSame('2026-11-23', $next['to']);
        self::assertSame(16000000, $next['opening']);
        self::assertSame(15000000, $next['opening_paid']);
        self::assertSame(1000000, $next['opening_remaining']);
        self::assertSame(26000000, $next['net_new']);
        self::assertSame(27000000, $next['closing']);
        $controller->closeCycle($this->request($query + [
            'cycle_date' => $next['to'], 'revision' => $next['revision'],
        ], 'accountant'), $sheets, $cycles);

        $entries[0]['amount'] += 100000;
        $changed = $controller->index($this->request($query, 'accountant'), $this->service($entries))->getData();
        $saved = $changed['cycleHistory']['2026-11-16'];
        self::assertSame(16000000, $cycles->statement($saved['ledger'], $saved['to'])['closing']);
        self::assertSame(100000, $changed['cyclePreview']['prior_correction']);
        self::assertFalse($changed['cyclePreview']['can_close']);
    }

    public function test_custom_cycle_ranges_only_assign_selected_bills_and_carry_unselected_bills(): void
    {
        $this->collectionClock('2026-11-23 12:00:00');
        $sheets = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2024-01-01', 'order_code' => 'A001', 'amount' => 16000000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2024-01-01', 'order_code' => 'A002', 'amount' => 2000000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-05', 'order_code' => 'A003', 'amount' => 26000000]),
            array_replace($this->entry('CRM01', 'Chị Bình', 4), ['date' => '2026-11-06', 'type' => 'payment', 'amount' => 15000000]),
        ]);
        app(\App\Services\CustomerDebtPolicyService::class)->save(['customer_id' => 1], [
            'payment_mode' => 'cycle', 'cycle_type' => 'weekly', 'cycle_close_weekday' => 2, 'cycle_weekday' => 5,
        ], 7, true, 0);
        $controller = new CustomerDebtMonitorController;
        $cycles = app(\App\Services\CustomerDebtCycleStatementService::class);
        $query = ['system_customer' => 1, 'window' => 'all'];
        $first = $controller->index($this->request($query + [
            'cycle_from' => '2024-01-01', 'cycle_date' => '2026-11-01',
        ], 'accountant'), $sheets)->getData()['cyclePreview'];
        self::assertCount(2, $first['bills']);
        $chosen = array_key_first($first['bills']);
        $controller->closeCycle($this->request($query + [
            'cycle_from' => $first['from'], 'cycle_date' => $first['to'],
            'cycle_bills' => json_encode([$chosen]), 'revision' => $first['revision'],
        ], 'accountant'), $sheets, $cycles);

        $detail = $controller->index($this->request($query + [
            'cycle_from' => '2026-11-02', 'cycle_date' => '2026-11-09',
        ], 'accountant'), $sheets)->getData();
        $saved = $detail['cycleHistory']['2026-11-01'];
        self::assertSame(16000000, $saved['closing']);
        self::assertSame([$chosen], $saved['bill_keys']);
        $next = $detail['cyclePreview'];
        self::assertSame('2026-11-02', $next['from']);
        self::assertSame(0, $next['prior_correction']);
        self::assertSame(['A002', 'A003'], array_column($next['bills'], 'label'));
        self::assertCount(3, $next['bill_catalog']);
        self::assertSame('Đã gán vào kỳ đã chốt', $next['bill_catalog'][$chosen]['unavailable']);
        self::assertSame(29000000, $next['closing']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->closeCycle($this->request($query + [
            'cycle_from' => $next['from'], 'cycle_date' => $next['to'],
            'cycle_bills' => json_encode([$chosen]), 'revision' => $next['revision'],
        ], 'accountant'), $sheets, $cycles);
    }

    public function test_named_cycle_draft_starts_empty_and_saves_bills_before_closing(): void
    {
        $this->collectionClock('2026-11-23 12:00:00');
        $sheets = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình'), ['date' => '2026-11-22', 'order_code' => 'A001']),
        ]);
        app(\App\Services\CustomerDebtPolicyService::class)->save(['customer_id' => 1], [
            'payment_mode' => 'cycle', 'cycle_type' => 'weekly', 'cycle_close_weekday' => 2, 'cycle_weekday' => 5,
        ], 7, true, 0);
        $controller = new CustomerDebtMonitorController;
        $cycles = app(\App\Services\CustomerDebtCycleStatementService::class);
        $query = ['system_customer' => 1, 'window' => 'all'];
        $detail = fn (array $extra = []) => $controller->index($this->request($query + $extra, 'accountant'), $sheets)->getData();
        $dates = ['cycle_from' => '2026-11-18', 'cycle_date' => '2026-11-24'];
        $draftInput = $dates + ['cycle_name' => 'Kỳ công nợ 1'];
        $preview = $detail($dates)['cyclePreview'];
        $controller->closeCycle($this->request($query + $draftInput + [
            'cycle_action' => 'create', 'revision' => $preview['revision'],
        ], 'accountant'), $sheets, $cycles);
        $afterCreate = $detail();
        self::assertSame('Kỳ công nợ 1', $afterCreate['cycleDraft']['name']);
        self::assertSame([], $afterCreate['cycleDraft']['bill_keys']);
        self::assertFalse($afterCreate['cyclePreview']['can_close']);
        self::assertSame(0, $afterCreate['cyclePreview']['closing']);

        $bill = array_key_first($afterCreate['cyclePreview']['bills']);
        $controller->closeCycle($this->request($query + $draftInput + [
            'cycle_action' => 'save', 'draft_version' => $afterCreate['cycleDraft']['version'],
            'revision' => $afterCreate['cyclePreview']['revision'], 'cycle_bills' => json_encode([$bill]),
        ], 'accountant'), $sheets, $cycles);

        $this->collectionClock('2026-11-24 12:00:00');
        $saved = $detail();
        self::assertSame([$bill], $saved['cycleDraft']['bill_keys']);
        self::assertSame(1000, $saved['cyclePreview']['closing']);
        $controller->closeCycle($this->request($query + $draftInput + [
            'cycle_action' => 'close', 'draft_version' => $saved['cycleDraft']['version'],
            'revision' => $saved['cyclePreview']['revision'], 'cycle_bills' => json_encode([$bill]),
        ], 'accountant'), $sheets, $cycles);
        $closed = $detail();
        self::assertNull($closed['cycleDraft']);
        self::assertSame('Kỳ công nợ 1', $closed['cycleHistory']['2026-11-24']['name']);
        self::assertSame([$bill], $closed['cycleHistory']['2026-11-24']['bill_keys']);
    }

    public function test_automatic_cycle_collects_bills_preserves_manual_adjustment_and_accepts_evidence(): void
    {
        $this->collectionClock('2026-11-23 12:00:00');
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-19', 'order_code' => 'A001']),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-23', 'order_code' => 'A002']),
        ];
        $sheets = $this->service($entries);
        app(\App\Services\CustomerDebtPolicyService::class)->save(['customer_id' => 1], [
            'payment_mode' => 'cycle', 'cycle_type' => 'weekly', 'cycle_close_weekday' => 1, 'cycle_weekday' => 5,
        ], 7, true, 0);
        $controller = new CustomerDebtMonitorController;
        $cycles = app(\App\Services\CustomerDebtCycleStatementService::class);
        $documents = app(\App\Services\CustomerDebtCycleDocuments::class);
        $query = ['system_customer' => 1, 'window' => 'all', 'debt_section' => 'cycles'];
        $controller->autoCycle($this->request($query, 'accountant'), $sheets, $cycles);
        $auto = $controller->index($this->request($query, 'accountant'), $sheets)->getData();
        self::assertTrue($auto['cycleDraft']['automatic']);
        self::assertSame('2026-11-17', $auto['cyclePreview']['from']);
        self::assertSame('2026-11-23', $auto['cyclePreview']['to']);
        self::assertCount(2, $auto['cyclePreview']['bill_keys']);
        self::assertSame(2000, $auto['cyclePreview']['closing']);

        $data = $query + [
            'cycle_name' => $auto['cycleDraft']['name'], 'draft_version' => $auto['cycleDraft']['version'],
            'cycle_date' => $auto['cyclePreview']['to'], 'cycle_from' => $auto['cyclePreview']['from'],
            'revision' => $auto['cyclePreview']['revision'], 'cycle_bills' => json_encode($auto['cyclePreview']['bill_keys']),
            'cycle_adjustment' => 150, 'cycle_adjustment_reason' => 'Chênh lệch đối chiếu đã xác nhận',
        ];
        $controller->closeCycle($this->request($data + ['cycle_action' => 'save'], 'accountant'), $sheets, $cycles);
        $draft = $controller->index($this->request($query, 'accountant'), $sheets)->getData();
        self::assertSame(2150, $draft['cyclePreview']['closing']);
        $controller->closeCycle($this->request(array_replace($data, [
            'cycle_action' => 'close', 'draft_version' => $draft['cycleDraft']['version'],
        ]), 'accountant'), $sheets, $cycles);
        $after = $controller->index($this->request($query, 'accountant'), $sheets)->getData();
        $closed = $after['cycleHistory']['2026-11-23'];
        self::assertSame(2150, $closed['closing']);
        self::assertSame(2150, $after['cyclePreview']['opening']);
        self::assertSame(0, $after['cyclePreview']['prior_correction']);

        $disk = \Illuminate\Support\Facades\Storage::fake('local');
        $upload = $this->request([], 'accountant');
        $upload->files->set('documents', [\Illuminate\Http\UploadedFile::fake()->create('signed.pdf', 4, 'application/pdf')]);
        $controller->uploadCycleDocuments($upload, $closed['ledger'], $closed['to'], $cycles, $documents);
        $document = $documents->forLedger($closed['ledger'])[$closed['to']][0];
        $disk->assertExists($document['path']);
        $response = $controller->downloadCycleDocument($this->request([], 'admin'), $closed['ledger'], $closed['to'], $document['id'], $documents);
        self::assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        self::assertSame(2150, $cycles->statement($closed['ledger'], $closed['to'])['closing']);

        $this->expectException(HttpException::class);
        $controller->downloadCycleDocument($this->request([], 'sale'), $closed['ledger'], $closed['to'], $document['id'], $documents);
    }

    public function test_monthly_cycle_clamps_the_close_day_and_starts_after_the_previous_close(): void
    {
        $this->collectionClock('2026-02-28 12:00:00');
        $policy = [
            'payment_mode' => 'cycle', 'cycle_type' => 'monthly', 'cycle_close_monthday' => 31,
            'cycle_monthday' => 5, 'edit_version' => 1,
        ];
        $preview = app(\App\Services\CustomerDebtCycleStatementService::class)->preview([
            'customer_id' => 1, 'name' => 'Khách thử', 'policy' => $policy, 'entries' => [],
        ], []);
        self::assertSame('2026-02-01', $preview['from']);
        self::assertSame('2026-02-28', $preview['to']);
        self::assertSame('2026-03-05', $preview['due']);
    }

    public function test_debt_notes_are_shared_by_accounting_and_admin_per_customer(): void
    {
        $sheets = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải', 2)]);
        $controller = new CustomerDebtMonitorController;
        $customer = ['system_customer' => 1];
        $note = "Khách hẹn thanh toán thứ Sáu.\nKiểm tra lại sau khi nhận tiền.";
        $controller->saveNote($this->request($customer + [
            'note' => $note, 'revision' => hash('sha256', ''),
        ], 'accountant'), $sheets);

        self::assertSame($note, $controller->index($this->request($customer, 'admin'), $sheets)->getData()['debtNote']);
        $source = ['customer' => hash('sha256', 'code:CRM01'), 'window' => 'all'];
        foreach (['account', 'accountant', 'accounting'] as $role) {
            $accountingPage = $controller->index($this->request($source, $role), $sheets);
            self::assertSame('customer-debt.index', $accountingPage->name());
            self::assertSame($note, $accountingPage->getData()['debtNote']);
        }
        self::assertSame('', $controller->index($this->request(['system_customer' => 2], 'admin'), $sheets)->getData()['debtNote']);
        self::assertNull($controller->index($this->request($customer, 'sale'), $sheets)->getData()['debtNote']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->saveNote($this->request($customer + [
            'note' => 'Nội dung cũ', 'revision' => hash('sha256', ''),
        ], 'admin'), $sheets);
    }

    public function test_accounting_can_preview_bill_transition_and_split_unmatched_receipts_without_changing_balance(): void
    {
        $this->collectionClock('2026-11-20 12:00:00');
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-17', 'order_code' => 'DH001']),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-19', 'order_code' => 'DH002']),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-20', 'order_code' => 'DH003']),
            array_replace($this->entry('CRM01', 'Chị Bình', 4), ['date' => '2026-11-18', 'type' => 'payment', 'amount' => 400]),
            array_replace($this->entry('CRM01', 'Chị Bình', 5), ['type' => 'payment', 'amount' => 800, 'note' => 'Theo đợt']),
            array_replace($this->entry('CRM01', 'Chị Bình', 6), ['type' => 'payment', 'amount' => 750, 'note' => 'Khách trả gộp']),
        ];
        $sheets = $this->service($entries);
        $controller = new CustomerDebtMonitorController;
        $manual = app(\App\Services\CustomerDebtManualAllocationService::class);
        $allocator = app(\App\Services\CustomerDebtPaymentAllocation::class);
        $query = ['system_customer' => 1, 'window' => 'all'];
        $detail = fn (array $filters = []) => $controller->index($this->request($query + $filters, 'accountant'), $sheets)->getData();

        $preview = $detail(['transition_preview' => '2026-11-19', 'transition_method' => 'bill']);
        self::assertSame(600, $preview['settlementPreview']['remaining']);
        self::assertNull($preview['changeDate']);
        $controller->changeSettlementMethod($this->request($query + [
            'transition_date' => '2026-11-19', 'transition_method' => 'bill', 'revision' => $preview['allocationRevision'],
        ], 'accountant'), $sheets, $manual);
        $after = $detail();
        self::assertSame('2026-11-19', $after['changeDate']);
        self::assertSame($preview['closing'], $after['closing']);
        self::assertSame(0, $after['allocation']['lines']['nhatkybanhang:1']['remaining']);
        self::assertSame(1000, $after['allocation']['lines']['nhatkybanhang:2']['remaining']);
        self::assertSame(200, $after['allocation']['unmatched']['Dongtienhoatdong:5']['amount']);

        $paymentKey = 'Dongtienhoatdong:6';
        $form = $detail(['allocate' => $paymentKey, 'debt_section' => 'bill']);
        self::assertSame(750, $form['allocationForm']['available']);
        $allocation = [
            'payment_key' => $paymentKey, 'revision' => $form['allocationRevision'],
            'items' => [['target' => 'nhatkybanhang:2', 'amount' => 500], ['target' => 'nhatkybanhang:3', 'amount' => 200]],
        ];
        $response = $controller->saveManualAllocation($this->request($query + $allocation + ['debt_section' => 'bill'], 'accountant'), $sheets, $manual, $allocator);
        self::assertStringContainsString('debt_section=bill#debt-payment-periods', $response->getTargetUrl());
        $after = $detail();
        self::assertSame(50, $after['allocation']['unmatched'][$paymentKey]['amount']);
        self::assertSame(500, $after['allocation']['lines']['nhatkybanhang:2']['remaining']);
        self::assertSame(800, $after['allocation']['lines']['nhatkybanhang:3']['remaining']);
        self::assertSame($preview['closing'], $after['closing']);

        try {
            $controller->saveManualAllocation($this->request($query + $allocation, 'accountant'), $sheets, $manual, $allocator);
            self::fail('A stale allocation update was accepted');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertSame(50, $detail()['allocation']['unmatched'][$paymentKey]['amount']);
        }

        $editedEntries = $entries;
        $editedEntries[5]['note'] = 'Dòng thu đã sửa trên Sheet';
        $changed = $controller->index($this->request($query, 'accountant'), $this->service($editedEntries))->getData()['allocation'];
        self::assertArrayHasKey($paymentKey, $changed['invalidManual']);
        self::assertSame(750, $changed['unmatched'][$paymentKey]['amount']);

        $controller->saveManualAllocation($this->request($query + [
            'payment_key' => $paymentKey, 'cancel' => 1, 'revision' => $after['allocationRevision'],
        ], 'accountant'), $sheets, $manual, $allocator);
        self::assertSame(750, $detail()['allocation']['unmatched'][$paymentKey]['amount']);
        self::assertSame(2, DB::table('settings')->where('key', 'like', 'customer_debt_manual_allocation_audit_%')->count());
    }

    public function test_bill_reconciliation_confirms_both_grouping_directions_and_reserves_confirmed_amounts(): void
    {
        $this->collectionClock('2026-11-20 12:00:00');
        $entries = [];
        foreach ([1 => 'DH001', 2 => 'DH002', 3 => 'DH003'] as $row => $code) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $row), [
                'date' => '2026-11-19', 'order_code' => $code,
            ]);
        }
        foreach ([4 => 400, 5 => 600, 6 => 2000] as $row => $amount) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $row), [
                'type' => 'payment', 'amount' => $amount, 'note' => 'Khách chuyển khoản',
            ]);
        }
        $sheets = $this->service($entries);
        $controller = new CustomerDebtMonitorController;
        $manual = app(\App\Services\CustomerDebtManualAllocationService::class);
        $bills = app(\App\Services\CustomerDebtBillReconciliation::class);
        $allocator = app(\App\Services\CustomerDebtPaymentAllocation::class);
        $query = ['system_customer' => 1, 'window' => 'all', 'debt_section' => 'bill'];
        $detail = fn () => $controller->index($this->request($query, 'accountant'), $sheets)->getData();
        $submit = fn (array $data) => $controller->confirmBillAllocations(
            $this->request($query + $data, 'accountant'), $sheets, $manual, $bills, $allocator
        );

        $controller->changeSettlementMethod($this->request($query + [
            'transition_date' => '2026-11-19', 'transition_method' => 'bill', 'revision' => $detail()['allocationRevision'],
        ], 'accountant'), $sheets, $manual);

        $period = $detail()['billPeriods']['2026-11-19'];
        self::assertSame(3000, $period['bills']->sum('remaining'));
        self::assertSame(3000, $period['payments']->sum('pending'));
        $first = [
            'period_from' => '2026-11-19', 'bill_keys' => ['2026-11-19|DH001'], 'revision' => $detail()['allocationRevision'],
        ];
        $submit($first + ['payment_keys' => ['Dongtienhoatdong:4', 'Dongtienhoatdong:5']]);
        self::assertSame(2000, $detail()['billPeriods']['2026-11-19']['bills']->sum('remaining'));
        $submit([
            'period_from' => '2026-11-19', 'bill_keys' => ['2026-11-19|DH002', '2026-11-19|DH003'],
            'payment_keys' => ['Dongtienhoatdong:6'], 'revision' => $detail()['allocationRevision'],
        ]);
        $after = $detail();
        self::assertSame(0, $after['closing']);
        self::assertSame(0, $after['billPeriods']['2026-11-19']['bills']->sum('remaining'));
        self::assertSame(0, $after['billPeriods']['2026-11-19']['payments']->sum('pending'));
        self::assertCount(2, $after['billGroups']);
        $matched = $after['billPeriods']['2026-11-19']['payments']->where('matched', true);
        self::assertCount(3, $matched);
        self::assertSame(['DH002', 'DH003'], $matched['Dongtienhoatdong:6']['matched_bills']);

        $extra = array_replace($entries[3], ['row' => 7, 'amount' => 1000, 'note' => 'Bill 19/11/2026 | Mã đơn: DH001']);
        $refreshed = $controller->index($this->request($query, 'accountant'), $this->service([...$entries, $extra]))->getData();
        self::assertSame(1000, $refreshed['allocation']['unmatched']['Dongtienhoatdong:7']['amount']);
        self::assertTrue($refreshed['billGroups']->every('valid', true));
        self::assertFalse($refreshed['billPeriods']['2026-11-19']['payments']['Dongtienhoatdong:7']['matched']);

        $amended = $entries;
        $amended[3]['amount'] = 500;
        $refreshed = $controller->index($this->request($query, 'accountant'), $this->service($amended))->getData();
        self::assertSame(1000, $refreshed['allocation']['lines']['nhatkybanhang:1']['remaining']);
        self::assertArrayHasKey('Dongtienhoatdong:5', $refreshed['allocation']['invalidManual']);

        $submit(['cancel_group' => $after['billGroups']->keys()->first(), 'revision' => $after['allocationRevision']]);
        self::assertSame(1000, $detail()['billPeriods']['2026-11-19']['bills']->sum('remaining'));
    }

    public function test_bill_reconciliation_carries_surplus_and_shortfall_between_independent_allocations(): void
    {
        $this->collectionClock('2026-11-20 12:00:00');
        $entries = [];
        foreach ([1 => ['sale', 1000], 2 => ['sale', 1000], 3 => ['payment', 1200], 4 => ['payment', 800]] as $row => [$type, $amount]) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $row), [
                'date' => '2026-11-20', 'type' => $type, 'amount' => $amount, 'order_code' => 'B'.$row,
            ]);
        }
        $controller = new CustomerDebtMonitorController;
        $sheets = $this->service($entries);
        $manual = app(\App\Services\CustomerDebtManualAllocationService::class);
        $bills = app(\App\Services\CustomerDebtBillReconciliation::class);
        $allocator = app(\App\Services\CustomerDebtPaymentAllocation::class);
        $query = ['system_customer' => 1, 'window' => 'all', 'debt_section' => 'bill'];
        $detail = fn () => $controller->index($this->request($query, 'accountant'), $sheets)->getData();
        $controller->changeSettlementMethod($this->request($query + [
            'transition_date' => '2026-11-20', 'transition_method' => 'bill', 'revision' => $detail()['allocationRevision'],
        ], 'accountant'), $sheets, $manual);
        $submit = fn (array $data) => $controller->confirmBillAllocations(
            $this->request($query + $data + ['revision' => $detail()['allocationRevision']], 'accountant'), $sheets, $manual, $bills, $allocator
        );

        $submit(['period_from' => '2026-11-20', 'bill_keys' => ['2026-11-20|B1'], 'payment_keys' => ['Dongtienhoatdong:3']]);
        $after = $detail();
        $first = $after['billGroups']->keys()->first();
        self::assertSame(1, $after['billGroups'][$first]['number']);
        self::assertSame(200, $after['billGroups'][$first]['surplus']);
        self::assertSame(['2026-11-20|B1'], $after['billGroups'][$first]['bill_keys']);
        self::assertSame(200, $after['allocation']['unmatched']['Dongtienhoatdong:3']['amount']);
        self::assertSame(1000, $after['allocation']['lines']['nhatkybanhang:2']['remaining']);

        $submit(['period_from' => '2026-11-20', 'bill_keys' => ['2026-11-20|B2'], 'payment_keys' => ['Dongtienhoatdong:3']]);
        $after = $detail();
        self::assertSame(800, $after['allocation']['lines']['nhatkybanhang:2']['remaining']);
        self::assertSame(1200, $after['billPeriods']['2026-11-20']['payments']['Dongtienhoatdong:3']['allocated']);
        $submit(['period_from' => '2026-11-20', 'bill_keys' => ['2026-11-20|B2'], 'payment_keys' => ['Dongtienhoatdong:4']]);
        self::assertSame(0, $detail()['allocation']['lines']['nhatkybanhang:2']['remaining']);

        $response = $submit(['cancel_group' => $first]);
        self::assertStringEndsWith('#debt-bill-reopened', $response->getTargetUrl());
        self::assertSame(['2026-11-20|B1'], session('bill_reopened.bill_keys'));
        self::assertSame(['Dongtienhoatdong:3'], session('bill_reopened.payment_keys'));
        $after = $detail();
        self::assertSame(1000, $after['allocation']['lines']['nhatkybanhang:1']['remaining']);
        self::assertSame(1000, $after['allocation']['unmatched']['Dongtienhoatdong:3']['amount']);
        self::assertSame(0, $after['allocation']['lines']['nhatkybanhang:2']['remaining']);
        self::assertCount(2, $after['billGroups']);
        self::assertTrue($after['billGroups']->every('valid', true));
        self::assertSame([2, 3], $after['billGroups']->pluck('number')->all());

        $submit(['period_from' => '2026-11-20', 'bill_keys' => ['2026-11-20|B1'], 'payment_keys' => ['Dongtienhoatdong:3']]);
        self::assertSame([2, 3, 4], $detail()['billGroups']->pluck('number')->all());
    }

    public function test_bill_tab_automatically_marks_only_fully_allocated_bill_payments(): void
    {
        $this->collectionClock('2026-11-20 12:00:00');
        $entries = [];
        foreach ([1 => 'A001', 2 => 'A002'] as $row => $code) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $row), [
                'date' => '2026-11-19', 'order_code' => $code,
            ]);
        }
        foreach ([3 => [1000, 'A001'], 4 => [1200, 'A002'], 5 => [300, 'A003']] as $row => [$amount, $code]) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $row), [
                'type' => 'payment', 'amount' => $amount, 'note' => 'Bill 19/11/2026 | Mã đơn: '.$code,
            ]);
        }
        $controller = new CustomerDebtMonitorController;
        $sheets = $this->service($entries);
        $query = ['system_customer' => 1, 'window' => 'all', 'debt_section' => 'bill'];
        $detail = fn () => $controller->index($this->request($query, 'accountant'), $sheets)->getData();
        $controller->changeSettlementMethod($this->request($query + [
            'transition_date' => '2026-11-19', 'transition_method' => 'bill', 'revision' => $detail()['allocationRevision'],
        ], 'accountant'), $sheets, app(\App\Services\CustomerDebtManualAllocationService::class));

        $payments = $detail()['billPeriods']['2026-11-19']['payments'];
        self::assertSame(['Dongtienhoatdong:3'], $payments->where('matched', true)->keys()->all());
        self::assertSame(['A001'], $payments['Dongtienhoatdong:3']['matched_bills']);
        self::assertSame(200, $payments['Dongtienhoatdong:4']['pending']);
        self::assertSame(300, $payments['Dongtienhoatdong:5']['pending']);
    }

    public function test_bill_tab_bulk_confirms_unique_same_day_exact_pairs(): void
    {
        $this->collectionClock('2026-11-20 12:00:00');
        $entries = [];
        foreach ([1 => 1000, 2 => 700, 3 => 500, 4 => 500, 5 => 2964500] as $row => $amount) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $row), [
                'date' => '2026-11-20', 'amount' => $amount, 'order_code' => 'B'.$row,
            ]);
        }
        foreach ([6 => 700, 7 => 1000, 8 => 500, 9 => 2960000] as $row => $amount) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $row), [
                'date' => '2026-11-20', 'type' => 'payment', 'amount' => $amount,
            ]);
        }
        $controller = new CustomerDebtMonitorController;
        $sheets = $this->service($entries);
        $manual = app(\App\Services\CustomerDebtManualAllocationService::class);
        $query = ['system_customer' => 1, 'window' => 'all', 'debt_section' => 'bill'];
        $detail = fn () => $controller->index($this->request($query, 'accountant'), $sheets)->getData();
        $controller->changeSettlementMethod($this->request($query + [
            'transition_date' => '2026-11-20', 'transition_method' => 'bill', 'revision' => $detail()['allocationRevision'],
        ], 'accountant'), $sheets, $manual);
        $before = $detail();
        self::assertSame([
            'Dongtienhoatdong:6' => '2026-11-20|B2',
            'Dongtienhoatdong:7' => '2026-11-20|B1',
        ], $before['billPeriods']['2026-11-20']['exact_pairs']);
        $controller->confirmBillAllocations($this->request($query + [
            'period_from' => '2026-11-20', 'match_exact' => '1', 'revision' => $before['allocationRevision'],
        ], 'accountant'), $sheets, $manual, app(\App\Services\CustomerDebtBillReconciliation::class), app(\App\Services\CustomerDebtPaymentAllocation::class));
        $after = $detail();
        $period = $after['billPeriods']['2026-11-20'];
        self::assertSame(['B2'], $period['payments']['Dongtienhoatdong:6']['matched_bills']);
        self::assertSame(['B1'], $period['payments']['Dongtienhoatdong:7']['matched_bills']);
        self::assertSame(500, $period['payments']['Dongtienhoatdong:8']['pending']);
        self::assertSame(2960000, $period['payments']['Dongtienhoatdong:9']['pending']);
        self::assertCount(1, $after['billGroups']);
        self::assertSame(1700, $after['billGroups']->first()['amount']);
        self::assertSame([], $period['exact_pairs']);
    }

    public function test_dated_payment_forms_switch_between_bill_cycle_and_batch(): void
    {
        $this->collectionClock('2026-11-26 12:00:00');
        $sheets = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình'), ['date' => '2026-11-12', 'order_code' => 'A001']),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-24', 'order_code' => 'A002']),
        ]);
        $monitor = new CustomerDebtMonitorController;
        $manual = app(\App\Services\CustomerDebtManualAllocationService::class);
        $query = ['system_customer' => 1, 'window' => 'all'];
        foreach ([['2026-11-17', '2026-11-20', 'bill'], ['2026-11-21', '2026-11-30', 'cycle']] as [$from, $to, $method]) {
            $preview = $monitor->index($this->request($query + [
                'transition_preview' => $from, 'transition_end' => $to, 'transition_method' => $method,
            ], 'accountant'), $sheets)->getData();
            self::assertArrayNotHasKey('error', $preview['settlementPreview']);
            $monitor->changeSettlementMethod($this->request($query + [
                'transition_date' => $from, 'transition_end' => $to, 'transition_method' => $method,
                'revision' => $preview['allocationRevision'],
            ], 'accountant'), $sheets, $manual);
        }
        $result = $monitor->index($this->request($query, 'accountant'), $sheets)->getData();
        self::assertSame('cycle', $result['currentSettlementMethod']);
        self::assertSame([
            ['from' => '2026-11-12', 'to' => '2026-11-16', 'method' => 'batch'],
            ['from' => '2026-11-17', 'to' => '2026-11-20', 'method' => 'bill'],
            ['from' => '2026-11-21', 'to' => '2026-11-30', 'method' => 'cycle'],
            ['from' => '2026-12-01', 'to' => null, 'method' => 'batch'],
        ], $result['paymentPeriods']);
        self::assertSame('batch', \App\Services\CustomerDebtPolicyService::currentSettlementMethod($result['selected']['policy'], '2026-12-01'));
        self::assertNotNull($result['cyclePreview']);
        self::assertGreaterThanOrEqual('2026-11-21', $result['cyclePreview']['from']);
        self::assertLessThanOrEqual('2026-11-30', $result['cyclePreview']['to']);
    }

    public function test_settlement_changes_can_return_to_batch_and_keep_previous_bill_allocations(): void
    {
        $this->collectionClock('2026-11-20 12:00:00');
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-16', 'order_code' => 'DH001']),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-17', 'order_code' => 'DH002']),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-19', 'order_code' => 'DH003']),
            array_replace($this->entry('CRM01', 'Chị Bình', 4), ['date' => '2026-11-18', 'type' => 'payment', 'amount' => 300, 'note' => 'Bill 17/11/2026 | Mã đơn: DH002']),
            array_replace($this->entry('CRM01', 'Chị Bình', 5), ['type' => 'payment', 'amount' => 1100]),
            array_replace($this->entry('CRM01', 'Chị Bình', 6), ['type' => 'payment', 'amount' => 250, 'note' => 'Bill 17/11/2026 | Mã đơn: DH002']),
        ];
        $sheets = $this->service($entries);
        $controller = new CustomerDebtMonitorController;
        $manual = app(\App\Services\CustomerDebtManualAllocationService::class);
        $query = ['system_customer' => 1, 'window' => 'all'];
        $detail = fn (array $filters = []) => $controller->index($this->request($query + $filters, 'accountant'), $sheets)->getData();
        foreach (['2026-11-17' => 'bill', '2026-11-19' => 'batch'] as $date => $method) {
            $preview = $detail(['transition_preview' => $date, 'transition_method' => $method]);
            self::assertArrayNotHasKey('error', $preview['settlementPreview']);
            $controller->changeSettlementMethod($this->request($query + [
                'transition_date' => $date, 'transition_method' => $method, 'revision' => $preview['allocationRevision'],
            ], 'accountant'), $sheets, $manual);
        }
        $after = $detail();
        self::assertSame('batch', $after['currentSettlementMethod']);
        self::assertSame('2026-11-19', $after['changeDate']);
        self::assertSame(1000, $after['allocation']['lines']['nhatkybanhang:1']['remaining']);
        self::assertSame(450, $after['allocation']['lines']['nhatkybanhang:2']['remaining']);
        self::assertSame(0, $after['allocation']['lines']['nhatkybanhang:3']['remaining']);
        self::assertSame(100, $after['allocation']['unmatched']['Dongtienhoatdong:5']['amount']);
        self::assertSame(1350, $after['closing']);

        $historical = $detail(['report_from' => '2026-11-16', 'report_to' => '2026-11-18']);
        self::assertSame('bill', $historical['currentSettlementMethod']);
        self::assertSame(700, $historical['allocation']['lines']['nhatkybanhang:2']['remaining']);

        $policies = app(\App\Services\CustomerDebtPolicyService::class);
        $legacy = $policies->effective(['customer_id' => 1], ['customer_1' => ['settlement_method' => 'mixed', 'bill_from' => '2026-11-17']]);
        self::assertSame(['2026-11-17' => 'bill'], $legacy['settlement_changes']);
    }

    public function test_bill_allocation_matches_date_and_code_and_leaves_unidentified_receipts_unallocated(): void
    {
        $entries = [
            array_replace($this->entry('CRM01', 'Chị Bình', 1), ['date' => '2026-11-12', 'order_code' => 'DH123']),
            array_replace($this->entry('CRM01', 'Chị Bình', 2), ['date' => '2026-11-13', 'order_code' => 'DH124']),
            array_replace($this->entry('CRM01', 'Chị Bình', 3), ['date' => '2026-11-14', 'type' => 'payment', 'amount' => 400, 'note' => 'Bill 13/11/2026 | Mã đơn: DH124']),
            array_replace($this->entry('CRM01', 'Chị Bình', 4), ['date' => '2026-11-14', 'type' => 'payment', 'amount' => 300, 'note' => 'Thu tiền hàng không có mã']),
            array_replace($this->entry('CRM01', 'Chị Bình', 5), ['date' => '2026-11-14', 'type' => 'payment', 'amount' => 100, 'note' => 'Bill 12/11/2026 | Mã đơn: DH124']),
            array_replace($this->entry('CRM01', 'Chị Bình', 6), ['date' => '2026-11-15', 'type' => 'payment', 'amount' => 500, 'note' => 'Theo đợt']),
        ];

        $service = new \App\Services\CustomerDebtPaymentAllocation;
        $batch = $service->reconcile($entries, 'batch');
        self::assertSame(1, $batch['paidThrough']['row']);
        self::assertSame(700, $batch['firstOpen']['remaining']);

        $bill = $service->reconcile($entries, 'bill');
        self::assertSame(0, $bill['lines']['nhatkybanhang:1']['paid']);
        self::assertSame(400, $bill['lines']['nhatkybanhang:2']['paid']);
        self::assertCount(3, $bill['unmatched']);

        $mixed = $service->reconcile($entries, 'mixed');
        self::assertSame(500, $mixed['lines']['nhatkybanhang:1']['paid']);
        self::assertSame(400, $mixed['lines']['nhatkybanhang:2']['paid']);
        self::assertCount(2, $mixed['unmatched']);
    }

    public function test_debt_threshold_defaults_to_100000_on_both_lists_and_supports_larger_pages(): void
    {
        $service = $this->service([
            array_replace($this->entry('CRM01', 'Chị Bình'), ['amount' => 100000]),
            array_replace($this->entry('KH02', 'Anh Hải', 2), ['amount' => 100001]),
        ]);
        $report = new \App\Http\Controllers\CustomerDebtReportController;
        $mine = new \App\Http\Controllers\MyCustomerDebtController;
        $monitor = new CustomerDebtMonitorController;
        $request = $this->request(['per_page' => 200], 'admin');
        $request->query->remove('min_debt');
        $rows = $report->index($request, $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData()['rows'];
        self::assertSame(['Anh Hải'], $rows->pluck('name')->all());
        self::assertSame(200, $rows->perPage());

        $request = $this->request(['per_page' => 300], 'sale');
        $request->query->remove('min_debt');
        $rows = $mine->index($request, $service, $monitor)->getData()['debts'];
        self::assertSame(0, $rows->total());
        self::assertSame(300, $rows->perPage());
        $rows = $mine->index($this->request(['min_debt' => 99999]), $service, $monitor)->getData()['debts'];
        self::assertSame(['Chị Bình'], $rows->pluck('name')->all());
        $rows = $report->index($this->request(['min_debt' => 900000, 'balance' => 'all'], 'admin'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData()['rows'];
        self::assertTrue($rows->contains('name', 'Chị Bình'));
    }

    public function test_debt_report_allows_accounting_and_deduplicates_linked_sources(): void
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
        $accounting = $controller->index($this->request([], 'accountant'), $service, new \App\Services\CustomerDebtReportService, new \App\Services\DebtCollectionService)->getData();
        self::assertSame(2000, $accounting['rows']->first()['balance']);
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

    public function test_accounting_can_record_a_reminder_and_see_it_on_customer_debt_detail(): void
    {
        $this->collectionClock('2026-11-19 10:00:00');
        $sheets = $this->service([$this->entry('CRM01', 'Chị Bình')]);
        $key = hash('sha256', 'code:CRM01');
        $collections = new \App\Services\DebtCollectionService;
        $report = new \App\Http\Controllers\CustomerDebtReportController;
        $response = $report->remind($this->request($this->reminderData(['source_key' => $key, 'target_amount' => 500]), 'accountant'), $sheets, $collections);
        self::assertSame(200, $response->getStatusCode());
        $detail = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key], 'accountant'), $sheets)->getData();
        self::assertCount(1, $detail['reminderHistory']['logs']);
        self::assertSame(500, $detail['reminderHistory']['latest']['target']);
        self::assertSame(route('customers.debt-policy.update', 1), $detail['detailPolicyAction']);
        $feedback = $report->respond($this->request([
            'source_key' => $key, 'reminder_id' => $response->getData(true)['id'],
            'request_id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'response', 'note' => 'Khách hẹn thanh toán',
        ], 'accountant'), $sheets, $collections);
        self::assertSame(200, $feedback->getStatusCode());
        $history = $report->history($this->request(['source_key' => $key], 'accountant'), $sheets, $collections)->getData(true)['html'];
        self::assertStringContainsString('Thêm phản hồi / lời hẹn', $history);
        self::assertStringContainsString('Khách hẹn thanh toán', $history);

        $this->expectException(HttpException::class);
        $report->remind($this->request($this->reminderData(['source_key' => $key, 'target_amount' => 500]), 'sale'), $sheets, $collections);
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->temporary();
            $table->id();
            $table->string('account_number');
            $table->string('name');
            $table->string('type');
            $table->string('bank_name');
            $table->string('owner_type');
            $table->decimal('opening_balance', 18, 2);
            $table->decimal('balance', 18, 2);
            $table->boolean('is_active');
            $table->timestamps();
        });
        $this->tables[] = 'accounts';
        $tabs = [[['Ngày', 'Mã KH', 'Khách hàng', 'Tổng tiền'], ['13/11/2026', 'CRM01', 'Chị Bình', '1000']]];
        $accountNumbers = ['1115129999', '7799131453666', '1050717555', '8638596666', '39876555'];
        foreach ($accountNumbers as $index => $number) {
            $tabs[] = [
                ['NKC TK '.$number],
                ['Ngày tháng', 'Tháng', $index === 0 ? 'Loại' : 'Code', 'Tên KH', 'NVKD', 'Nội dung', 'Thu', 'Chi', 'Còn lại'],
                ['13/11/2026', '11', 'TTBH', 'Chị Bình', '', 'Thu khách', '100', '', '600'],
                ['13/11/2026', '11', 'TTM', 'Chị Bình', '', 'Nộp tiền', '9000', '', '9600'],
                ['13/11/2026', '11', 'CPK', '', '', 'Phí', '', '10', '9590'],
                ...($index === 0 ? [] : [['13/11/2026', '11', 'TTBH', '', '', 'Chưa rõ khách', '50', '', '9640']]),
            ];
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
        self::assertSame(5, DB::table('accounts')->count());
        $cashflow = $service->snapshot()['cashflow_ledgers']['TKCTBIDV'];
        self::assertSame(500, $cashflow['opening']);
        self::assertSame(9640, $cashflow['closing']);
        self::assertSame(4, count($cashflow['rows']));
        self::assertSame(10, $cashflow['rows'][2]['out']);
        self::assertSame(9590, $cashflow['rows'][2]['balance']);
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
        self::assertSame(500, $data['detailSalesPayments']);
        self::assertSame(150, $data['detailGifts']);
        self::assertSame(150, $data['closing']);
        self::assertSame(5, $data['payments']->total());
        self::assertSame(850, $data['detailSettlementTotal']);
        self::assertSame(150, $data['payments']->getCollection()->where('type', 'gift')->sum('amount'));
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
        (new \App\Http\Controllers\CustomerDebtReportController)->updateRecovery($this->request(['report_source' => 'system', 'customer_id' => 1, 'source_key' => $row['key'], 'recovery_status' => 'difficult', 'recovery_note' => 'Cần nhắc nhiều lần', 'recovery_version' => $row['recovery']['version']], 'accountant'), $this->service([]), new \App\Services\CustomerDebtRecoveryService);
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

    public function test_sales_can_only_see_their_customers_with_verified_identity(): void
    {
        $view = (new CustomerDebtMonitorController)->index($this->request(), $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải')]));
        $data = $view->getData();
        self::assertCount(1, $data['groups']);
        self::assertSame(1, array_values($data['groups'])[0]['customer_id']);
        self::assertSame('code', array_values($data['groups'])[0]['matched_by']);
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
        $view = (new CustomerDebtMonitorController)->index($this->request(['customer' => $key, 'window' => 'all', 'detail_page' => 2, 'purchase_per_page' => 100], 'admin'), $service);
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
        $controller = new CustomerDebtMonitorController;
        $controller->unmap($this->request(['source_key' => $key, 'customer_id' => 1], 'admin'), $service);
        $service->save($sales, $payments, 7, 'paste');
        self::assertTrue($service->mappings()[$key]['unlinked']);
        $data = $controller->index($this->request(['customer' => $key], 'admin'), $service)->getData();
        self::assertNull($data['selected']['customer_id']);
        self::assertSame('Khách nguồn A', $data['selected']['name']);
        self::assertSame(500000, $data['closing']);
    }

    public function test_unlinking_an_automatic_match_requires_an_explicit_remap(): void
    {
        $service = $this->service([$this->entry('WRONG01', 'Anh Hải')]);
        $key = array_key_first($service->summarize($service->snapshot()['entries']));
        $controller = new CustomerDebtMonitorController;
        $before = $controller->index($this->request(['customer' => $key], 'admin'), $service)->getData();
        self::assertSame(2, $before['selected']['customer_id']);
        self::assertSame('KH02', $before['selected']['code']);
        self::assertSame('WRONG01', $before['groups'][$key]['code']);

        $redirect = $controller->unmap($this->request(['source_key' => $key, 'customer_id' => 2], 'admin'), $service);
        self::assertStringEndsWith('#debt-detail', $redirect->getTargetUrl());
        $after = $controller->index($this->request(['customer' => $key], 'admin'), $service)->getData();
        self::assertNull($after['selected']['customer_id']);
        self::assertArrayNotHasKey(2, $after['customerDebts']);

        $service->mapCustomer($key, 2, 7);
        $after = $controller->index($this->request(['customer' => $key], 'admin'), $service)->getData();
        self::assertSame(2, $after['selected']['customer_id']);
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
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
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
        self::assertSame(1, $data['customerDebts'][1]['policy']['allowed_orders']);
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
        $service = $this->service([$this->entry('CRM01', 'Chị Bình'), $this->entry('KH02', 'Anh Hải')]);
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
        $first = $this->entry('CRM01', 'Chị Bình');
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
        self::assertSame(2, $data['payments']->total());
        self::assertSame(700, $data['detailSettlementTotal']);
        self::assertSame('2026-11-10', $data['reportLastPurchase']);
        self::assertSame('2026-11-05', $data['reportLastPayment']);
        self::assertSame(0, $data['reportAlerts']['overdue_days']);
        self::assertSame('high', $data['reportAlerts']['risk']);
    }

    public function test_customer_statement_keeps_full_period_entries_and_matches_detail_totals(): void
    {
        $this->collectionClock('2026-11-13 12:00:00');
        $entries = [array_replace($this->entry('CRM01', 'Chị Bình'), ['date' => '2026-10-01'])];
        for ($i = 1; $i <= 105; $i++) {
            $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', $i + 1), ['date' => '2026-11-13']);
        }
        $entries[] = array_replace($this->entry('CRM01', 'Chị Bình', 200), ['type' => 'payment', 'date' => '2026-11-12', 'amount' => 500]);
        $service = $this->service($entries);
        $key = array_key_first($service->summarize($entries));
        $params = ['customer' => $key, 'report_from' => '2026-11-10', 'report_to' => '2026-11-13', 'purchase_page' => 2];
        $controller = new CustomerDebtMonitorController;
        $detail = $controller->index($this->request($params, 'admin'), $service)->getData();
        $view = $controller->index($this->request($params + ['presentation' => 'customer'], 'admin'), $service);
        self::assertSame('customer-debt.customer-statement', $view->name());
        $statement = $view->getData();
        self::assertCount(105, $statement['saleEntries']);
        self::assertCount(1, $statement['paymentEntries']);
        self::assertSame(1000, $statement['opening']);
        self::assertSame(105500, $statement['closing']);
        self::assertSame($detail['closing'], $statement['closing']);
        self::assertSame($detail['detailPayments'], $statement['detailPayments']);
        self::assertSame(1, $statement['statementCustomer']->id);
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
        $params = ['tab' => 'all', 'customer' => $key, 'window' => 'all', 'purchase_page' => 2, 'payment_page' => 1];
        $controller = new CustomerDebtMonitorController;
        $default = $controller->index($this->request($params, 'admin'), $service)->getData();
        self::assertSame(20, $default['purchases']->perPage());
        self::assertCount(20, $default['purchases']);

        $data = $controller->index($this->request($params + ['purchase_per_page' => 50], 'admin'), $service)->getData();
        self::assertSame(50, $data['purchases']->perPage());
        self::assertCount(50, $data['purchases']);
        self::assertStringContainsString('purchase_per_page=50', $data['purchases']->url(3));
        self::assertStringContainsString('purchase_per_page=50', $data['payments']->url(2));
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

    public function test_admin_and_accounting_unlock_allows_only_one_further_save_and_does_not_bank_credits(): void
    {
        $this->policyUpdate();
        $controller = new \App\Http\Controllers\CustomerDebtPolicyController;
        $service = app(\App\Services\CustomerDebtPolicyService::class);
        $customer = \App\Models\Customer::findOrFail(1);
        foreach (['admin', 'accountant'] as $cycle => $role) {
            $unlocked = $controller->unlock($this->request(['policy_version' => $service->policies()['customer_1']['edit_version']], $role), $customer, $service)->getData(true)['policy'];
            $again = $controller->unlock($this->request(['policy_version' => $service->policies()['customer_1']['edit_version']], $role), $customer, $service)->getData(true)['policy'];
            self::assertFalse($again['staff_edit_locked']);
            self::assertSame($unlocked['edit_version'], $again['edit_version']);
            $saved = $this->policyUpdate(['policy_version' => $again['edit_version']], 'leader')['policy'];
            self::assertTrue($saved['staff_edit_locked']);
            self::assertSame($cycle + 2, $saved['staff_edit_count']);
        }
        $audits = DB::table('settings')->where('key', 'like', 'customer_debt_policy_audit_%')->count();
        self::assertSame(5, $audits);
    }

    public function test_roles_outside_debt_management_cannot_unlock_even_through_service(): void
    {
        $this->policyUpdate();
        $service = app(\App\Services\CustomerDebtPolicyService::class);
        foreach (['sale', 'leader', 'manager', 'warehouse'] as $role) {
            try {
                $service->unlock(['customer_id' => 1], $this->user($role));
                self::fail('Unauthorized role unlocked');
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
        $service = $this->service([$this->entry('CRM01', 'Chị Bình')]);
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
