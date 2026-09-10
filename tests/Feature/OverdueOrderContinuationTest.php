<?php

namespace Tests\Feature;

use App\Models\{Customer, Inventory, InventoryReservation, Order, ProductVariant, Role, User, Warehouse};
use App\Services\ShipperAssignmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OverdueOrderContinuationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function actor(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => $role]));
        return $user;
    }

    private function overdueOrder(User $sale): Order
    {
        $order = Order::create([
            'user_id' => $sale->id,
            'customer_id' => Customer::create(['name' => 'Khách giao trễ', 'status' => 'active'])->id,
            'status' => Order::STATUS_OVERDUE_DELIVERY,
        ]);
        $order->forceFill(['created_at' => '2026-09-09 12:00:00'])->saveQuietly();
        return $order;
    }

    #[DataProvider('previousStages')]
    public function test_overdue_order_is_dispatchable_and_resumes_its_previous_stage(string $stage): void
    {
        Carbon::setTestNow('2026-09-11 12:00:00');
        Notification::fake();
        $manager = $this->actor('manager_shipper');
        $shipper = $this->actor('shipper');
        $order = $this->overdueOrder($this->actor('sale'));
        $order->histories()->create([
            'action' => 'mark_overdue_delivery', 'role' => 'system',
            'status_before' => $stage, 'status_after' => Order::STATUS_OVERDUE_DELIVERY,
        ]);
        $this->actingAs($manager)->get(route('shipper.manage-assignments', ['date' => '2026-09-09']))
            ->assertOk()->assertSee($order->code)->assertSee('Giao trễ — chờ điều phối tiếp');
        $this->post(route('shipper.assign-order', [$order, $shipper]), ['date' => '2026-09-09'])
            ->assertRedirect();
        $this->assertEquals($shipper->id, $order->fresh()->shipper_id);
        $this->assertTrue(app(ShipperAssignmentService::class)->publishDailySchedule(
            $shipper->id, '2026-09-09', $manager->id, 'manager_shipper'
        ));
        $this->assertSame($stage, $order->fresh()->status);
        $this->assertTrue($order->fresh()->skip_auto_cancel);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id, 'action' => 'resume_overdue_delivery', 'status_after' => $stage,
        ]);
        if ($stage === Order::STATUS_READY_TO_SHIP) {
            $order->histories()->create([
                'action' => 'schedule_confirmed', 'user_id' => $shipper->id,
                'role' => 'shipper', 'status_after' => $stage,
            ]);
            $this->actingAs($shipper)->postJson(route('shipper.accept', $order))->assertOk();
            $this->assertSame(Order::STATUS_DELIVERING, $order->fresh()->status);
        }
    }

    public static function previousStages(): array
    {
        return [['packed_waiting_pickup'], ['packing'], ['pending_leader_approval']];
    }

    #[DataProvider('cancelActors')]
    public function test_overdue_cancellation_permissions_and_reservation_release(string $actorType, bool $allowed): void
    {
        Carbon::setTestNow('2026-09-11 12:00:00');
        $sale = $this->actor('sale');
        $actor = $actorType === 'owner' ? $sale : $this->actor($actorType);
        $order = $this->overdueOrder($sale);
        $variant = ProductVariant::factory()->create();
        $inventory = Inventory::create([
            'warehouse_id' => Warehouse::factory()->create()->id,
            'product_variant_id' => $variant->id, 'quantity' => 5, 'reserved_quantity' => 2,
        ]);
        $item = $order->items()->create([
            'product_id' => $variant->product_id, 'product_variant_id' => $variant->id,
            'quantity' => 2, 'price' => 10000, 'total' => 20000,
        ]);
        InventoryReservation::create(['inventory_id' => $inventory->id, 'order_item_id' => $item->id, 'quantity' => 2]);
        if ($allowed) {
            $this->actingAs($actor)->get(route('pages.my_orders.monitoring', [
                'tab' => 'today', 'view' => 'cards', 'date_field' => 'business_date', 'date' => '2026-09-09',
            ]))->assertOk()->assertSee(route('site.orders.cancel', $order), false);
        }
        $this->actingAs($actor);
        $response = $allowed
            ? $this->post(route('site.orders.cancel', $order))
            : $this->postJson(route('site.orders.cancel', $order));
        if ($allowed) {
            $response->assertRedirect()->assertSessionHas('success');
            $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
            $this->assertEquals($actor->id, $order->fresh()->cancelled_by);
            $this->assertEquals(0, $inventory->fresh()->reserved_quantity);
            $this->assertDatabaseMissing('inventory_reservations', ['order_item_id' => $item->id]);
        } else {
            $response->assertForbidden();
            $this->assertSame(Order::STATUS_OVERDUE_DELIVERY, $order->fresh()->status);
            $this->assertEquals(2, $inventory->fresh()->reserved_quantity);
        }
        $this->assertEquals(5, $inventory->fresh()->quantity);
    }

    public static function cancelActors(): array
    {
        return [['owner', true], ['admin', true], ['sale', false]];
    }
}
