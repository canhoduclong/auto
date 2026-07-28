<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_assign_customer_commission_from_customer_list(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin']));
        $sale = User::factory()->create();
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $firstCustomer = Customer::create(['name' => 'Khách hàng hoa hồng A', 'status' => 'active']);
        $secondCustomer = Customer::create(['name' => 'Khách hàng hoa hồng B', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $firstCustomer->id,
            'user_id' => $sale->id,
            'total' => 2000000,
            'status' => Order::STATUS_COMPLETED,
        ]);
        DB::table('order_commissions')->insert([
            'order_id' => $order->id,
            'sale_user_id' => $sale->id,
            'customer_id' => $firstCustomer->id,
            'order_total' => 2000000,
            'commission_percent' => 0,
            'commission_amount' => 0,
            'status' => 'confirmed',
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('customers.bulkUpdateCommission'), [
            'ids' => $firstCustomer->id.','.$secondCustomer->id,
            'commission_percent' => 3.5,
            'recalculate_existing' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['id' => $firstCustomer->id, 'commission_percent' => 3.5]);
        $this->assertDatabaseHas('customers', ['id' => $secondCustomer->id, 'commission_percent' => 3.5]);
        $this->assertDatabaseHas('order_commissions', [
            'order_id' => $order->id,
            'commission_percent' => 3.5,
            'commission_amount' => 70000,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'commission_percent_snapshot' => 3.5,
            'commission_amount_snapshot' => 70000,
        ]);
    }

    public function test_sale_only_sees_customers_still_owned_by_them(): void
    {
        Setting::set('customer_free_days', 7);

        $saleRole = Role::create(['name' => 'sale']);
        $indexPermission = Permission::create(['name' => 'customers.index']);
        $saleRole->permissions()->attach($indexPermission);

        $sale = User::factory()->create(['name' => 'Sale A']);
        $sale->roles()->attach($saleRole);

        $otherSale = User::factory()->create(['name' => 'Sale B']);
        $otherSale->roles()->attach($saleRole);

        Customer::create([
            'name' => 'Managed Customer',
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'assigned_at' => now()->subDays(2),
            'status' => 'active',
        ]);

        Customer::create([
            'name' => 'Expired Customer',
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'assigned_at' => now()->subDays(10),
            'status' => 'active',
        ]);

        Customer::create([
            'name' => 'Other Sale Customer',
            'user_id' => $otherSale->id,
            'assigned_to' => $otherSale->id,
            'assigned_at' => now()->subDay(),
            'status' => 'active',
        ]);

        Customer::create([
            'name' => 'Free Customer',
            'assigned_to' => null,
            'assigned_at' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($sale)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Managed Customer');
        $response->assertDontSee('Expired Customer');
        $response->assertDontSee('Other Sale Customer');
        $response->assertDontSee('Free Customer');
    }

    public function test_admin_can_assign_customer_to_sale_and_refresh_assignment_time(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $saleRole = Role::create(['name' => 'sale']);

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $customer = Customer::create([
            'name' => 'Customer To Assign',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('customers.assign-sale', $customer), [
            'assigned_to' => $sale->id,
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'assigned_to' => $sale->id,
        ]);

        $customer->refresh();
        $this->assertNotNull($customer->assigned_at);
    }
}
