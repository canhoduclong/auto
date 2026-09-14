<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountingReconciliationBusinessDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_can_identify_and_remove_an_admin_deleted_order_snapshot(): void
    {
        $accountRole = Role::create(['name' => 'accounting']);
        $accountant = User::factory()->create();
        $accountant->roles()->attach($accountRole);
        $sale = User::factory()->create(['name' => 'Sale đơn đã xóa']);
        $customer = Customer::create(['name' => 'Khách đơn đã mất', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ADMIN-DELETED-RECON-001',
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => now(),
            'total' => 350000,
            'amount_paid' => 100000,
            'shipping_fee' => 20000,
        ]);
        $deletedRecordId = DB::table('admin_deleted_orders')->insertGetId([
            'order_id' => $order->id,
            'order_code' => $order->code,
            'customer_id' => $customer->id,
            'sale_user_id' => $sale->id,
            'order_total' => $order->total,
            'recognized_revenue' => 0,
            'commission_amount' => 0,
            'reason' => 'Admin xóa nhầm dữ liệu thử nghiệm',
            'snapshot' => json_encode([
                'order' => $order->getAttributes(),
                'customer' => ['id' => $customer->id, 'name' => $customer->name],
                'sale' => ['id' => $sale->id, 'name' => $sale->name],
            ], JSON_UNESCAPED_UNICODE),
            'deleted_by' => $accountant->id,
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $order->delete();

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('ADMIN-DELETED-RECON-001')
            ->assertSee('Không còn dữ liệu đơn thực tế')
            ->assertSee('Admin xóa nhầm dữ liệu thử nghiệm')
            ->assertSee(route('accounting.reconciliation.exclude-missing', $deletedRecordId), false);

        $this->actingAs($accountant)
            ->delete(route('accounting.reconciliation.exclude-missing', $deletedRecordId), [
                'reason' => 'Đơn gốc không còn tồn tại',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('accounting_reconciliation_exclusions', [
            'deleted_order_id' => $deletedRecordId,
            'order_id' => null,
            'excluded_by' => $accountant->id,
        ]);
        $this->assertDatabaseHas('admin_deleted_orders', ['id' => $deletedRecordId]);

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertDontSee(route('accounting.reconciliation.exclude-missing', $deletedRecordId), false);
    }

    public function test_accounting_can_identify_and_remove_cancelled_or_trashed_orders_from_reconciliation(): void
    {
        $accountRole = Role::create(['name' => 'accounting']);
        $accountant = User::factory()->create();
        $accountant->roles()->attach($accountRole);
        $customer = Customer::create(['name' => 'Khách đơn không hiệu lực', 'status' => 'active']);
        $cancelled = Order::create([
            'customer_id' => $customer->id,
            'code' => 'RECON-CANCELLED-INVALID',
            'status' => Order::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'total' => 100000,
        ]);
        $trashed = Order::create([
            'customer_id' => $customer->id,
            'code' => 'RECON-TRASHED-INVALID',
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => now(),
            'trash_at' => now(),
            'total' => 200000,
        ]);

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('RECON-CANCELLED-INVALID')
            ->assertSee('Đơn đã hủy')
            ->assertSee('RECON-TRASHED-INVALID')
            ->assertSee('Không còn tồn tại')
            ->assertSee(route('accounting.reconciliation.exclude', $cancelled), false);

        $this->actingAs($accountant)
            ->delete(route('accounting.reconciliation.exclude', $cancelled), [
                'reason' => 'Đơn đã hủy không cần đối soát',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('accounting_reconciliation_exclusions', [
            'order_id' => $cancelled->id,
            'excluded_by' => $accountant->id,
            'reason' => 'Đơn đã hủy không cần đối soát',
        ]);
        $this->assertDatabaseHas('orders', ['id' => $cancelled->id]);

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertDontSee(route('accounting.reconciliation.exclude', $cancelled), false)
            ->assertSee('RECON-TRASHED-INVALID');
    }

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

    public function test_accounting_can_cancel_selected_reconciliations_in_bulk(): void
    {
        $accountRole = Role::create(['name' => 'accounting']);
        $accountant = User::factory()->create();
        $accountant->roles()->attach($accountRole);
        $sale = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Khách hủy đối soát hàng loạt',
            'status' => 'active',
            'commission_percent' => 2,
        ]);

        $orders = collect(['A', 'B', 'P'])->map(fn (string $suffix) => Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ORD-BULK-CANCEL-'.$suffix,
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => now(),
            'total' => 1000000,
        ]));

        foreach ($orders->take(2) as $order) {
            $this->actingAs($accountant)
                ->postJson(route('accounting.reconciliation.confirm', $order))
                ->assertOk();
        }

        $this->actingAs($accountant)
            ->get(route('accounting.reconciliation', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Hủy xác nhận đã chọn')
            ->assertSee('bulk-cancel');

        $response = $this->actingAs($accountant)
            ->postJson(route('accounting.reconciliation.bulk-cancel'), [
                'order_ids' => $orders->pluck('id')->all(),
                'reason' => 'Kế toán chọn nhầm ngày',
            ])
            ->assertOk()
            ->assertJsonCount(2, 'cancelled_order_ids')
            ->assertJsonCount(1, 'skipped');

        $this->assertEqualsCanonicalizing(
            $orders->take(2)->pluck('id')->all(),
            $response->json('cancelled_order_ids')
        );

        foreach ($orders->take(2) as $order) {
            $this->assertDatabaseHas('accounting_reconciliations', [
                'order_id' => $order->id,
                'status' => 'pending',
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);
            $this->assertDatabaseMissing('accounting_sales_entries', [
                'order_id' => $order->id,
                'source' => 'order',
            ]);
            $this->assertDatabaseMissing('order_commissions', ['order_id' => $order->id]);
            $this->assertDatabaseHas('order_histories', [
                'order_id' => $order->id,
                'action' => 'accounting_reconciliation_cancelled',
                'user_id' => $accountant->id,
                'note' => 'Kế toán hủy đối soát. Lý do: Kế toán chọn nhầm ngày',
            ]);
        }

        $this->assertDatabaseMissing('accounting_reconciliations', ['order_id' => $orders->last()->id]);
    }
}
