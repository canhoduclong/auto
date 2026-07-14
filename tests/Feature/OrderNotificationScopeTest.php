<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Notifications\OrderWorkflowNotification;
use App\Services\OrderNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderNotificationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitted_order_only_notifies_its_sale_and_related_management(): void
    {
        Notification::fake();

        $saleRole = Role::create(['name' => 'sale']);
        $leaderRole = Role::create(['name' => 'leader']);
        $managerRole = Role::create(['name' => 'manager']);
        $teamA = Team::create(['name' => 'Team A', 'code' => 'TEAM-A']);
        $teamB = Team::create(['name' => 'Team B', 'code' => 'TEAM-B']);

        $sale = User::factory()->create(['team_id' => $teamA->id]);
        $sale->roles()->attach($saleRole);
        $leader = User::factory()->create(['team_id' => $teamA->id]);
        $leader->roles()->attach($leaderRole);
        $manager = User::factory()->create(['team_id' => $teamA->id]);
        $manager->roles()->attach($managerRole);
        $unrelatedManager = User::factory()->create(['team_id' => $teamB->id]);
        $unrelatedManager->roles()->attach($managerRole);
        $otherSale = User::factory()->create(['team_id' => $teamA->id]);
        $otherSale->roles()->attach($saleRole);

        $customer = Customer::create(['name' => 'Khách A', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ORD-NOTIFY-1',
            'status' => 'pending_leader_approval',
        ]);

        app(OrderNotificationService::class)->notifySubmitted($order);

        Notification::assertSentTo([$sale, $leader, $manager], OrderWorkflowNotification::class);
        Notification::assertNotSentTo([$unrelatedManager, $otherSale], OrderWorkflowNotification::class);
    }

    public function test_approved_order_notification_is_private_to_the_approving_manager(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $otherManager = User::factory()->create();
        $sale = User::factory()->create();
        $customer = Customer::create(['name' => 'Khách B', 'status' => 'active']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'ORD-NOTIFY-2',
            'status' => 'approved',
        ]);

        app(OrderNotificationService::class)->notifyApproved($order, $manager);

        Notification::assertSentTo($manager, OrderWorkflowNotification::class);
        Notification::assertNotSentTo([$otherManager, $sale], OrderWorkflowNotification::class);
    }
}
