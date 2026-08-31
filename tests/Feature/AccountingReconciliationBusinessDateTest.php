<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReconciliationBusinessDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_order_without_delivered_timestamp_is_listed_by_business_date(): void
    {
        $accountRole = Role::create(['name' => 'accounting']);
        $accountant = User::factory()->create();
        $accountant->roles()->attach($accountRole);
        $sale = User::factory()->create();
        $customer = Customer::create(['name' => 'Khách ngày nghiệp vụ', 'status' => 'active']);
        $businessDate = now()->subDays(6)->toDateString();

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ORD-BUSINESS-DATE-RECON',
            'status' => Order::STATUS_COMPLETED,
            'delivered_at' => null,
            'total' => 1250000,
        ]);
        Order::withoutTimestamps(function () use ($order, $businessDate): void {
            $order->forceFill([
                'created_at' => $businessDate.' 08:30:00',
                'updated_at' => $businessDate.' 10:30:00',
            ])->save();
        });

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => $businessDate]))
            ->assertOk()
            ->assertSee('ORD-BUSINESS-DATE-RECON')
            ->assertSee('Ngày nghiệp vụ');

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', [
                'date' => $businessDate,
                'date_field' => 'delivered_at',
            ]))
            ->assertOk()
            ->assertDontSee('ORD-BUSINESS-DATE-RECON');
    }
}
