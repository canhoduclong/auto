<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFee;
use App\Models\OrderFeeType;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnItem;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CompletedSalesJournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingSalesJournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_tab_lists_only_delivered_orders_by_order_date(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->create(['name' => 'admin']));
        $sale = User::factory()->create(['name' => 'Duệ']);
        $customer = Customer::query()->create([
            'name' => 'HKD Trường Hưng',
            'customer_code' => '08349',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'user_id' => $sale->id,
            'name' => 'Vịt nguyên con',
            'unit' => 'con',
            'status' => true,
            'is_priced_by_kg' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '3.17 kg',
            'sku' => 'JOURNAL-317',
            'kg' => 3.17,
            'is_priced_by_kg' => true,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'JOURNAL-DELIVERED',
            'status' => Order::STATUS_DELIVERED,
            'delivery_date' => '2026-08-12',
            'delivered_at' => '2026-08-12 10:00:00',
            'charge_vat' => true,
            'vat_percent' => 5,
            'vat_amount' => 330480,
            'charge_foam_box_fee' => true,
            'foam_box_price' => 80000,
            'charge_shipping_fee' => true,
            'shipping_fee' => 50000,
            'extra_discount_total' => 20000,
        ]);
        $order->forceFill(['created_at' => '2026-08-10 08:00:00'])->saveQuietly();
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 29,
            'unit_weight' => 3.17,
            'is_priced_by_kg' => true,
            'total_weight' => 91.8,
            'actual_weight' => 91.8,
            'price' => 72000,
            // Simulate the stale warehouse/order amount left before the
            // customer's delivered weight was recorded.
            'total' => 6832800,
        ]);
        $packingFeeType = OrderFeeType::query()->create([
            'name' => 'Phí đóng gói đặc biệt',
            'code' => 'special_packing',
            'calculation_type' => 'fixed',
            'direction' => 'charge',
            'default_value' => 40000,
            'is_active' => true,
            'is_system' => false,
        ]);
        OrderFee::query()->create([
            'order_id' => $order->id,
            'order_fee_type_id' => $packingFeeType->id,
            'fee_code' => $packingFeeType->code,
            'fee_name' => $packingFeeType->name,
            'calculation_type' => 'fixed',
            'direction' => 'charge',
            'rate' => 40000,
            'base_amount' => 6609600,
            'amount' => 40000,
        ]);

        $pendingCustomer = Customer::query()->create([
            'name' => 'Khách đơn ngoại lệ chưa giao',
            'status' => 'active',
        ]);
        $pendingOrder = Order::query()->create([
            'customer_id' => $pendingCustomer->id,
            'user_id' => $sale->id,
            'status' => Order::STATUS_PACKING,
            'delivery_date' => '2026-08-11',
        ]);
        $pendingOrder->forceFill(['created_at' => '2026-08-10 09:00:00'])->saveQuietly();
        $pendingOrder->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'is_priced_by_kg' => true,
            'total_weight' => 3.17,
            'price' => 72000,
            'total' => 228240,
        ]);

        $cancelledCustomer = Customer::query()->create([
            'name' => 'Khách có đơn đã hủy',
            'status' => 'active',
        ]);
        $cancelledOrder = Order::query()->create([
            'customer_id' => $cancelledCustomer->id,
            'user_id' => $sale->id,
            'status' => Order::STATUS_CANCELLED,
        ]);
        $cancelledOrder->forceFill(['created_at' => '2026-08-10 10:00:00'])->saveQuietly();
        $cancelledOrder->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 72000,
            'total' => 72000,
        ]);

        $response = $this->actingAs($admin)->get(route('accounting.daily-sales', [
            'tab' => 'journal',
            'from_date' => '2026-08-10',
            'to_date' => '2026-08-10',
        ]));

        $response->assertOk()
            ->assertSee('Nhật ký bán hàng')
            ->assertSee('08349')
            ->assertSee('HKD Trường Hưng')
            ->assertSee('Duệ')
            ->assertSee('Con')
            ->assertSee('29')
            ->assertSee('3,17')
            ->assertSee('91,8')
            ->assertSee('72.000')
            ->assertSee('6.609.600')
            ->assertSee('Phí VAT')
            ->assertSee('330.480')
            ->assertSee('Phí Ship')
            ->assertSee('Chiết khấu đơn')
            ->assertSee('Phí thùng xốp')
            ->assertSee('80.000')
            ->assertSee('Phí đóng gói đặc biệt')
            ->assertSee('Cộng thêm')
            ->assertSee('Giảm trừ')
            ->assertSee('Đồng bộ Google Sheets');

        $journal = app(CompletedSalesJournalService::class)->paginate(
            '2026-08-10',
            '2026-08-10',
            0,
            0,
            'date_asc',
            20,
            1,
            route('accounting.daily-sales'),
        );

        $this->assertSame(6, $journal['summary']['rows']);
        $this->assertSame(1, $journal['summary']['orders']);
        $this->assertSame(7090080.0, $journal['summary']['amount']);
        $productRow = $journal['rows']->getCollection()->firstWhere('entry_type', 'product');
        $this->assertSame(91.8, (float) $productRow->total_quantity);
        $this->assertSame(6609600.0, (float) $productRow->total_amount);
        $this->assertNotContains(
            'Khách đơn ngoại lệ chưa giao',
            $journal['rows']->getCollection()->pluck('customer_name')->all()
        );
        $this->assertNotContains(
            'Khách có đơn đã hủy',
            $journal['rows']->getCollection()->pluck('customer_name')->all()
        );

        $this->mock(\App\Services\GoogleSheetsJournalService::class)
            ->shouldReceive('syncJournalDates')
            ->once()
            ->with(
                \Mockery::on(fn ($rows) => $rows->count() === 6),
                ['2026-08-10']
            )
            ->andReturn([
                'rows' => 6,
                'dates' => 1,
                'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/test/edit',
                'sheet_name' => 'Nhật ký bán hàng',
            ]);

        $this->actingAs($admin)->post(route('accounting.daily-sales.google-sheets'), [
            'from_date' => '2026-08-10',
            'to_date' => '2026-08-10',
            'sale_id' => 0,
            'customer_id' => 0,
            'sort' => 'date_asc',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Đã đồng bộ 6 dòng của 1 ngày vào trang tính “Nhật ký bán hàng”.')
            ->assertSessionHas('google_sheets_url', 'https://docs.google.com/spreadsheets/d/test/edit');
    }

    public function test_partial_delivery_is_journaled_before_and_after_warehouse_confirmation(): void
    {
        $warehouse = Warehouse::query()->create([
            'name' => 'Kho nhận hàng giao một phần',
            'status' => true,
        ]);
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $warehouseUser->roles()->attach(Role::query()->create(['name' => 'warehouse']));
        $sale = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Khách nhận một phần',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'user_id' => $sale->id,
            'name' => 'Sản phẩm giao một phần',
            'unit' => 'cái',
            'status' => true,
            'is_priced_by_kg' => false,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Tiêu chuẩn',
            'sku' => 'PARTIAL-DELIVERY',
            'kg' => 1,
            'is_priced_by_kg' => false,
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'PARTIAL-DELIVERY-ORDER',
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => now(),
            'total' => 600000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 6,
            'price' => 100000,
            'total' => 1000000,
            'is_priced_by_kg' => false,
        ]);
        $orderReturn = OrderReturn::query()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'created_by' => $warehouseUser->id,
            'status' => 'pending_warehouse',
            'return_scope' => 'partial',
            'note' => 'Giao 1 phần: giao 6/10 (trả 4)',
        ]);
        ReturnItem::query()->create([
            'order_return_id' => $orderReturn->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
            'condition' => 'good',
        ]);
        Inventory::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);

        $journal = app(CompletedSalesJournalService::class);
        $date = $order->created_at->toDateString();
        $rowsBeforeWarehouseConfirmation = $journal->all($date, $date);
        $this->assertCount(1, $rowsBeforeWarehouseConfirmation);
        $this->assertSame(6.0, (float) $rowsBeforeWarehouseConfirmation->first()->quantity);
        $this->assertSame(600000.0, (float) $rowsBeforeWarehouseConfirmation->first()->total_amount);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.returns.confirm', $order))
            ->assertSessionHas('success');

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertSame('warehouse_received', $orderReturn->fresh()->status);
        $this->assertSame(4.0, (float) Inventory::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $variant->id)
            ->value('quantity'));

        $rows = $journal->all($date, $date);
        $this->assertCount(1, $rows);
        $this->assertSame(6.0, (float) $rows->first()->quantity);
        $this->assertSame(600000.0, (float) $rows->first()->total_amount);
    }
}
