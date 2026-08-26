<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalExceptionApprovalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_historical_exception_flows_through_today_leader_and_manager_queues(): void
    {
        Carbon::setTestNow('2026-08-25 10:00:00');

        $team = Team::query()->create(['name' => 'Team đơn ngoại lệ']);
        $saleRole = Role::query()->create(['name' => 'sale']);
        $leaderRole = Role::query()->create(['name' => 'leader']);
        $managerRole = Role::query()->create(['name' => 'manager']);
        $warehouseRole = Role::query()->create(['name' => 'warehouse']);
        $warehouse = Warehouse::query()->create(['name' => 'Kho đơn ngoại lệ', 'status' => true]);

        $sale = User::factory()->create(['team_id' => $team->id]);
        $leader = User::factory()->create(['team_id' => $team->id]);
        $manager = User::factory()->create();
        $warehouseUser = User::factory()->create(['warehouse_id' => $warehouse->id]);
        $sale->roles()->attach($saleRole);
        $leader->roles()->attach($leaderRole);
        $manager->roles()->attach($managerRole);
        $warehouseUser->roles()->attach($warehouseRole);

        $workflow = ApprovalWorkflow::query()->create([
            'code' => 'historical-exception-approval',
            'name' => 'Duyệt đơn ngoại lệ ngày trước',
            'is_active' => true,
            'applies_to' => [ApprovalWorkflow::ACTIVITY_ORDER_CREATE],
        ]);
        ApprovalStep::query()->create([
            'approval_flow_id' => $workflow->id,
            'step_order' => 1,
            'role_slug' => 'leader',
        ]);
        ApprovalStep::query()->create([
            'approval_flow_id' => $workflow->id,
            'step_order' => 2,
            'role_slug' => 'manager',
        ]);

        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách có đơn bổ sung',
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'EXCEPTION-20260823',
            'status' => 'pending',
            'skip_auto_cancel' => true,
        ]);
        $order->forceFill(['created_at' => '2026-08-23 23:59:59'])->saveQuietly();
        app(ApprovalService::class)->initOrderApproval($order);

        $this->assertSame(Order::STATUS_PENDING_LEADER_APPROVAL, $order->fresh()->status);
        $this->actingAs($leader)
            ->get(route('pages.my_team_orders'))
            ->assertOk()
            ->assertSee('EXCEPTION-20260823');

        $this->actingAs($leader)
            ->post(route('site.orders.approve', $order), ['note' => 'Leader duyệt đơn ngoại lệ'])
            ->assertRedirect();

        $this->assertSame(Order::STATUS_PENDING_MANAGER_APPROVAL, $order->fresh()->status);
        $this->actingAs($manager)
            ->get(route('pages.all_team_orders'))
            ->assertOk()
            ->assertSee('EXCEPTION-20260823');

        $this->actingAs($manager)
            ->post(route('site.orders.approve', $order), ['note' => 'Manager duyệt đơn ngoại lệ'])
            ->assertRedirect();

        $this->assertSame(Order::STATUS_APPROVED, $order->fresh()->status);
        $this->actingAs($warehouseUser)
            ->get(route('warehouse.orders', ['date' => '2026-08-23']))
            ->assertOk()
            ->assertSee('EXCEPTION-20260823');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
