<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseOrdersCancelledVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-03 16:25:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cancelled_and_trashed_orders_are_excluded_from_the_packing_page_and_daily_count(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho Long An', 'status' => true]);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach(Role::query()->create(['name' => 'warehouse']));
        $sale = User::factory()->create(['name' => 'Sale Nguyễn An']);
        $customer = Customer::query()->create(['name' => 'Khách đóng hàng', 'status' => 'active']);

        $visibleOrder = $this->order($customer, $warehouse, 'VISIBLE-PACKING', Order::STATUS_READY_TO_PACK);
        $visibleOrder->update(['user_id' => $sale->id]);
        $this->order($customer, $warehouse, 'HIDDEN-CANCELLED', Order::STATUS_CANCELLED);
        $this->order($customer, $warehouse, 'HIDDEN-TRASHED', Order::STATUS_READY_TO_PACK, now());

        $this->actingAs($user)
            ->get(route('warehouse.orders', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('VISIBLE-PACKING')
            ->assertSee('Sale: Sale Nguyễn An')
            ->assertDontSee('HIDDEN-CANCELLED')
            ->assertDontSee('HIDDEN-TRASHED')
            ->assertSee('Tổng đơn: 1');
    }

    public function test_cancelled_status_cannot_be_forced_into_the_packing_page_by_query_string(): void
    {
        $warehouse = Warehouse::query()->create(['name' => 'Kho Long An', 'status' => true]);
        $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $user->roles()->attach(Role::query()->create(['name' => 'warehouse']));
        $customer = Customer::query()->create(['name' => 'Khách đã hủy', 'status' => 'active']);
        $this->order($customer, $warehouse, 'HIDDEN-CANCELLED', Order::STATUS_CANCELLED);

        $this->actingAs($user)
            ->get(route('warehouse.orders', [
                'date' => now()->toDateString(),
                'status' => Order::STATUS_CANCELLED,
            ]))
            ->assertOk()
            ->assertDontSee('HIDDEN-CANCELLED')
            ->assertSee('Không có đơn nào cần xử lý lúc này.');
    }

    private function order(
        Customer $customer,
        Warehouse $warehouse,
        string $code,
        string $status,
        ?Carbon $trashAt = null
    ): Order {
        return Order::query()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'code' => $code,
            'status' => $status,
            'delivery_date' => now()->toDateString(),
            'skip_auto_cancel' => true,
            'trash_at' => $trashAt,
        ]);
    }
}
