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

    public function test_restored_delivered_order_is_listed_on_its_actual_delivery_day(): void
    {
        $accountRole = Role::create(['name' => 'accounting']);
        $accountant = User::factory()->create();
        $accountant->roles()->attach($accountRole);
        $sale = User::factory()->create();
        $customer = Customer::create(['name' => 'Khách đơn phục hồi', 'status' => 'active']);
        $oldBusinessDate = now()->subDays(8)->toDateString();
        $actualDeliveryDate = now()->toDateString();

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ORD-RESTORED-DELIVERED-RECON',
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => $actualDeliveryDate.' 14:20:00',
            'total' => 980000,
            'skip_auto_cancel' => true,
        ]);
        Order::withoutTimestamps(function () use ($order, $oldBusinessDate): void {
            $order->forceFill([
                'created_at' => $oldBusinessDate.' 08:00:00',
                'updated_at' => $oldBusinessDate.' 09:00:00',
            ])->save();
        });
        $order->histories()->create([
            'action' => 'restore_cancelled_order',
            'user_id' => $accountant->id,
            'role' => 'admin',
            'status_before' => Order::STATUS_CANCELLED,
            'status_after' => Order::STATUS_READY_TO_SHIP,
            'note' => 'Phục hồi đơn để tiếp tục giao',
        ]);

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => $actualDeliveryDate]))
            ->assertOk()
            ->assertSee('ORD-RESTORED-DELIVERED-RECON');

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => $oldBusinessDate]))
            ->assertOk()
            ->assertDontSee('ORD-RESTORED-DELIVERED-RECON');
    }

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

    public function test_accounting_can_cancel_a_confirmed_order_reconciliation(): void
    {
        $accountRole = Role::create(['name' => 'accounting']);
        $accountant = User::factory()->create();
        $accountant->roles()->attach($accountRole);
        $sale = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Khách hủy đối soát',
            'status' => 'active',
            'commission_percent' => 2,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ORD-CANCEL-RECON',
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => now(),
            'total' => 1000000,
        ]);

        $this->actingAs($accountant)
            ->postJson(route('accounting.reconciliation.confirm', $order))
            ->assertOk()
            ->assertJsonPath('reconciliation.status', 'confirmed');

        $this->assertDatabaseHas('accounting_sales_entries', ['order_id' => $order->id]);
        $this->assertDatabaseHas('order_commissions', ['order_id' => $order->id, 'status' => 'confirmed']);

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Hủy đối soát')
            ->assertSee(route('accounting.reconciliation.cancel', $order), false);

        $this->actingAs($accountant)
            ->postJson(route('accounting.reconciliation.cancel', $order), ['reason' => 'Xác nhận nhầm đơn'])
            ->assertOk()
            ->assertJsonPath('reconciliation.status', 'pending');

        $this->assertDatabaseHas('accounting_reconciliations', [
            'order_id' => $order->id,
            'status' => 'pending',
            'confirmed_by' => null,
            'confirmed_at' => null,
        ]);
        $this->assertDatabaseMissing('accounting_sales_entries', ['order_id' => $order->id, 'source' => 'order']);
        $this->assertDatabaseMissing('order_commissions', ['order_id' => $order->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'amount_due' => 0]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'accounting_reconciliation_cancelled',
            'user_id' => $accountant->id,
        ]);
    }
}
