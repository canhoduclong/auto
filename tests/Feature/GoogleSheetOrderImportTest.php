<?php

namespace Tests\Feature;

use App\Models\{Customer, Order, User};
use App\Services\GoogleSheetOrderImportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use PHPUnit\Framework\TestCase;

class GoogleSheetOrderImportTest extends TestCase
{
    private $app;
    private array $tables = [];
    private const HEADER = "Ngày tháng\tTháng\tMã KH\tKhách hàng\tNVKD\tDVT\tSL\tKg/con\tTổng\tĐơn giá\tTổng tiền";
    private const LINE = "04/05/2026\t5\tCT7188\tAnh Huyên lông\tCTY\tbộ lông\t1295\t1,00\t1295\t11\t14.245.000";

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tables) as $table) DB::statement('DROP TEMPORARY TABLE IF EXISTS `'.$table.'`');
        $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }

    public function test_pasted_sample_uses_inclusive_dates_and_preserves_vietnamese_numbers(): void
    {
        $service = new GoogleSheetOrderImportService;
        $text = self::HEADER."\n".self::LINE."\n".str_replace('04/05/2026', '05/05/2026', self::LINE);
        $rows = $service->parseText($text, '2026-05-04', '2026-05-04');
        self::assertCount(1, $rows);
        self::assertSame('2026-05-04', $rows[0]['date']);
        self::assertSame('Anh Huyên lông', $rows[0]['customer_name']);
        self::assertSame('14.245.000', $rows[0]['total_amount']);
        self::assertSame('1,00', $rows[0]['unit_weight']);
        self::assertSame($service->sourceKey($rows[0]), $service->sourceKey($service->parseText(self::HEADER."\n".self::LINE, '2026-05-01', '2026-05-31')[0]));
    }

    public function test_invalid_dates_are_reported_instead_of_silently_dropping_orders(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không xác định được ngày');
        (new GoogleSheetOrderImportService)->parseText(self::HEADER."\n".str_replace('04/05/2026', '31/02/2026', self::LINE), '2026-01-01', '2026-12-31');
    }

    public function test_merged_customer_cells_and_totals_do_not_create_extra_orders(): void
    {
        $text = self::HEADER."\n".self::LINE."\n\t\t\t\t\tbộ lông\t2\t1,00\t2\t11\t22.000\n\t\t\tTổng cộng\t\tbộ lông\t1297\t1\t1297\t\t14.267.000";
        $rows = (new GoogleSheetOrderImportService)->parseText($text, '2026-05-04', '2026-05-04');
        self::assertCount(2, $rows);
        self::assertSame($rows[0]['customer_key'], $rows[1]['customer_key']);
        self::assertSame('2026-05-04', $rows[1]['date']);
    }

    private function table(string $name, array $strings = [], array $numbers = [], array $ids = [], ?string $unique = null): void
    {
        Schema::create($name, function (Blueprint $t) use ($strings, $numbers, $ids, $unique) {
            $t->temporary(); $t->id();
            foreach ($strings as $field) $t->text($field)->nullable();
            foreach ($numbers as $field) $t->decimal($field, 18, 3)->nullable();
            foreach ($ids as $field) $t->unsignedBigInteger($field)->nullable();
            if ($unique) $t->string($unique, 64)->unique();
            $t->timestamps();
        });
        $this->tables[] = $name;
    }

    private function fixtures(): array
    {
        $this->table('users', ['name', 'short_name', 'email']);
        $this->table('roles', ['name']);
        $this->table('role_user', [], [], ['role_id', 'user_id']);
        $this->table('customers', ['name', 'name_normalized', 'customer_code', 'old_name', 'phone', 'address', 'deleted_at', 'customer_status', 'assigned_at'], ['commission_percent', 'is_employee'], ['assigned_to', 'current_owner_sale_id']);
        $this->table('settings', ['key', 'value']);
        $this->table('products', ['name', 'unit'], ['status', 'is_priced_by_kg']);
        $this->table('product_variants', ['name', 'size', 'sku'], ['kg', 'is_priced_by_kg'], ['product_id']);
        $this->table('accounting_sales_import_batches', ['raw_text', 'business_date'], ['total_amount', 'row_count'], ['imported_by', 'source_warehouse_id'], 'source_hash');
        $this->table('accounting_sales_entries', ['entry_date', 'customer_code', 'customer_name', 'sale_name', 'unit', 'source', 'source_key'], ['quantity', 'unit_weight', 'total_quantity', 'unit_price', 'total_amount', 'entry_month', 'import_row'], ['customer_id', 'sale_id', 'order_id', 'order_item_id', 'accounting_reconciliation_id', 'import_batch_id', 'created_by', 'updated_by']);
        $this->table('orders', ['code', 'status', 'payment_status', 'recipient_name', 'recipient_phone', 'recipient_address', 'delivery_date', 'delivered_at', 'trash_at', 'note', 'imported_sales_group_key', 'commission_created_at'], ['total', 'subtotal_amount', 'amount_paid', 'amount_due', 'needs_operational_completion', 'total_weight', 'skip_auto_cancel', 'commission_percent_snapshot', 'commission_amount_snapshot'], ['customer_id', 'user_id', 'warehouse_id', 'shipper_id', 'accounting_sales_import_batch_id']);
        $this->table('order_items', ['imported_name'], ['quantity', 'price', 'base_price', 'unit_weight', 'total_weight', 'total', 'packed_weight'], ['order_id', 'accounting_sales_entry_id', 'product_id', 'product_variant_id']);
        $this->table('accounting_reconciliations', ['status', 'confirmed_at', 'note'], ['total_amount', 'paid_amount', 'shipping_fee', 'return_amount', 'recognized_revenue'], ['order_id', 'sale_id', 'shipper_id', 'confirmed_by']);
        $this->table('order_histories', ['role', 'action', 'status_before', 'status_after', 'note'], [], ['order_id', 'user_id']);
        $this->table('order_commissions', ['status', 'confirmed_at'], ['order_total', 'commission_percent', 'commission_amount'], ['order_id', 'sale_user_id', 'customer_id', 'confirmed_by']);
        $this->table('google_sheet_order_imports', ['order_date', 'source_customer'], [], ['customer_id', 'order_id', 'imported_by'], 'source_key');
        DB::table('users')->insert(['id' => 1, 'name' => 'Sale Test']);
        DB::table('roles')->insert(['id' => 1, 'name' => 'sale']);
        DB::table('role_user')->insert(['role_id' => 1, 'user_id' => 1]);
        DB::table('customers')->insert(['id' => 2, 'name' => 'Khách B', 'name_normalized' => Customer::normalizeName('Khách B'), 'customer_code' => 'B002', 'old_name' => 'Tên cũ trước đó', 'commission_percent' => 2, 'customer_status' => 'active', 'is_employee' => 0, 'assigned_to' => 1, 'current_owner_sale_id' => 1]);
        return [Customer::findOrFail(2), User::findOrFail(1)];
    }

    public function test_import_maps_to_customer_b_converts_price_and_confirms_only_the_selected_stage(): void
    {
        [$customer, $sale] = $this->fixtures();
        DB::table('users')->insert(['id' => 3, 'name' => 'Admin Import']);
        $actor = User::findOrFail(3);
        Model::withoutEvents(function () use ($customer, $sale, $actor) {
            $service = new GoogleSheetOrderImportService;
            foreach (['created', 'delivered', 'completed'] as $index => $stage) {
                $date = '2026-05-0'.($index + 4);
                $text = self::HEADER."\n".str_replace('04/05/2026', '0'.($index + 4).'/05/2026', self::LINE);
                $rows = $service->parseText($text, $date, $date);
                $prepared = $service->prepare($rows, $customer, $sale, $actor);
                self::assertSame(11000.0, $prepared['preview']['rows'][0]['data']['unit_price']);
                $service->import($rows, $customer, $sale, $actor, ['stage' => $stage, 'warehouse_id' => 1, 'shipper_id' => 1]);
                $order = Order::whereDate('delivery_date', $date)->firstOrFail();
                self::assertSame(2, (int) $order->customer_id);
                self::assertSame((int) $sale->id, (int) $order->user_id);
                $entry = DB::table('accounting_sales_entries')->where('order_id', $order->id)->first();
                self::assertSame((int) $sale->id, (int) $entry->sale_id);
                self::assertSame((int) $actor->id, (int) $entry->created_by);
                self::assertSame(['created' => Order::STATUS_PENDING_LEADER_APPROVAL, 'delivered' => Order::STATUS_DELIVERED, 'completed' => Order::STATUS_COMPLETED][$stage], $order->status);
                self::assertSame(14245000.0, (float) $order->total);
                self::assertSame(11000.0, (float) DB::table('order_items')->where('order_id', $order->id)->value('price'));
                self::assertSame($stage === 'completed' ? 1 : 0, DB::table('accounting_reconciliations')->where('order_id', $order->id)->count());
                self::assertSame($stage === 'completed' ? 1 : 0, DB::table('order_commissions')->where('order_id', $order->id)->count());
            }
            self::assertSame("Tên cũ trước đó\nAnh Huyên lông", $customer->fresh()->old_name);
            self::assertSame(3, DB::table('google_sheet_order_imports')->count());
            self::assertSame('confirmed', DB::table('accounting_reconciliations')->value('status'));
            self::assertSame((int) $sale->id, (int) DB::table('accounting_reconciliations')->value('sale_id'));
            self::assertSame((int) $sale->id, (int) DB::table('order_commissions')->value('sale_user_id'));
            self::assertSame(284900.0, (float) DB::table('order_commissions')->value('commission_amount'));
        });
    }

    public function test_duplicate_paste_rolls_back_without_changing_customer_b(): void
    {
        [$customer, $sale] = $this->fixtures();
        Model::withoutEvents(function () use ($customer, $sale) {
            $service = new GoogleSheetOrderImportService;
            $rows = $service->parseText(self::HEADER."\n".self::LINE, '2026-05-01', '2026-05-31');
            $options = ['stage' => 'completed', 'warehouse_id' => 1, 'shipper_id' => 1];
            $service->import($rows, $customer, $sale, $sale, $options);
            try {
                $service->import($rows, $customer, $sale, $sale, $options);
                self::fail('Duplicate import must fail');
            } catch (\Illuminate\Validation\ValidationException $e) {
                self::assertSame(1, DB::table('orders')->count());
                self::assertSame(1, DB::table('accounting_sales_import_batches')->count());
                self::assertSame("Tên cũ trước đó\nAnh Huyên lông", $customer->fresh()->old_name);
            }
        });
    }
    public function test_failure_after_first_order_rolls_back_all_orders_entries_and_old_name(): void
    {
        [$customer, $sale] = $this->fixtures();
        $this->app->instance(\App\Services\AccountingSalesImportService::class, new class extends \App\Services\AccountingSalesImportService {
            private int $calls = 0;
            public function syncEntryOrderItem(\App\Models\AccountingSalesEntry $entry): ?\App\Models\OrderItem
            {
                if (++$this->calls === 2) throw new \RuntimeException('Simulated storage failure');
                return null;
            }
        });
        Model::withoutEvents(function () use ($customer, $sale) {
            $service = new GoogleSheetOrderImportService;
            $text = self::HEADER."\n".self::LINE."\n".str_replace('04/05/2026', '05/05/2026', self::LINE);
            $rows = $service->parseText($text, '2026-05-04', '2026-05-05');
            try {
                $service->import($rows, $customer, $sale, $sale, ['stage' => 'completed', 'warehouse_id' => 1, 'shipper_id' => 1]);
                self::fail('Storage failure must propagate');
            } catch (\RuntimeException $e) {
                self::assertSame('Simulated storage failure', $e->getMessage());
                foreach (['orders', 'order_items', 'accounting_sales_entries', 'accounting_sales_import_batches', 'accounting_reconciliations', 'order_commissions', 'google_sheet_order_imports'] as $table) self::assertSame(0, DB::table($table)->count(), $table);
                self::assertSame('Tên cũ trước đó', $customer->fresh()->old_name);
            }
        });
    }

    public function test_same_day_report_shows_existing_orders_and_actual_counts_after_import(): void
    {
        [$customer, $sale] = $this->fixtures();
        DB::table('orders')->insert(['id' => 10, 'customer_id' => 2, 'code' => 'OLD-10', 'status' => 'completed', 'delivery_date' => '2026-05-04', 'total' => 100000]);
        DB::table('orders')->insert(['id' => 11, 'customer_id' => 2, 'code' => 'OLD-11', 'status' => 'delivered', 'created_at' => '2026-05-04 08:00:00', 'total' => 200000]);
        DB::table('orders')->insert(['id' => 12, 'customer_id' => 2, 'status' => 'cancelled', 'delivery_date' => '2026-05-04']);
        DB::table('orders')->insert(['id' => 13, 'customer_id' => 2, 'status' => 'completed', 'delivery_date' => '2026-05-04', 'trash_at' => now()]);
        DB::table('orders')->insert(['id' => 14, 'customer_id' => 99, 'status' => 'completed', 'delivery_date' => '2026-05-04']);
        DB::table('orders')->insert(['id' => 15, 'customer_id' => 2, 'status' => 'completed', 'delivery_date' => '2026-05-03']);
        Model::withoutEvents(function () use ($customer, $sale) {
            $service = new GoogleSheetOrderImportService;
            $rows = $service->parseText(self::HEADER."\n".self::LINE, '2026-05-04', '2026-05-04');
            $preview = $service->prepare($rows, $customer, $sale, $sale)['preview'];
            $report = $preview['same_day_orders'];
            self::assertCount(1, $report);
            self::assertSame('2026-05-04', $report[0]['date']);
            self::assertSame(2, $report[0]['existing_count']);
            self::assertSame(3, $report[0]['resulting_count']);
            self::assertSame(['OLD-10', 'OLD-11'], array_column($report[0]['orders'], 'code'));
            $service->import($rows, $customer, $sale, $sale, ['stage' => 'completed', 'warehouse_id' => 1, 'shipper_id' => 1]);
            $after = $service->sameDayOrders($customer, ['2026-05-04'], 0);
            self::assertSame(3, $after[0]['resulting_count']);
            self::assertCount(3, $after[0]['orders']);
            self::assertSame([], $service->sameDayOrders($customer, ['2026-05-03'], 0));
            self::assertSame([], $service->sameDayOrders($customer, ['2026-05-05']));
        });
    }

}
