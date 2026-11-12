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
        DB::table('users')->insert([['id' => 7, 'name' => 'Nguyễn Văn Huy', 'short_name' => 'Huy'], ['id' => 8, 'name' => 'Trần Văn Hà', 'short_name' => 'Hà']]);
        Schema::create('customers', function (Blueprint $t) {
            $t->temporary();
            $t->id();
            $t->string('name');
            $t->string('customer_code');
            $t->string('phone')->nullable();
            $t->unsignedBigInteger('assigned_to')->nullable();
            $t->unsignedBigInteger('current_owner_sale_id')->nullable();
            $t->boolean('is_employee')->default(false);
            $t->string('customer_status')->default('managed');
            $t->timestamp('assigned_at')->nullable();
            $t->softDeletes();
        });
        $this->tables[] = 'customers';
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

        return $request;
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
