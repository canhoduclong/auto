<?php
namespace Tests\Feature;

use App\Models\{Customer, Order, User};
use App\Services\{ApprovalService, SpecialWarehousePickupService};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class SpecialWarehousePickupTest extends TestCase
{
    private $app;
    private array $tables = [];
    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.connections.mysql.prefix' => 'special_pickup_test_'.getmypid().'_']); DB::purge('mysql');
        $this->table('users', ['name'], [], ['warehouse_id']);
        $this->table('roles', ['name']); $this->table('role_user', [], [], ['role_id', 'user_id']);
        $this->table('customers', ['name', 'phone', 'address', 'deleted_at', 'customer_status'], ['special_pickup_enabled', 'commission_percent', 'shipping_fee'], ['assigned_to', 'current_owner_sale_id', 'default_shipper_id']);
        $this->table('products', ['name', 'unit'], ['status', 'special_pickup_enabled', 'is_priced_by_kg']);
        $this->table('product_variants', ['name', 'sku', 'size'], ['kg', 'stock', 'is_priced_by_kg'], ['product_id']);
        $this->table('product_price_rules', ['start_date', 'end_date'], ['price', 'min_price'], ['product_variant_id']);
        $this->table('warehouses', ['name'], ['status']);
        $this->table('accounts', ['name', 'type'], ['is_active', 'opening_balance', 'balance']);
        $this->table('account_user', [], [], ['account_id', 'user_id']);
        $this->table('inventories', [], ['quantity', 'reserved_quantity', 'weight_kg'], ['warehouse_id', 'product_variant_id']);
        $this->table('inventory_reservations', ['reserved_at', 'packed_at'], ['quantity'], ['inventory_id', 'order_item_id']);
        $this->table('inventory_documents', ['type', 'document_number', 'document_date', 'notes'], ['shipping_fee'], ['warehouse_id', 'user_id']);
        $this->table('inventory_document_items', [], ['quantity', 'weight_kg', 'unit_cost'], ['inventory_document_id', 'product_variant_id']);
        $this->table('inventory_movements', ['type', 'reference_type'], ['quantity'], ['inventory_id', 'reference_id', 'user_id']);
        $this->table('orders', ['creation_token', 'creation_payload_hash', 'code', 'recipient_name', 'recipient_phone', 'recipient_address', 'delivery_time', 'delivery_date', 'delivered_at', 'note', 'status', 'delivery_status', 'payment_status', 'payment_method', 'shipper_note', 'commission_created_at', 'operational_completion_note', 'operational_completed_at'], ['total', 'subtotal_amount', 'item_discount_total', 'extra_discount_total', 'total_discount', 'order_discount', 'total_weight', 'amount_paid', 'amount_due', 'collected_amount', 'shipping_fee', 'charge_shipping_fee', 'charge_vat', 'vat_percent', 'vat_amount', 'collect_customer_shipping_fee', 'customer_shipping_fee', 'is_special_pickup', 'skip_auto_cancel', 'needs_operational_completion', 'commission_percent_snapshot', 'commission_amount_snapshot'], ['customer_id', 'user_id', 'shipper_id', 'warehouse_id', 'special_import_document_id', 'special_export_document_id', 'special_payment_id', 'accounting_sales_import_batch_id', 'operational_completed_by']);
        $this->table('order_items', ['discount_type'], ['quantity', 'price', 'base_price', 'unit_discount', 'discount_total', 'unit_weight', 'is_priced_by_kg', 'total_weight', 'total', 'packed_weight', 'actual_weight', 'packed_quantity'], ['order_id', 'product_id', 'product_variant_id']);
        $this->table('order_returns', ['status'], ['refund_amount'], ['order_id', 'order_adjustment_id']);
        $this->table('order_histories', ['role', 'action', 'status_before', 'status_after', 'note'], [], ['order_id', 'user_id']);
        $this->table('transactions', ['type', 'method', 'status', 'note', 'approved_at', 'destination_type'], ['amount'], ['order_id', 'customer_id', 'account_id', 'destination_account_id', 'transaction_category_id', 'submitted_by', 'approved_by']);
        $this->table('transaction_categories', ['flow_direction']);
        $this->table('accounting_reconciliations', ['status', 'confirmed_at', 'note'], ['total_amount', 'paid_amount', 'shipping_fee', 'return_amount', 'recognized_revenue'], ['order_id', 'sale_id', 'shipper_id', 'confirmed_by']);
        $this->table('order_commissions', ['status', 'confirmed_at'], ['order_total', 'commission_percent', 'commission_amount'], ['order_id', 'sale_user_id', 'customer_id', 'confirmed_by']);
        $this->table('accounting_sales_entries', ['entry_date', 'customer_code', 'customer_name', 'sale_name', 'source', 'source_key', 'unit'], ['quantity', 'unit_weight', 'total_quantity', 'unit_price', 'total_amount', 'entry_month'], ['order_id', 'order_item_id', 'customer_id', 'sale_id', 'accounting_reconciliation_id', 'created_by', 'updated_by']);
        $this->table('settings', ['key', 'value']);
        DB::table('users')->insert([['id' => 1, 'name' => 'Admin'], ['id' => 2, 'name' => 'Shipper mặc định'], ['id' => 3, 'name' => 'Sale phụ trách']]);
        DB::table('roles')->insert([['id' => 1, 'name' => 'admin'], ['id' => 2, 'name' => 'shipper'], ['id' => 3, 'name' => 'sale']]);
        DB::table('role_user')->insert([['user_id' => 1, 'role_id' => 1], ['user_id' => 2, 'role_id' => 2], ['user_id' => 3, 'role_id' => 3]]);
        DB::table('customers')->insert(['id' => 1, 'name' => 'Khách lông', 'special_pickup_enabled' => 1, 'default_shipper_id' => 2, 'assigned_to' => 3, 'current_owner_sale_id' => 3, 'commission_percent' => 2]);
        DB::table('products')->insert(['id' => 1, 'name' => 'Bộ lông', 'unit' => 'bo', 'status' => 1, 'special_pickup_enabled' => 1, 'is_priced_by_kg' => 1]);
        DB::table('product_variants')->insert(['id' => 1, 'name' => 'Bộ lông', 'product_id' => 1, 'kg' => 1, 'stock' => 20, 'is_priced_by_kg' => 1]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'Kho', 'status' => 1]);
        DB::table('accounts')->insert(['id' => 1, 'name' => 'Tiền mặt', 'type' => 'cash', 'is_active' => 1, 'opening_balance' => 100000, 'balance' => 100000]);
        DB::table('inventories')->insert(['id' => 1, 'product_variant_id' => 1, 'warehouse_id' => 1, 'quantity' => 20, 'reserved_quantity' => 5, 'weight_kg' => 20]);
        auth()->setUser(User::findOrFail(1));
        // This branch must not create pending approval steps.
        $approval = \Mockery::mock(ApprovalService::class); $approval->shouldNotReceive('initOrderApproval'); $this->app->instance(ApprovalService::class, $approval);
        $cache = new \ReflectionProperty(\App\Http\Controllers\OrderController::class, 'tableColumnsCache'); $cache->setValue(null, []);
    }
    protected function tearDown(): void
    {
        foreach (array_reverse($this->tables) as $table) Schema::dropIfExists($table);
        config(['database.connections.mysql.prefix' => '']); DB::purge('mysql');
        $cache = new \ReflectionProperty(\App\Http\Controllers\OrderController::class, 'tableColumnsCache'); $cache->setValue(null, []);
        \Mockery::close(); $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    private function table(string $name, array $texts = [], array $numbers = [], array $ids = []): void
    {
        Schema::create($name, function (Blueprint $t) use ($texts, $numbers, $ids) {
            $t->id(); foreach ($texts as $field) $t->text($field)->nullable(); foreach ($numbers as $field) $t->decimal($field, 18, 3)->nullable(); foreach ($ids as $field) $t->unsignedBigInteger($field)->nullable(); $t->timestamps();
        }); $this->tables[] = $name;
    }
    private function data(): array
    {
        return ['creation_token' => '123e4567-e89b-42d3-a456-426614174000', 'customer_id' => 1, 'warehouse_id' => 1, 'stock_source' => 'existing', 'goods_received' => true, 'items' => [['variant_id' => 1, 'quantity' => 2, 'actual_weight' => 3.123, 'unit_price' => 11000]]];
    }
    public function test_pickup_exports_once_and_waits_for_accounting_without_receiving_any_money(): void
    {
        Model::withoutEvents(function () {
            $service = new SpecialWarehousePickupService;
            $order = $service->create($this->data(), auth()->user());
            self::assertSame('delivered', $order->status); self::assertSame('unpaid', $order->payment_status);
            self::assertSame(2, (int) $order->shipper_id); self::assertSame(3, (int) $order->user_id);
            self::assertSame(0.0, (float) $order->amount_due); self::assertSame(0.0, (float) $order->amount_paid);
            self::assertSame(18.0, (float) DB::table('inventories')->value('quantity')); self::assertSame(5.0, (float) DB::table('inventories')->value('reserved_quantity'));
            self::assertSame(100000.0, (float) DB::table('accounts')->value('balance'));
            foreach (['transactions', 'accounting_reconciliations', 'order_commissions', 'accounting_sales_entries'] as $table) self::assertSame(0, DB::table($table)->count(), $table);
            self::assertNull($order->special_payment_id);
            self::assertSame(0, DB::table('inventory_documents')->where('type', 'import')->count());
            self::assertSame(0, DB::table('inventory_reservations')->count());
            self::assertSame(0.0, (float) $order->collected_amount);
            $copy = $order->replicate()->resetForCopiedOrder($order->id);
            self::assertFalse($copy->is_special_pickup); self::assertNull($copy->special_payment_id); self::assertNull($copy->special_export_document_id);
            self::assertSame($order->id, $service->create($this->data(), auth()->user())->id);
            self::assertSame(0, DB::table('transactions')->count()); self::assertSame(1, DB::table('inventory_documents')->count());
        });
    }
    public function test_incoming_goods_create_linked_import_and_export_without_inventing_existing_stock(): void
    {
        Model::withoutEvents(function () {
            $data = $this->data(); $data['stock_source'] = 'new_receipt'; $data['receipt_source'] = 'Thu hồi sản xuất thực tế'; $data['items'][0]['unit_cost'] = 2000;
            $order = (new SpecialWarehousePickupService)->create($data, auth()->user());
            self::assertNotNull($order->special_import_document_id); self::assertNotNull($order->special_export_document_id);
            self::assertSame(20.0, (float) DB::table('inventories')->value('quantity'));
            self::assertSame(2, DB::table('inventory_documents')->count());
        });
    }
    public function test_insufficient_stock_rolls_back_order_documents_payment_and_account(): void
    {
        Model::withoutEvents(function () {
            DB::table('inventories')->update(['quantity' => 6, 'reserved_quantity' => 5]);
            try { (new SpecialWarehousePickupService)->create($this->data(), auth()->user()); self::fail('Should reject insufficient stock'); }
            catch (ValidationException $e) { self::assertStringContainsString('Không đủ tồn', $e->getMessage()); }
            foreach (['orders', 'order_items', 'inventory_documents', 'inventory_movements', 'transactions', 'accounting_reconciliations'] as $table) self::assertSame(0, DB::table($table)->count(), $table);
            self::assertSame(100000.0, (float) DB::table('accounts')->value('balance'));
        });
    }
    public function test_switches_and_mixed_products_are_checked_on_server(): void
    {
        $service = new SpecialWarehousePickupService; $customer = Customer::findOrFail(1);
        self::assertTrue($service->eligible($customer, [1]));
        DB::table('products')->where('id', 1)->update(['special_pickup_enabled' => 0]); self::assertFalse($service->eligible($customer, [1]));
        DB::table('products')->where('id', 1)->update(['special_pickup_enabled' => 1]); $customer->special_pickup_enabled = false; self::assertFalse($service->eligible($customer, [1]));
        $customer->special_pickup_enabled = true; self::assertFalse($service->eligible($customer, [1, 999]));
    }
    public function test_customer_must_have_received_goods_before_pickup_can_finish(): void
    {
        $data = $this->data(); $data['goods_received'] = false;
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('khách đã nhận hàng');
        (new SpecialWarehousePickupService)->create($data, auth()->user());
    }
    public function test_measured_weight_is_used_without_rounding_through_size_and_vat_is_reconciled(): void
    {
        Model::withoutEvents(function () {
            DB::table('products')->where('id', 1)->update(['unit' => 'kg']);
            $data = $this->data();
            $data['items'][0]['quantity'] = 3; $data['items'][0]['actual_weight'] = 4.123;
            $data['charge_vat'] = true; $data['vat_percent'] = 8;
            $order = (new SpecialWarehousePickupService)->create($data, auth()->user());
            self::assertSame(48981.24, (float) $order->total);
            self::assertSame(4.123, (float) $order->items()->value('actual_weight'));
            self::assertSame(45353.0, (float) $order->items()->value('total'));
            self::assertSame(0, DB::table('accounting_sales_entries')->count());
            self::assertSame('delivered', $order->delivery_status);
        });
    }
    public function test_missing_default_shipper_and_off_configuration_cannot_create_orders(): void
    {
        Model::withoutEvents(function () {
            DB::table('customers')->update(['default_shipper_id' => null]);
            try { (new SpecialWarehousePickupService)->create($this->data(), auth()->user()); self::fail('Missing shipper accepted'); }
            catch (ValidationException $e) { self::assertStringContainsString('shipper mặc định', $e->getMessage()); }
            DB::table('customers')->update(['default_shipper_id' => 2]);
            DB::table('products')->update(['special_pickup_enabled' => false]);
            try { (new SpecialWarehousePickupService)->create($this->data(), auth()->user()); self::fail('Off configuration accepted'); }
            catch (ValidationException $e) { self::assertStringContainsString('phải bật', $e->getMessage()); }
            self::assertSame(0, DB::table('orders')->count());
        });
    }
    public function test_pickup_needs_no_cash_account_and_replay_with_changed_payload_is_rejected(): void
    {
        DB::table('accounts')->delete();
        Model::withoutEvents(function () {
            $service = new SpecialWarehousePickupService; $service->create($this->data(), auth()->user());
            $data = $this->data(); $data['note'] = 'Nội dung đã thay đổi';
            try { $service->create($data, auth()->user()); self::fail('Changed replay accepted'); }
            catch (ValidationException $e) { self::assertStringContainsString('nội dung khác', $e->getMessage()); }
            self::assertSame(1, DB::table('orders')->count()); self::assertSame(0, DB::table('transactions')->count());
        });
    }

    public function test_accounting_can_receive_money_then_confirm_revenue_in_separate_steps(): void
    {
        Model::withoutEvents(function () {
            $order = (new SpecialWarehousePickupService)->create($this->data(), auth()->user());
            $accounting = app(\App\Http\Controllers\AccountingDashboardController::class);
            $payment = \App\Models\Transaction::create(['order_id' => $order->id, 'customer_id' => 1, 'account_id' => 1, 'amount' => 22000, 'type' => 'payment', 'method' => 'cash', 'status' => 'approved', 'submitted_by' => 1, 'approved_by' => 1]);
            (new \ReflectionMethod($accounting, 'applyTransactionToOrder'))->invoke($accounting, $payment);
            self::assertSame('paid', $order->fresh()->payment_status);
            self::assertSame('delivered', $order->fresh()->status);
            self::assertSame(0, DB::table('accounting_reconciliations')->count());
            self::assertSame(122000.0, (float) DB::table('accounts')->value('balance'));
            (new \ReflectionMethod($accounting, 'confirmReconciliationOrder'))->invoke($accounting, $order->fresh(), 1, 'Kế toán xác nhận sau khi giao hàng');
            self::assertSame('completed', $order->fresh()->status);
            self::assertSame(0.0, (float) $order->fresh()->amount_due);
            self::assertSame('confirmed', DB::table('accounting_reconciliations')->value('status'));
            self::assertSame(2, (int) DB::table('accounting_reconciliations')->value('shipper_id'));
            self::assertSame(22000.0, (float) DB::table('accounting_sales_entries')->sum('total_amount'));
            self::assertSame(440.0, (float) DB::table('order_commissions')->value('commission_amount'));
        });
    }

    public function test_accounting_can_confirm_revenue_first_and_receive_payment_later(): void
    {
        Model::withoutEvents(function () {
            $order = (new SpecialWarehousePickupService)->create($this->data(), auth()->user());
            $accounting = app(\App\Http\Controllers\AccountingDashboardController::class);
            (new \ReflectionMethod($accounting, 'confirmReconciliationOrder'))->invoke($accounting, $order->fresh(), 1, 'Kế toán xác nhận doanh thu trước khi thu tiền');
            self::assertSame('completed', $order->fresh()->status);
            self::assertSame('unpaid', $order->fresh()->payment_status);
            self::assertSame(22000.0, (float) $order->fresh()->amount_due);
            self::assertSame(0, DB::table('transactions')->count());
            self::assertSame(100000.0, (float) DB::table('accounts')->value('balance'));
            $payment = \App\Models\Transaction::create(['order_id' => $order->id, 'customer_id' => 1, 'account_id' => 1, 'amount' => 22000, 'type' => 'payment', 'method' => 'cash', 'status' => 'approved', 'submitted_by' => 1, 'approved_by' => 1]);
            (new \ReflectionMethod($accounting, 'applyTransactionToOrder'))->invoke($accounting, $payment);
            self::assertSame('paid', $order->fresh()->payment_status);
            self::assertSame(0.0, (float) $order->fresh()->amount_due);
            self::assertSame(122000.0, (float) DB::table('accounts')->value('balance'));
        });
    }
    public function test_pickup_endpoint_rejects_payment_fields_from_old_cached_clients(): void
    {
        $data = $this->data(); $data['amount_received'] = 22000; $data['account_id'] = 1; $data['cash_received'] = true;
        $request = \Illuminate\Http\Request::create('/special-warehouse-pickup', 'POST', $data);
        $request->setUserResolver(fn () => auth()->user());
        try {
            (new \App\Http\Controllers\SpecialWarehousePickupController)->store($request, new SpecialWarehousePickupService);
            self::fail('Old payment fields accepted');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('amount_received', $e->errors());
            self::assertArrayHasKey('account_id', $e->errors());
            self::assertArrayHasKey('cash_received', $e->errors());
            self::assertSame(0, DB::table('transactions')->count());
            self::assertSame(0, DB::table('orders')->count());
        }
    }

}
