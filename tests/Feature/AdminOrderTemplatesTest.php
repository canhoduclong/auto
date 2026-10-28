<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\{OrderTemplateController, TextOrderImportController};
use App\Models\{Role, TextOrderDraft, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminOrderTemplatesTest extends TestCase
{
    private $app;
    private array $tables = ['text_order_drafts', 'users', 'customers', 'products', 'product_variants', 'orders', 'settings'];
    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.connections.mysql.prefix' => 'admin_template_test_'.getmypid().'_']); DB::purge('mysql');
        Schema::create('text_order_drafts', function (Blueprint $t) {
            $t->id(); foreach (['sale_id', 'customer_id', 'created_by', 'order_id', 'product_variant_id', 'truck_brand_id', 'truck_station_id'] as $column) $t->unsignedBigInteger($column)->nullable();
            foreach (['draft_scope', 'customer_name', 'phone', 'product_text', 'status', 'error_message', 'delivery_date', 'delivery_time', 'note', 'automation_mode', 'automation_last_error', 'automation_last_run_at', 'truck_brand_name', 'truck_station_name', 'truck_station_address', 'truck_station_phone', 'truck_receive_time'] as $column) $t->text($column)->nullable();
            $t->json('parsed_items')->nullable(); $t->json('automation_dates')->nullable(); $t->boolean('automation_enabled')->default(false); $t->boolean('use_truck_station')->default(false); $t->timestamps();
        });
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name')]);
        Schema::create('customers', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('phone')->nullable(), $t->string('customer_code')->nullable(), $t->softDeletes()]);
        Schema::create('products', fn (Blueprint $t) => [$t->id(), $t->string('name')]);
        Schema::create('product_variants', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('product_id')->nullable()]);
        Schema::create('orders', fn (Blueprint $t) => [$t->id(), $t->string('code')]);
        Schema::create('settings', fn (Blueprint $t) => [$t->id(), $t->string('key'), $t->string('value')]);
        DB::table('users')->insert([['id' => 1, 'name' => 'Sale A'], ['id' => 2, 'name' => 'Sale B']]);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Khách lông', 'phone' => '0900000000', 'customer_code' => 'KH01']);
        DB::table('product_variants')->insert(['id' => 1]);
        DB::table('orders')->insert(['id' => 10, 'code' => 'ORDER-EXISTING']);
        foreach ([[1, 1, 'sale_private'], [2, 2, 'sale_private'], [3, 1, 'admin_import']] as [$id, $saleId, $scope]) {
            DB::table('text_order_drafts')->insert(['id' => $id, 'sale_id' => $saleId, 'created_by' => $saleId, 'customer_id' => 1, 'order_id' => 10, 'draft_scope' => $scope, 'status' => 'draft', 'parsed_items' => json_encode([['product_variant_id' => 1, 'quantity' => 2, 'size_kg' => 1, 'unit_price' => 11000]])]);
        }
    }
    protected function tearDown(): void
    {
        foreach ($this->tables as $table) Schema::dropIfExists($table);
        config(['database.connections.mysql.prefix' => '']); DB::purge('mysql');
        $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    private function request(array $data = [], string $role = 'admin'): Request
    {
        $user = new User; $user->id = 99; $user->setRelation('roles', collect([new Role(['name' => $role])]));
        auth()->setUser($user);
        $request = Request::create('/admin/order-templates', 'POST', $data); $request->setUserResolver(fn () => $user);
        return $request;
    }
    private function mutations(): TextOrderImportController
    {
        return (new \ReflectionClass(TextOrderImportController::class))->newInstanceWithoutConstructor();
    }
    public function test_admin_lists_private_templates_across_sales_with_filters(): void
    {
        $controller = new OrderTemplateController;
        $all = $controller->index($this->request())->getData();
        self::assertSame(2, $all['drafts']->total());
        self::assertEqualsCanonicalizing([1, 2], $all['drafts']->pluck('sale_id')->all());
        $filtered = $controller->index($this->request(['sale_id' => 2, 'search' => 'KH01']))->getData();
        self::assertSame([2], $filtered['drafts']->pluck('id')->all());
        self::assertSame(2, $all['counts']['all']);
    }
    public function test_admin_updates_another_sales_template_without_changing_owner_or_existing_orders(): void
    {
        $request = $this->request(['sale_id' => 99, 'items' => [['product_variant_id' => 1, 'quantity' => 9, 'size_kg' => 1, 'unit_price' => 12000]], 'note' => 'Đã sửa bởi admin']);
        $this->mutations()->adminTemplateUpdate($request, TextOrderDraft::findOrFail(2));
        $draft = TextOrderDraft::findOrFail(2);
        self::assertSame(2, (int) $draft->sale_id);
        self::assertSame(9, $draft->parsed_items[0]['quantity']);
        self::assertSame('Đã sửa bởi admin', $draft->note);
        self::assertSame('ORDER-EXISTING', DB::table('orders')->where('id', 10)->value('code'));
    }
    public function test_editing_content_preserves_existing_truck_station_configuration(): void
    {
        DB::table('text_order_drafts')->where('id', 2)->update(['use_truck_station' => true, 'truck_station_name' => 'Trạm cũ', 'truck_brand_name' => 'Nhà xe cũ', 'truck_station_phone' => '0901234567']);
        $this->mutations()->adminTemplateUpdate($this->request(['note' => 'Chỉ sửa ghi chú']), TextOrderDraft::findOrFail(2));
        $draft = TextOrderDraft::findOrFail(2);
        self::assertTrue($draft->use_truck_station);
        self::assertSame('Trạm cũ', $draft->truck_station_name);
        self::assertSame('0901234567', $draft->truck_station_phone);
    }

    public function test_admin_can_enable_and_disable_schedule_using_existing_validation(): void
    {
        $this->mutations()->adminTemplateAutomation($this->request(['automation_mode' => 'scheduled', 'automation_enabled' => 1, 'automation_dates' => "2026-11-02\n2026-11-01\n2026-11-01"]), TextOrderDraft::findOrFail(2));
        $draft = TextOrderDraft::findOrFail(2);
        self::assertTrue($draft->automation_enabled);
        self::assertSame(['2026-11-01', '2026-11-02'], $draft->automation_dates);
        $this->mutations()->adminTemplateAutomation($this->request(['automation_mode' => 'daily', 'automation_enabled' => 0]), $draft);
        self::assertFalse($draft->fresh()->automation_enabled);
        self::assertSame(1, DB::table('orders')->count());
    }
    public function test_non_admin_cannot_manage_templates_even_using_direct_controller_calls(): void
    {
        foreach (['adminTemplateUpdate', 'adminTemplateAutomation', 'adminTemplateDestroy'] as $method) {
            try { $this->mutations()->$method($this->request([], 'sale'), TextOrderDraft::findOrFail(2)); self::fail('Non-admin accepted'); }
            catch (HttpException $e) { self::assertSame(403, $e->getStatusCode()); }
        }
        try { (new OrderTemplateController)->index($this->request([], 'manager')); self::fail('Manager accepted'); }
        catch (HttpException $e) { self::assertSame(403, $e->getStatusCode()); }
    }
    public function test_admin_import_drafts_are_not_exposed_as_sale_templates(): void
    {
        $this->expectException(HttpException::class);
        $this->mutations()->adminTemplateDestroy($this->request(), TextOrderDraft::findOrFail(3));
    }
    public function test_delete_only_removes_selected_template(): void
    {
        $this->mutations()->adminTemplateDestroy($this->request(), TextOrderDraft::findOrFail(2));
        self::assertNull(TextOrderDraft::find(2));
        self::assertNotNull(TextOrderDraft::find(1));
        self::assertSame(1, DB::table('orders')->count());
    }
}
