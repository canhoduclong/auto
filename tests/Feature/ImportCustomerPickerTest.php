<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\GoogleSheetOrderImportController;
use App\Models\Customer;
use App\Services\GoogleSheetOrderImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class ImportCustomerPickerTest extends TestCase
{
    private $app;
    private array $tables = ['customers', 'users', 'roles', 'role_user', 'settings'];

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.connections.mysql.prefix' => 'import_picker_test_'.getmypid().'_']); DB::purge('mysql');
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('customer_code')->nullable(); $t->text('old_name')->nullable();
            $t->string('customer_status')->default('active'); $t->boolean('is_employee')->default(false);
            $t->unsignedBigInteger('assigned_to')->nullable(); $t->unsignedBigInteger('current_owner_sale_id')->nullable();
            $t->timestamp('assigned_at')->nullable(); $t->softDeletes();
        });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('role_user', function (Blueprint $t) { $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('role_id'); });
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->string('value'); });
        DB::table('users')->insert([['id' => 1, 'name' => 'Sale A'], ['id' => 2, 'name' => 'Sale B']]);
        DB::table('roles')->insert(['id' => 1, 'name' => 'sale']);
        DB::table('role_user')->insert([['user_id' => 1, 'role_id' => 1], ['user_id' => 2, 'role_id' => 1]]);
        for ($i = 1; $i <= 25; $i++) DB::table('customers')->insert(['id' => $i, 'name' => sprintf('Khách A %02d', $i), 'customer_code' => 'A'.$i, 'old_name' => $i === 25 ? 'Tên lịch sử' : null, 'assigned_to' => 1, 'current_owner_sale_id' => 1]);
        DB::table('customers')->insert(['id' => 30, 'name' => 'Khách sale B', 'assigned_to' => 2, 'current_owner_sale_id' => 2]);
        DB::table('customers')->insert(['id' => 31, 'name' => 'Khách đã chuyển từ A sang B', 'assigned_to' => 1, 'current_owner_sale_id' => 2]);
        DB::table('customers')->insert(['id' => 32, 'name' => 'Khách tự do', 'assigned_to' => 1, 'customer_status' => 'free']);
        DB::table('customers')->insert(['id' => 33, 'name' => 'Khách đã xóa', 'assigned_to' => 1, 'deleted_at' => now()]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $table) Schema::dropIfExists($table);
        config(['database.connections.mysql.prefix' => '']); DB::purge('mysql');
        $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }

    private function search(array $query): array
    {
        $request = Request::create('/admin/import-orders/customers', 'GET', $query);
        return (new GoogleSheetOrderImportController)->customers($request)->getData(true);
    }

    public function test_results_are_paginated_and_only_include_customers_currently_managed_by_selected_sale(): void
    {
        $first = $this->search(['sale_id' => 1]);
        $second = $this->search(['sale_id' => 1, 'page' => 2]);
        self::assertSame(25, $first['pagination']['total']);
        self::assertCount(20, $first['customers']);
        self::assertSame(2, $first['pagination']['last_page']);
        self::assertCount(5, $second['customers']);
        self::assertSame([], array_intersect(array_column($first['customers'], 'id'), array_column($second['customers'], 'id')));
        $other = $this->search(['sale_id' => 2]);
        self::assertEqualsCanonicalizing([30, 31], array_column($other['customers'], 'id'));
    }

    public function test_search_matches_name_code_and_old_name_without_crossing_sale_scope(): void
    {
        foreach (['Khách A 25', 'A25', 'Tên lịch sử'] as $search) {
            $data = $this->search(['sale_id' => 1, 'search' => $search]);
            self::assertSame([25], array_column($data['customers'], 'id'));
        }
        self::assertSame([], $this->search(['sale_id' => 2, 'search' => 'A25'])['customers']);
    }

    public function test_import_rechecks_ownership_inside_transaction_before_creating_orders(): void
    {
        $customer = Customer::findOrFail(1);
        DB::table('customers')->where('id', 1)->update(['assigned_to' => 2, 'current_owner_sale_id' => 2]);
        $sale = \App\Models\User::findOrFail(1);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Khách B không còn do sale đã chọn quản lý');
        (new GoogleSheetOrderImportService)->import([['customer_key' => 'source']], $customer, $sale, $sale, ['stage' => 'created']);
    }

    public function test_review_rejects_customer_from_another_sale_before_reading_import_snapshot(): void
    {
        Schema::create('warehouses', function (Blueprint $t) { $t->id(); $t->boolean('status'); }); $this->tables[] = 'warehouses';
        DB::table('warehouses')->insert(['id' => 1, 'status' => true]);
        $request = Request::create('/admin/import-orders/review', 'POST', ['preview' => '123e4567-e89b-42d3-a456-426614174000', 'source_customer' => str_repeat('a', 64), 'customer_id' => 30, 'sale_id' => 1, 'warehouse_id' => 1, 'stage' => 'created']);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Khách B không còn do sale đã chọn quản lý');
        (new GoogleSheetOrderImportController)->review($request, new GoogleSheetOrderImportService);
    }
}
