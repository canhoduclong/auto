<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringOrderSupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_view_can_attach_and_detach_an_order_supplier(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách danh sách gọn',
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'LIST-SUPPLIER-001',
            'status' => Order::STATUS_ORDER_PLACED,
            'total' => 6700000,
            'delivery_date' => now()->toDateString(),
        ]);
        $supplier = Supplier::query()->create([
            'name' => 'San Hà',
            'is_active' => true,
        ]);

        $listResponse = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'date' => now()->toDateString(),
                'date_field' => 'business_date',
                'view' => 'list',
            ]));

        $listResponse->assertOk()
            ->assertSee('Khách danh sách gọn')
            ->assertSee('Chưa gắn')
            ->assertSee('San Hà')
            ->assertSee(route('pages.my_orders.monitoring.supplier', $order), false);

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->put(route('pages.my_orders.monitoring.supplier', $order), ['supplier_id' => $supplier->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->put(route('pages.my_orders.monitoring.supplier', $order), ['supplier_id' => null])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'supplier_id' => null,
        ]);
    }
}
