<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperShippingFeeUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_shipper_can_save_order_shipping_fee_from_assignment_page(): void
    {
        $role = Role::create(['name' => 'manager_shipper']);
        $manager = User::factory()->create();
        $manager->roles()->attach($role);
        $customer = Customer::create([
            'name' => 'Khách kiểm tra phí ship',
            'phone' => '0900000999',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'SHIP-FEE-TEST',
            'status' => Order::STATUS_READY_TO_PACK,
            'total' => 0,
            'shipping_fee' => 0,
            'charge_shipping_fee' => false,
        ]);

        $this->actingAs($manager)
            ->postJson(route('shipper.update-fee', $order), [
                'shipping_fee' => 45000,
            ])
            ->assertOk()
            ->assertJsonPath('order_id', $order->id)
            ->assertJsonPath('shipping_fee', 45000);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'shipping_fee' => 45000,
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'shipping_fee_updated',
            'user_id' => $manager->id,
        ]);
    }
}
