<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOwnershipTest extends TestCase
{
    use RefreshDatabase;

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