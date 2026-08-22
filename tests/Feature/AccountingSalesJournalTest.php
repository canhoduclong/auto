<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Services\CompletedSalesJournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingSalesJournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_tab_lists_delivered_order_items_vat_and_foam_box_only(): void
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
            'delivery_date' => '2026-08-10',
            'delivered_at' => '2026-08-10 10:00:00',
            'charge_vat' => true,
            'vat_percent' => 5,
            'vat_amount' => 330480,
            'charge_foam_box_fee' => true,
            'foam_box_price' => 80000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 29,
            'unit_weight' => 3.17,
            'is_priced_by_kg' => true,
            'total_weight' => 91.8,
            'actual_weight' => 91.8,
            'price' => 72000,
            'total' => 6609600,
        ]);

        $pendingCustomer = Customer::query()->create([
            'name' => 'Khách chưa giao không được ghi sổ',
            'status' => 'active',
        ]);
        $pendingOrder = Order::query()->create([
            'customer_id' => $pendingCustomer->id,
            'user_id' => $sale->id,
            'status' => Order::STATUS_PACKING,
            'delivery_date' => '2026-08-10',
        ]);
        $pendingOrder->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'is_priced_by_kg' => true,
            'total_weight' => 3.17,
            'price' => 72000,
            'total' => 228240,
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
            ->assertSee('vat')
            ->assertSee('330.480')
            ->assertSee('thùng xốp')
            ->assertSee('80.000')
            ->assertSee('Ghi vào Google Sheets');

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

        $this->assertSame(3, $journal['summary']['rows']);
        $this->assertSame(1, $journal['summary']['orders']);
        $this->assertNotContains(
            'Khách chưa giao không được ghi sổ',
            $journal['rows']->getCollection()->pluck('customer_name')->all()
        );

        $this->mock(\App\Services\GoogleSheetsJournalService::class)
            ->shouldReceive('replaceJournal')
            ->once()
            ->with(\Mockery::on(fn ($rows) => $rows->count() === 3))
            ->andReturn([
                'rows' => 3,
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
            ->assertSessionHas('success', 'Đã ghi toàn bộ 3 dòng vào trang tính “Nhật ký bán hàng”.');
    }
}
