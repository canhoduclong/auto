<?php

namespace Tests\Feature;

use App\Models\AccountingReconciliation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\TextOrderDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_menu_has_dashboard_and_does_not_render_fake_sequence_slots(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', ['tab' => 'today']));

        $response->assertOk()
            ->assertSee('Bảng điều khiển')
            ->assertSee(route('pages.my_dashboard'), false)
            ->assertDontSee('aria-label="Điều hướng nhanh theo số thứ tự đơn"', false);
    }

    public function test_order_cards_and_sequence_navigation_use_workflow_status_colors(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách kiểm tra màu trạng thái',
            'status' => 'active',
        ]);

        $statuses = [
            Order::STATUS_ORDER_PLACED => 'pending',
            Order::STATUS_APPROVED => 'approved',
            Order::STATUS_PACKED => 'packed',
            Order::STATUS_DELIVERING => 'transit',
            Order::STATUS_DELIVERED => 'delivered',
        ];

        $sequence = 0;
        foreach (array_keys($statuses) as $status) {
            $sequence++;
            Order::query()->create([
                'customer_id' => $customer->id,
                'user_id' => $sale->id,
                'code' => 'MONITOR-COLOR-' . $sequence,
                'status' => $status,
                'daily_sequence' => $sequence,
            ]);
        }

        $accountedOrder = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'MONITOR-COLOR-6',
            'status' => Order::STATUS_COMPLETED,
            'daily_sequence' => 6,
        ]);
        AccountingReconciliation::query()->create([
            'order_id' => $accountedOrder->id,
            'sale_id' => $sale->id,
            'status' => AccountingReconciliation::STATUS_CONFIRMED,
        ]);
        TextOrderDraft::query()->create([
            'created_by' => $sale->id,
            'draft_scope' => TextOrderDraft::SCOPE_SALE_PRIVATE,
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'raw_text' => 'Đơn mẫu đã có',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'view' => 'cards',
                'date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertDontSee('data-sample-url=', false);
        foreach (array_merge(array_values($statuses), ['accounted']) as $visualState) {
            $response
                ->assertSee('monitor-sequence status-' . $visualState, false)
                ->assertSee('monitor-panel monitor-order status-' . $visualState, false);
        }
    }

    public function test_refresh_resequences_open_and_closed_orders_together_without_duplicates(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách kiểm tra làm mới số thứ tự',
            'status' => 'active',
        ]);
        $date = now()->startOfDay();
        $orders = collect([
            [Order::STATUS_READY_TO_PACK, 1],
            [Order::STATUS_PACKED, 1],
            [Order::STATUS_COMPLETED, 2],
            [Order::STATUS_DELIVERED, 2],
        ])->map(function (array $row, int $index) use ($sale, $customer, $date): Order {
            $order = new Order;
            $order->forceFill([
                'customer_id' => $customer->id,
                'user_id' => $sale->id,
                'code' => 'MONITOR-REFRESH-' . ($index + 1),
                'status' => $row[0],
                'daily_sequence' => $row[1],
                'created_at' => $date->copy()->addHours($index + 8),
                'updated_at' => $date->copy()->addHours($index + 8),
            ])->save();

            return $order;
        });

        $this->actingAs($sale)
            ->withSession(['active_role' => 'sale'])
            ->post(route('pages.my_orders.monitoring.refresh_sequence'), [
                'date' => $date->toDateString(),
                'date_field' => 'business_date',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'sửa 2 số trùng'));

        $this->assertSame(
            [1, 2, 3, 4],
            $orders->map(fn (Order $order): int => (int) $order->fresh()->daily_sequence)->all()
        );
    }
}
