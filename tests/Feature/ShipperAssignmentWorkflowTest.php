<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_return_order_resumes_at_payment_completion(): void
    {
        $shipperRole = Role::create(['name' => 'shipper']);
        $shipper = User::factory()->create(['name' => 'Shipper Partial']);
        $shipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer Partial',
            'phone' => '0922222222',
            'status' => 'active',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'code' => 'ORD-PARTIAL-RETURN',
            'total' => 100000,
            'status' => Order::STATUS_DELIVERING,
        ]);

        OrderReturn::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'created_by' => $shipper->id,
            'status' => 'pending_warehouse',
            'reason' => 'customer_refused',
            'return_scope' => 'partial',
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.my-orders'))
            ->assertOk()
            ->assertSee('Chờ thu tiền &amp; hoàn tất', false)
            ->assertSee('Thu tiền &amp; hoàn tất', false)
            ->assertDontSee('>Trả hàng<', false);

        $this->actingAs($shipper)
            ->get(route('shipper.delivered-form', $order))
            ->assertOk()
            ->assertSee('Phần hàng trả lại đã được ghi nhận')
            ->assertSee('id="step-3-content" style="display:block;"', false);
    }

    public function test_manager_can_preassign_order_without_changing_warehouse_status(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);

        $manager = User::factory()->create(['name' => 'Manager Ship']) ;
        $manager->roles()->attach($managerRole->id);

        $shipper = User::factory()->create(['name' => 'Shipper A']);
        $shipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer A',
            'phone' => '0900000000',
            'status' => 'active',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-PREASSIGN-1',
            'total' => 0,
            'status' => Order::STATUS_READY_TO_PACK,
        ]);

        $response = $this->actingAs($manager)->post(route('shipper.assign-order.selected', $order), [
            'shipper_id' => $shipper->id,
        ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $customer->refresh();

        $this->assertSame($shipper->id, (int) $order->shipper_id);
        $this->assertSame($shipper->id, (int) $customer->default_shipper_id);
        $this->assertSame(Order::STATUS_READY_TO_PACK, $order->status);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'schedule_created',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $shipper->id,
            'notifiable_type' => 'user',
        ]);
    }

    public function test_manager_can_bulk_transfer_assigned_orders(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);

        $manager = User::factory()->create(['name' => 'Manager Ship']);
        $manager->roles()->attach($managerRole->id);

        $fromShipper = User::factory()->create(['name' => 'Shipper A']);
        $fromShipper->roles()->attach($shipperRole->id);

        $toShipper = User::factory()->create(['name' => 'Shipper B']);
        $toShipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer B',
            'phone' => '0911111111',
            'status' => 'active',
        ]);

        $orderOne = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-TRANSFER-1',
            'total' => 0,
            'status' => Order::STATUS_PACKING,
            'shipper_id' => $fromShipper->id,
        ]);

        $orderTwo = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-TRANSFER-2',
            'total' => 0,
            'status' => Order::STATUS_READY_TO_SHIP,
            'shipper_id' => $fromShipper->id,
        ]);

        $response = $this->actingAs($manager)->post(route('shipper.bulk-transfer-assignments'), [
            'from_shipper_id' => $fromShipper->id,
            'to_shipper_id' => $toShipper->id,
        ]);

        $response->assertSessionHas('success');

        $orderOne->refresh();
        $orderTwo->refresh();

        $this->assertSame($toShipper->id, (int) $orderOne->shipper_id);
        $this->assertSame($toShipper->id, (int) $orderTwo->shipper_id);
        $this->assertSame(Order::STATUS_PACKING, $orderOne->status);
        $this->assertSame(Order::STATUS_READY_TO_SHIP, $orderTwo->status);
    }

    public function test_manager_can_change_customer_default_shipper_and_transfer_pending_orders(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);

        $manager = User::factory()->create(['name' => 'Manager Ship']);
        $manager->roles()->attach($managerRole->id);

        $oldShipper = User::factory()->create(['name' => 'Shipper Old']);
        $oldShipper->roles()->attach($shipperRole->id);

        $newShipper = User::factory()->create(['name' => 'Shipper New']);
        $newShipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer Fixed Shipper',
            'phone' => '0933333333',
            'status' => 'active',
            'default_shipper_id' => $oldShipper->id,
        ]);

        $pendingOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $oldShipper->id,
            'code' => 'ORD-FIXED-PENDING',
            'total' => 0,
            'status' => Order::STATUS_READY_TO_PACK,
        ]);

        $deliveringOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $oldShipper->id,
            'code' => 'ORD-FIXED-DELIVERING',
            'total' => 0,
            'status' => Order::STATUS_DELIVERING,
        ]);

        $response = $this->actingAs($manager)->post(
            route('shipper.customers.default-shipper.update', $customer),
            [
                'shipper_id' => $newShipper->id,
                'transfer_pending_orders' => 1,
            ]
        );

        $response->assertSessionHas('success');

        $this->assertSame($newShipper->id, (int) $customer->fresh()->default_shipper_id);
        $this->assertSame($newShipper->id, (int) $pendingOrder->fresh()->shipper_id);
        $this->assertSame($oldShipper->id, (int) $deliveringOrder->fresh()->shipper_id);
    }
}
