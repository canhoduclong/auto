<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringMyOrdersCustomerFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_orders_tab_renders_customer_picker_and_filters_orders_by_selected_customer(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $selectedCustomer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách được chọn',
            'phone' => '0901000001',
        ]);
        $otherCustomer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách không được chọn',
            'phone' => '0901000002',
        ]);

        Order::query()->create([
            'customer_id' => $selectedCustomer->id,
            'user_id' => $sale->id,
            'code' => 'FILTER-SELECTED',
            'total' => 100000,
            'status' => 'pending',
        ]);
        Order::query()->create([
            'customer_id' => $otherCustomer->id,
            'user_id' => $sale->id,
            'code' => 'FILTER-OTHER',
            'total' => 200000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'my_orders',
                'customer_ids' => [$selectedCustomer->id],
            ]));

        $response->assertOk()
            ->assertSee('id="openOrdersCustomerPicker"', false)
            ->assertSee('id="ordersCustomerPickerModal"', false)
            ->assertSee('Đang lọc:')
            ->assertSee('Khách được chọn')
            ->assertSee('FILTER-SELECTED')
            ->assertDontSee('FILTER-OTHER');
    }
}
