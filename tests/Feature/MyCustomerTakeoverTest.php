<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPriority;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCustomerTakeoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_can_reactivate_a_free_customer_they_previously_owned(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'current_owner_sale_id' => $sale->id,
            'current_cycle_no' => 1,
            'customer_status' => 'free',
            'free_from_date' => now()->subDay(),
            'name' => 'Khách tự do cần nhận lại',
        ]);

        CustomerPriority::query()->create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'priority_level' => 1,
            'care_score' => 0,
            'is_active' => true,
            'takeover_eligible' => true,
            'cycle_no' => 1,
        ]);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->postJson(route('my_customer.takeover', $customer));

        $response->assertOk()->assertJson(['success' => true]);

        $customer->refresh();
        $this->assertSame('active', $customer->customer_status);
        $this->assertSame($sale->id, (int) $customer->assigned_to);
        $this->assertSame($sale->id, (int) $customer->current_owner_sale_id);
        $this->assertNull($customer->free_from_date);
    }
}
