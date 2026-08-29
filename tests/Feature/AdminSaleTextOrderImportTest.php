<?php

namespace Tests\Feature;

use App\Http\Controllers\OrderController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\TextOrderDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminSaleTextOrderImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_plain_text_imports_for_an_explicit_sale_and_date(): void
    {
        [$admin, $sale] = $this->adminAndSale();

        $response = $this->actingAs($admin)->post(
            route('admin.text-order-import.parse-for-sale'),
            [
                'sale_id' => $sale->id,
                'delivery_date' => '2026-08-28',
                'text' => "KH: Khách nhập text\nSĐT: 0901234567\nĐịa chỉ: Long An\nSP: Vịt móc\nSL: 2 con\nSize: 2,5\nGiá: 100k",
            ]
        );

        $response->assertRedirect(route('admin.text-order-import.index'))
            ->assertSessionHas('success');

        $draft = TextOrderDraft::query()->sole();
        $this->assertSame($admin->id, $draft->created_by);
        $this->assertSame($sale->id, $draft->sale_id);
        $this->assertSame(TextOrderDraft::SCOPE_ADMIN_IMPORT, $draft->draft_scope);
        $this->assertSame('2026-08-28', $draft->delivery_date->toDateString());
        $this->assertSame('Khách nhập text', $draft->customer_name);
    }

    public function test_confirming_text_import_preserves_the_selected_delivery_date(): void
    {
        [$admin, $sale] = $this->adminAndSale();
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'current_owner_sale_id' => $sale->id,
            'name' => 'Khách theo ngày',
            'status' => 'active',
        ]);
        $variant = ProductVariant::factory()->create();
        $draft = TextOrderDraft::query()->create([
            'created_by' => $admin->id,
            'draft_scope' => TextOrderDraft::SCOPE_ADMIN_IMPORT,
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'delivery_date' => '2026-08-28',
            'parsed_items' => [[
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'size_kg' => 2.5,
                'unit_price' => 100000,
            ]],
            'raw_text' => 'Đơn kiểm thử theo ngày',
            'status' => 'draft',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'TEXT-DATE-TEST',
            'total' => 200000,
            'delivery_date' => '2026-08-28',
            'status' => 'pending',
        ]);

        $controller = Mockery::mock(OrderController::class);
        $controller->shouldReceive('createOrderFromSchedule')
            ->once()
            ->withArgs(function (array $items, array $orderData): bool {
                $this->assertSame('2026-08-28', $orderData['delivery_date']);
                $this->assertSame(2, $items[0]['quantity']);

                return true;
            })
            ->andReturn($order);
        $this->app->instance(OrderController::class, $controller);

        $this->actingAs($admin)
            ->postJson(route('admin.text-order-import.confirm', $draft), [
                'sale_id' => $sale->id,
                'customer_id' => $customer->id,
                'delivery_date' => '2026-08-28',
                'items' => [[
                    'product_variant_id' => $variant->id,
                    'quantity' => 2,
                    'size_kg' => 2.5,
                    'unit_price' => 100000,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('delivery_date', '2026-08-28');

        $this->assertSame('2026-08-28', $draft->fresh()->delivery_date->toDateString());
    }

    private function adminAndSale(): array
    {
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);
        $saleRole = Role::query()->firstOrCreate(['name' => 'sale']);
        $admin = User::factory()->create(['name' => 'Admin nhập đơn']);
        $sale = User::factory()->create(['name' => 'Sale Nguyễn Văn A', 'zalo_name' => 'Sale A']);
        $admin->roles()->attach($adminRole);
        $sale->roles()->attach($saleRole);

        return [$admin, $sale];
    }
}
