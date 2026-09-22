<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopyOrderApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_copy_order_is_scheduled_for_tomorrow_and_has_an_approvable_step(): void
    {
        Carbon::setTestNow('2026-09-22 09:30:00');

        $sale = User::factory()->create(['name' => 'Sale copy']);
        $sale->roles()->attach(Role::query()->create(['name' => 'sale']));
        $manager = User::factory()->create(['name' => 'Manager duyệt']);
        $manager->roles()->attach(Role::query()->create(['name' => 'manager']));
        $workflow = ApprovalWorkflow::query()->create([
            'code' => 'copy-order-approval-test',
            'name' => 'Duyệt đơn sao chép',
            'is_active' => true,
            'applies_to' => [ApprovalWorkflow::ACTIVITY_ORDER_CREATE],
        ]);
        ApprovalStep::query()->create([
            'approval_flow_id' => $workflow->id,
            'step_order' => 1,
            'role_slug' => 'manager',
        ]);
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách cần giao đúng giờ',
            'delivery_time' => '07:15',
            'status' => 'active',
        ]);
        $source = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'SOURCE-COPY-2209',
            'status' => Order::STATUS_DELIVERED,
            'delivery_date' => '2026-09-20',
            'delivery_time' => '06:45',
            'total' => 0,
        ]);

        $response = $this->actingAs($sale)->get(route('site.orders.copy', $source));
        $copy = Order::query()->whereKeyNot($source->id)->firstOrFail();

        $response->assertRedirect(route('pages.my_orders.monitoring', [
            'tab' => 'today',
            'date' => '2026-09-22',
            'date_field' => 'business_date',
            'highlight' => $copy->id,
        ]));
        $this->assertSame('2026-09-22', $copy->created_at->toDateString());
        $this->assertSame('2026-09-23', $copy->delivery_date->toDateString());
        $this->assertSame('06:45', $copy->delivery_time);
        $this->assertSame(Order::STATUS_PENDING_MANAGER_APPROVAL, $copy->status);
        $this->assertNull($copy->copied_from_order_id);
        $this->assertDatabaseHas('approval_orders', [
            'order_id' => $copy->id,
            'approval_step_id' => ApprovalStep::query()->value('id'),
            'status' => 'pending',
        ]);

        $monitoringResponse = $this->actingAs($manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('pages.my_orders.monitoring', [
                'tab' => 'today',
                'date' => '2026-09-22',
                'date_field' => 'business_date',
            ]))
            ->assertOk()
            ->assertSee($copy->code)
            ->assertSee('Ngày tạo: 22/09/2026')
            ->assertSee('Ngày giao: 23/09/2026')
            ->assertSee('action="'.route('site.orders.approve', $copy).'"', false);

        $this->actingAs($manager)
            ->post(route('site.orders.approve', $copy), ['note' => 'Duyệt đơn copy'])
            ->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_APPROVED, $copy->fresh()->status);
    }
}
