<?php

namespace Tests\Feature;

use App\Models\AccountingSalesImportBatch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\ShipperAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipperAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_freshly_published_schedule_is_waiting_instead_of_immediately_changed(): void
    {
        $manager = User::factory()->create();
        $shipper = User::factory()->create();
        $customer = Customer::create(['name' => 'Khách kiểm tra snapshot', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'SNAPSHOT-WAITING-001',
            'status' => Order::STATUS_READY_TO_SHIP,
            'daily_sequence' => 1,
        ]);
        $plan = [[
            'shipper_id' => $shipper->id,
            'routes' => [['name' => 'Lộ trình 1', 'orders' => [['order_id' => $order->id]]]],
        ]];

        $this->assertTrue(app(ShipperAssignmentService::class)->publishDailySchedule(
            $shipper->id, now()->toDateString(), $manager->id, 'manager_shipper', null, $plan
        ));

        $history = $order->histories()->where('action', 'schedule_created')->latest('id')->firstOrFail();
        $controller = app(\App\Http\Controllers\ShipperDashboardController::class);
        $snapshotMethod = new \ReflectionMethod($controller, 'buildDeliveryScheduleSnapshot');
        $statusMethod = new \ReflectionMethod($controller, 'deliveryScheduleStatus');
        $snapshot = $snapshotMethod->invoke($controller, collect([$order->fresh()]));
        $hash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $savedSnapshot = json_decode($history->schedule_snapshot, true);
        $this->assertSame($snapshot, array_values(array_filter($savedSnapshot, fn ($row) => isset($row['order_id']))));
        $this->assertSame('waiting', $statusMethod->invoke($controller, $history, $hash, $snapshot));
    }

    public function test_active_delivery_remains_eligible_when_dispatch_is_refreshed(): void
    {
        $order = new Order(['status' => Order::STATUS_DELIVERING]);
        $controller = app(\App\Http\Controllers\ShipperDashboardController::class);
        $method = new \ReflectionMethod($controller, 'isAssignmentEligible');

        $this->assertTrue($method->invoke($controller, $order));
        $this->assertContains(Order::STATUS_DELIVERING, app(ShipperAssignmentService::class)->assignmentStatuses());
    }

    public function test_completed_stop_is_not_reported_as_removed_from_route(): void
    {
        $savedSnapshot = [
            ['order_id' => 501, 'daily_sequence' => 1, 'delivery_date' => null, 'delivery_time' => null],
            ['order_id' => 502, 'daily_sequence' => 2, 'delivery_date' => null, 'delivery_time' => '09:00'],
        ];
        $currentSnapshot = [
            ['order_id' => 502, 'daily_sequence' => 2, 'delivery_date' => null, 'delivery_time' => '09:00'],
        ];
        $history = new OrderHistory([
            'action' => 'schedule_confirmed',
            'schedule_snapshot_hash' => hash('sha256', json_encode($savedSnapshot)),
            'schedule_snapshot' => json_encode($savedSnapshot),
        ]);
        $controller = app(\App\Http\Controllers\ShipperDashboardController::class);
        $statusMethod = new \ReflectionMethod($controller, 'deliveryScheduleStatus');
        $summaryMethod = new \ReflectionMethod($controller, 'deliveryScheduleChangeSummary');
        $currentHash = hash('sha256', json_encode($currentSnapshot));

        $this->assertSame('confirmed', $statusMethod->invoke(
            $controller, $history, $currentHash, $currentSnapshot, [501]
        ));
        $this->assertStringNotContainsString('Gỡ:', $summaryMethod->invoke(
            $controller, $history, $currentSnapshot, collect(), [501]
        ));
    }

    public function test_reordering_or_renumbering_stops_does_not_require_schedule_confirmation_again(): void
    {
        $savedSnapshot = [
            ['order_id' => 501, 'daily_sequence' => 1, 'delivery_date' => null, 'delivery_time' => '10:00'],
            ['order_id' => 502, 'daily_sequence' => 2, 'delivery_date' => null, 'delivery_time' => '08:00'],
        ];
        $currentSnapshot = [
            ['order_id' => 502, 'daily_sequence' => 20, 'delivery_date' => null, 'delivery_time' => '08:00'],
            ['order_id' => 501, 'daily_sequence' => 10, 'delivery_date' => null, 'delivery_time' => '10:00'],
        ];
        $history = new OrderHistory([
            'action' => 'schedule_confirmed',
            'schedule_snapshot_hash' => hash('sha256', json_encode($savedSnapshot)),
            'schedule_snapshot' => json_encode($savedSnapshot),
        ]);
        $controller = app(\App\Http\Controllers\ShipperDashboardController::class);
        $statusMethod = new \ReflectionMethod($controller, 'deliveryScheduleStatus');

        $this->assertSame('confirmed', $statusMethod->invoke(
            $controller,
            $history,
            hash('sha256', json_encode($currentSnapshot)),
            $currentSnapshot
        ));
    }

    public function test_route_plan_comparison_ignores_stop_sequence_and_array_order(): void
    {
        $oldPlan = [[
            'shipper_id' => 10,
            'shipper_name' => 'Ship A',
            'routes' => [['name' => 'Tuyến A', 'orders' => [
                ['order_id' => 101, 'sequence' => 1, 'final_fee' => 10000],
                ['order_id' => 102, 'sequence' => 2, 'final_fee' => 20000],
            ]]],
        ]];
        $renumberedPlan = [[
            'shipper_id' => 10,
            'shipper_name' => 'Ship A đổi tên hiển thị',
            'routes' => [['name' => 'Tuyến A', 'orders' => [
                ['order_id' => 102, 'sequence' => 20, 'final_fee' => 20000],
                ['order_id' => 101, 'sequence' => 10, 'final_fee' => 10000],
            ]]],
        ]];
        $controller = app(\App\Http\Controllers\ShipperDashboardController::class);
        $canonical = new \ReflectionMethod($controller, 'canonicalRoutePlan');

        $this->assertSame(
            $canonical->invoke($controller, $oldPlan),
            $canonical->invoke($controller, $renumberedPlan)
        );
    }

    public function test_packed_order_with_delivery_schedule_is_still_available_for_warehouse_transfer(): void
    {
        $warehouse = Warehouse::create(['name' => 'Kho nguồn']);
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Khách điều chuyển', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'code' => 'TRANSFER-WITH-ROUTE-001',
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        $order->histories()->create([
            'action' => 'schedule_created',
            'user_id' => $user->id,
            'role' => 'manager_shipper',
            'status_before' => $order->status,
            'status_after' => $order->status,
        ]);

        $controller = app(\App\Http\Controllers\Warehouse\OrderTransferController::class);
        $method = new \ReflectionMethod($controller, 'transferableOrders');
        $query = $method->invoke($controller, $warehouse->id);

        $this->assertTrue($query->whereKey($order->id)->exists());
    }

    public function test_only_shipper_with_changed_route_is_selected_for_resend(): void
    {
        $firstPlan = [
            ['shipper_id' => 10, 'shipper_name' => 'Ship A', 'routes' => [['name' => 'Tuyến A', 'orders' => [['order_id' => 101]]]]],
            ['shipper_id' => 20, 'shipper_name' => 'Ship B', 'routes' => [['name' => 'Tuyến B', 'orders' => [['order_id' => 202]]]]],
        ];
        \App\Models\ShipperDispatchHistory::create([
            'schedule_date' => '2026-09-15',
            'version' => 1,
            'route_plan' => $firstPlan,
            'orders_count' => 2,
            'published_at' => now(),
        ]);
        $changedPlan = $firstPlan;
        $changedPlan[1]['routes'][0]['orders'][] = ['order_id' => 203];

        $method = new \ReflectionMethod(
            app(\App\Http\Controllers\ShipperDashboardController::class),
            'changedShipperIdsForRoutePlan'
        );
        $changedShipperIds = $method->invoke(
            app(\App\Http\Controllers\ShipperDashboardController::class),
            '2026-09-15',
            $changedPlan
        );

        $this->assertSame([20], $changedShipperIds);
    }

    public function test_manager_can_review_and_print_published_delivery_notes(): void
    {
        $manager = User::factory()->create(['name' => 'Quản lý in phiếu']);
        $manager->roles()->attach(Role::create(['name' => 'manager_shipper']));
        $shipper = User::factory()->create(['name' => 'Shipper tuyến in']);
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách cần in', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'PRINT-DELIVERY-001',
            'status' => Order::STATUS_READY_TO_SHIP,
            'total' => 125000,
        ]);
        \App\Models\ShipperDispatchHistory::create([
            'schedule_date' => now()->toDateString(),
            'version' => 1,
            'route_plan' => [[
                'shipper_id' => $shipper->id,
                'shipper_name' => $shipper->name,
                'routes' => [['name' => 'Chuyến sáng', 'orders' => [['order_id' => $order->id]]]],
            ]],
            'orders_count' => 1,
            'created_by' => $manager->id,
            'published_at' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('shipper.manage-assignments.review.index'))
            ->assertOk()
            ->assertSee('Review &amp; In ấn', false)
            ->assertSee('PRINT-DELIVERY-001')
            ->assertSee('Chuyến sáng')
            ->assertSee('Chưa in');

        $this->actingAs($manager)
            ->post(route('shipper.manage-assignments.review.print'), [
                'date' => now()->toDateString(),
                'order_ids' => [$order->id],
            ])
            ->assertOk()
            ->assertSee('PHIẾU GIAO HÀNG')
            ->assertSee('PRINT-DELIVERY-001');

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'delivery_note_printed',
            'user_id' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('shipper.manage-assignments.review.index'))
            ->assertOk()
            ->assertSee('Đã In');
    }

    public function test_packing_update_does_not_invalidate_an_already_confirmed_route(): void
    {
        $savedSnapshot = [[
            'order_id' => 15,
            'daily_sequence' => 2,
            'delivery_date' => '2026-09-15',
            'delivery_time' => '08:30',
            'updated_at' => '2026-09-15 07:00:00',
        ]];
        $currentSnapshot = [[
            'order_id' => 15,
            'daily_sequence' => 2,
            'delivery_date' => '2026-09-15',
            'delivery_time' => '08:30',
        ]];
        $history = new OrderHistory([
            'action' => 'schedule_confirmed',
            'schedule_snapshot_hash' => hash('sha256', json_encode($savedSnapshot)),
            'schedule_snapshot' => json_encode($savedSnapshot),
        ]);
        $controller = app(\App\Http\Controllers\ShipperDashboardController::class);
        $method = new \ReflectionMethod($controller, 'deliveryScheduleStatus');

        $status = $method->invoke(
            $controller,
            $history,
            hash('sha256', json_encode($currentSnapshot)),
            $currentSnapshot
        );

        $this->assertSame('confirmed', $status);
    }

    public function test_manager_page_keeps_confirmed_daily_orders_and_disables_resend_until_changed(): void
    {
        $manager = User::factory()->create();
        $manager->roles()->attach(Role::create(['name' => 'manager_shipper']));
        $shipper = User::factory()->create(['name' => 'Shipper đã nhận lịch', 'show_in_shipper_assignment' => true]);
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách đã giao', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'CONFIRMED-DAILY-ORDER',
            'status' => Order::STATUS_DELIVERED,
            'daily_sequence' => 1,
        ]);
        $controller = app(\App\Http\Controllers\ShipperDashboardController::class);
        $snapshotMethod = new \ReflectionMethod($controller, 'buildDeliveryScheduleSnapshot');
        $snapshot = $snapshotMethod->invoke($controller, collect([$order->fresh()]));
        $routePlan = [[
            'shipper_id' => $shipper->id,
            'shipper_name' => $shipper->name,
            'routes' => [['name' => 'Lộ trình 1', 'orders' => [['order_id' => $order->id]]]],
        ]];
        $order->histories()->create([
            'action' => 'schedule_confirmed',
            'user_id' => $shipper->id,
            'role' => 'shipper',
            'schedule_snapshot_hash' => hash('sha256', json_encode($snapshot)),
            'schedule_snapshot' => json_encode(array_merge($snapshot, [['route_plan' => $routePlan[0]]])),
        ]);
        \App\Models\ShipperDispatchHistory::create([
            'schedule_date' => now()->toDateString(),
            'version' => 1,
            'route_plan' => $routePlan,
            'orders_count' => 1,
            'created_by' => $manager->id,
            'published_at' => now(),
        ]);

        $this->actingAs($manager)
            ->get(route('shipper.manage-assignments', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('CONFIRMED-DAILY-ORDER')
            ->assertSee('Đã Xác Nhận, lúc')
            ->assertSee('route-zone-card is-confirmed', false)
            ->assertSee('id="routeReviewButton"', false)
            ->assertSee('disabled', false);
    }

    public function test_manager_page_marks_completed_orders_from_legacy_confirmed_schedule(): void
    {
        $manager = User::factory()->create();
        $manager->roles()->attach(Role::create(['name' => 'manager_shipper']));
        $shipper = User::factory()->create([
            'name' => 'Shipper lịch sử cũ',
            'show_in_shipper_assignment' => true,
        ]);
        $shipper->roles()->attach(Role::create(['name' => 'shipper']));
        $customer = Customer::create(['name' => 'Khách đã hoàn thành', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $shipper->id,
            'code' => 'LEGACY-COMPLETED-ORDER',
            'status' => Order::STATUS_DELIVERED,
            'daily_sequence' => 1,
            'delivered_at' => now(),
        ]);
        $snapshot = [[
            'order_id' => $order->id,
            'daily_sequence' => 1,
            'delivery_date' => $order->delivery_date?->toDateString(),
            'delivery_time' => $order->delivery_time,
        ]];
        $order->histories()->create([
            'action' => 'schedule_confirmed',
            'user_id' => $shipper->id,
            'role' => 'shipper',
            'schedule_snapshot_hash' => hash('sha256', json_encode($snapshot)),
            'schedule_snapshot' => json_encode($snapshot),
        ]);

        // Legacy schedules have no shipper_dispatch_histories record.
        $this->assertDatabaseCount('shipper_dispatch_histories', 0);

        $this->actingAs($manager)
            ->get(route('shipper.manage-assignments', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('LEGACY-COMPLETED-ORDER')
            ->assertSee('Đã Xác Nhận, lúc')
            ->assertSee('trip-order-completed', false)
            ->assertSee('Đã giao / Hoàn thành');
    }

    public function test_confirmed_ready_order_is_visible_in_shippers_my_orders(): void
    {
        $shipperRole = Role::create(['name' => 'shipper']);
        $shipper = User::factory()->create(['name' => 'Leloi Huynh']);
        $shipper->roles()->attach($shipperRole->id);
        $customer = Customer::create([
            'name' => 'Khách trong lịch trình',
            'phone' => '0912345678',
            'status' => 'active',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'code' => 'ORD-CONFIRMED-READY',
            'total' => 100000,
            'status' => Order::STATUS_READY_TO_SHIP,
        ]);
        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'schedule_confirmed',
            'user_id' => $shipper->id,
            'role' => 'shipper',
            'status_before' => Order::STATUS_READY_TO_SHIP,
            'status_after' => Order::STATUS_READY_TO_SHIP,
            'note' => 'Shipper xác nhận lịch trình.',
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.my-orders'))
            ->assertOk()
            ->assertSee('ORD-CONFIRMED-READY')
            ->assertSee('Chờ ship nhận')
            ->assertSee('Nhận đơn để giao');
    }

    public function test_received_warehouse_transfer_does_not_complete_imported_order_before_customer_delivery(): void
    {
        $shipperRole = Role::create(['name' => 'shipper']);
        $shipper = User::factory()->create(['name' => 'Ship Dương']);
        $shipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Khách chờ giao từ kho chiến lược',
            'phone' => '0909090909',
            'address' => 'Long An',
            'status' => 'active',
        ]);
        $sourceWarehouse = Warehouse::create(['name' => 'Kho Long An', 'status' => true]);
        $targetWarehouse = Warehouse::create(['name' => 'Kho Chiến Lược', 'status' => true]);
        $batch = AccountingSalesImportBatch::create([
            'imported_by' => $shipper->id,
            'source_hash' => hash('sha256', 'pending-customer-delivery'),
            'row_count' => 1,
            'total_amount' => 100000,
            'raw_text' => 'test',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'warehouse_id' => $targetWarehouse->id,
            'code' => 'ORD-WAREHOUSE-LEG-DONE',
            'total' => 100000,
            'status' => Order::STATUS_COMPLETED,
            'accounting_sales_import_batch_id' => $batch->id,
            'needs_operational_completion' => true,
        ]);

        WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $sourceWarehouse->id,
            'target_warehouse_id' => $targetWarehouse->id,
            'shipper_id' => $shipper->id,
            'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            'received_at' => now(),
        ]);
        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'schedule_confirmed',
            'user_id' => $shipper->id,
            'role' => 'shipper',
            'status_before' => Order::STATUS_COMPLETED,
            'status_after' => Order::STATUS_COMPLETED,
            'note' => 'Shipper xác nhận chặng giao khách.',
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.my-orders'))
            ->assertOk()
            ->assertSee('Chờ ship nhận giao khách')
            ->assertSee('Nhận đơn để giao khách')
            ->assertDontSee('Đơn đã hoàn thành, không còn thao tác');

        $this->actingAs($shipper)
            ->post(route('shipper.accept', $order))
            ->assertRedirect(route('shipper.my-orders'));
        $this->assertSame(Order::STATUS_DELIVERING, $order->fresh()->status);

        $this->actingAs($shipper)
            ->post(route('shipper.mark-delivered', $order), [
                'collected_amount' => 100000,
                'has_partial_return' => 0,
            ])
            ->assertRedirect(route('shipper.available', ['date' => $order->created_at->toDateString()]));

        $order->refresh();
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertFalse($order->needs_operational_completion);
        $this->assertNotNull($order->operational_completed_at);
    }

    public function test_partial_return_order_resumes_at_payment_completion(): void
    {
        $shipperRole = Role::create(['name' => 'shipper']);
        $shipper = User::factory()->create(['name' => 'Shipper Partial']);
        $shipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer Partial',
            'phone' => '0922222222',
            'status' => 'active',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $shipper->id,
            'shipper_id' => $shipper->id,
            'code' => 'ORD-PARTIAL-RETURN',
            'total' => 100000,
            'status' => Order::STATUS_DELIVERING,
        ]);

        OrderReturn::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'created_by' => $shipper->id,
            'status' => 'pending_warehouse',
            'reason' => 'customer_refused',
            'return_scope' => 'partial',
        ]);

        $this->actingAs($shipper)
            ->get(route('shipper.my-orders'))
            ->assertOk()
            ->assertSee('Chờ thu tiền &amp; hoàn tất', false)
            ->assertSee('Thu tiền &amp; hoàn tất', false)
            ->assertDontSee('>Trả hàng<', false);

        $this->actingAs($shipper)
            ->get(route('shipper.delivered-form', $order))
            ->assertOk()
            ->assertSee('Phần hàng trả lại đã được ghi nhận')
            ->assertSee('id="step-3-content" style="display:block;"', false);
    }

    public function test_manager_can_preassign_order_without_changing_warehouse_status(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);

        $manager = User::factory()->create(['name' => 'Manager Ship']);
        $manager->roles()->attach($managerRole->id);

        $shipper = User::factory()->create(['name' => 'Shipper A']);
        $shipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer A',
            'phone' => '0900000000',
            'status' => 'active',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-PREASSIGN-1',
            'total' => 0,
            'status' => Order::STATUS_READY_TO_PACK,
        ]);

        $response = $this->actingAs($manager)->post(route('shipper.assign-order.selected', $order), [
            'shipper_id' => $shipper->id,
        ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $customer->refresh();

        $this->assertSame($shipper->id, (int) $order->shipper_id);
        $this->assertSame($shipper->id, (int) $customer->default_shipper_id);
        $this->assertSame(Order::STATUS_READY_TO_PACK, $order->status);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'action' => 'schedule_created',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $shipper->id,
            'notifiable_type' => 'user',
        ]);
    }

    public function test_manager_can_bulk_transfer_assigned_orders(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);

        $manager = User::factory()->create(['name' => 'Manager Ship']);
        $manager->roles()->attach($managerRole->id);

        $fromShipper = User::factory()->create(['name' => 'Shipper A']);
        $fromShipper->roles()->attach($shipperRole->id);

        $toShipper = User::factory()->create(['name' => 'Shipper B']);
        $toShipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer B',
            'phone' => '0911111111',
            'status' => 'active',
        ]);

        $orderOne = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-TRANSFER-1',
            'total' => 0,
            'status' => Order::STATUS_PACKING,
            'shipper_id' => $fromShipper->id,
        ]);

        $orderTwo = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'code' => 'ORD-TRANSFER-2',
            'total' => 0,
            'status' => Order::STATUS_READY_TO_SHIP,
            'shipper_id' => $fromShipper->id,
        ]);

        $response = $this->actingAs($manager)->post(route('shipper.bulk-transfer-assignments'), [
            'from_shipper_id' => $fromShipper->id,
            'to_shipper_id' => $toShipper->id,
        ]);

        $response->assertSessionHas('success');

        $orderOne->refresh();
        $orderTwo->refresh();

        $this->assertSame($toShipper->id, (int) $orderOne->shipper_id);
        $this->assertSame($toShipper->id, (int) $orderTwo->shipper_id);
        $this->assertSame(Order::STATUS_PACKING, $orderOne->status);
        $this->assertSame(Order::STATUS_READY_TO_SHIP, $orderTwo->status);
    }

    public function test_manager_can_change_customer_default_shipper_and_transfer_pending_orders(): void
    {
        $managerRole = Role::create(['name' => 'manager_shipper']);
        $shipperRole = Role::create(['name' => 'shipper']);

        $manager = User::factory()->create(['name' => 'Manager Ship']);
        $manager->roles()->attach($managerRole->id);

        $oldShipper = User::factory()->create(['name' => 'Shipper Old']);
        $oldShipper->roles()->attach($shipperRole->id);

        $newShipper = User::factory()->create(['name' => 'Shipper New']);
        $newShipper->roles()->attach($shipperRole->id);

        $customer = Customer::create([
            'name' => 'Customer Fixed Shipper',
            'phone' => '0933333333',
            'status' => 'active',
            'default_shipper_id' => $oldShipper->id,
        ]);

        $pendingOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $oldShipper->id,
            'code' => 'ORD-FIXED-PENDING',
            'total' => 0,
            'status' => Order::STATUS_READY_TO_PACK,
        ]);

        $deliveringOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $manager->id,
            'shipper_id' => $oldShipper->id,
            'code' => 'ORD-FIXED-DELIVERING',
            'total' => 0,
            'status' => Order::STATUS_DELIVERING,
        ]);

        $response = $this->actingAs($manager)->post(
            route('shipper.customers.default-shipper.update', $customer),
            [
                'shipper_id' => $newShipper->id,
                'transfer_pending_orders' => 1,
            ]
        );

        $response->assertSessionHas('success');

        $this->assertSame($newShipper->id, (int) $customer->fresh()->default_shipper_id);
        $this->assertSame($newShipper->id, (int) $pendingOrder->fresh()->shipper_id);
        $this->assertSame($oldShipper->id, (int) $deliveringOrder->fresh()->shipper_id);
    }
}
