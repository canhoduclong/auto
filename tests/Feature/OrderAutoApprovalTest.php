<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderAutoApprovalRule;
use App\Models\OrderItem;
use App\Models\ProductPriceRule;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAutoApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_save_separate_new_and_adjustment_rules(): void
    {
        $directorRole = Role::query()->create(['name' => 'director']);
        $director = User::factory()->create();
        $director->roles()->attach($directorRole);

        $response = $this->actingAs($director)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->put(route('pages.my_orders.monitoring.auto_approval'), [
                'new_order_enabled' => 1,
                'new_order_require_min_price' => 1,
                'new_order_allow_bulk_below_min' => 1,
                'new_order_bulk_min_quantity' => 100,
                'new_order_bulk_below_min_amount' => 3000,
                'order_adjustment_enabled' => 1,
                'order_adjustment_require_min_price' => 1,
                'order_adjustment_allow_bulk_below_min' => 1,
                'order_adjustment_bulk_min_quantity' => 120,
                'order_adjustment_bulk_below_min_amount' => 5000,
            ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'scan_completed' => true,
        ]);
        $this->assertDatabaseHas('order_auto_approval_rules', [
            'user_id' => $director->id,
            'order_type' => OrderAutoApprovalRule::TYPE_NEW_ORDER,
            'enabled' => 1,
            'bulk_min_quantity' => 100,
            'bulk_below_min_amount' => 3000,
        ]);
        $this->assertDatabaseHas('order_auto_approval_rules', [
            'user_id' => $director->id,
            'order_type' => OrderAutoApprovalRule::TYPE_ORDER_ADJUSTMENT,
            'enabled' => 1,
            'bulk_min_quantity' => 120,
            'bulk_below_min_amount' => 5000,
        ]);
    }

    public function test_leader_rule_automatically_approves_bulk_order_with_configured_below_min_amount(): void
    {
        [$sale, $leader] = $this->salesUsers();
        $variant = ProductVariant::factory()->create();
        ProductPriceRule::query()->create([
            'product_variant_id' => $variant->id,
            'price' => 85000,
            'min_price' => 80000,
        ]);

        OrderAutoApprovalRule::query()->create([
            'user_id' => $leader->id,
            'order_type' => OrderAutoApprovalRule::TYPE_NEW_ORDER,
            'enabled' => true,
            'require_min_price' => true,
            'allow_bulk_below_min' => true,
            'bulk_min_quantity' => 100,
            'bulk_below_min_amount' => 2000,
        ]);

        $order = $this->orderWithItem($sale, $variant, 100, 78000);
        app(ApprovalService::class)->initOrderApproval($order);

        $order->refresh();
        $this->assertSame(Order::STATUS_APPROVED, $order->status);
        $this->assertDatabaseHas('approval_orders', [
            'order_id' => $order->id,
            'status' => 'approved',
            'approved_by' => $leader->id,
        ]);
    }

    public function test_order_below_bulk_quantity_remains_pending_when_price_is_below_min(): void
    {
        [$sale, $leader] = $this->salesUsers();
        $variant = ProductVariant::factory()->create();
        ProductPriceRule::query()->create([
            'product_variant_id' => $variant->id,
            'price' => 85000,
            'min_price' => 80000,
        ]);

        OrderAutoApprovalRule::query()->create([
            'user_id' => $leader->id,
            'order_type' => OrderAutoApprovalRule::TYPE_NEW_ORDER,
            'enabled' => true,
            'require_min_price' => true,
            'allow_bulk_below_min' => true,
            'bulk_min_quantity' => 100,
            'bulk_below_min_amount' => 2000,
        ]);

        $order = $this->orderWithItem($sale, $variant, 99, 78000);
        app(ApprovalService::class)->initOrderApproval($order);

        $order->refresh();
        $this->assertSame('pending_leader_approval', $order->status);
        $this->assertDatabaseHas('approval_orders', [
            'order_id' => $order->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);
    }

    public function test_monitoring_refresh_approves_pending_order_that_matches_saved_rule(): void
    {
        [$sale, $leader] = $this->salesUsers();
        $variant = ProductVariant::factory()->create();
        ProductPriceRule::query()->create([
            'product_variant_id' => $variant->id,
            'price' => 85000,
            'min_price' => 80000,
        ]);

        $order = $this->orderWithItem($sale, $variant, 100, 78000);
        app(ApprovalService::class)->initOrderApproval($order);
        $this->assertSame('pending_leader_approval', $order->fresh()->status);

        OrderAutoApprovalRule::query()->create([
            'user_id' => $leader->id,
            'order_type' => OrderAutoApprovalRule::TYPE_NEW_ORDER,
            'enabled' => true,
            'require_min_price' => true,
            'allow_bulk_below_min' => true,
            'bulk_min_quantity' => 100,
            'bulk_below_min_amount' => 2000,
        ]);

        $response = $this->actingAs($leader)->post(
            route('pages.my_orders.monitoring.refresh_sequence'),
            ['date' => $order->created_at->toDateString()]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', fn (string $message) => str_contains($message, 'Đã tự động duyệt 1 đơn phù hợp.'));
        $this->assertSame(Order::STATUS_APPROVED, $order->fresh()->status);
    }

    public function test_later_matching_rule_is_not_hidden_by_an_earlier_stricter_rule(): void
    {
        [$sale, $leader] = $this->salesUsers();
        $secondLeader = User::factory()->create(['team_id' => $sale->team_id]);
        $secondLeader->roles()->attach($leader->roles->first());
        $variant = ProductVariant::factory()->create();
        ProductPriceRule::create(['product_variant_id' => $variant->id, 'price' => 85000, 'min_price' => 80000]);
        foreach ([$leader, $secondLeader] as $index => $approver) {
            OrderAutoApprovalRule::create([
                'user_id' => $approver->id, 'order_type' => 'new_order', 'enabled' => true,
                'require_min_price' => true, 'allow_bulk_below_min' => $index === 1,
                'bulk_min_quantity' => 100, 'bulk_below_min_amount' => 2000,
            ]);
        }
        $order = $this->orderWithItem($sale, $variant, 100, 78000);
        app(ApprovalService::class)->initOrderApproval($order);
        $this->assertSame('approved', $order->fresh()->status);
        $this->assertDatabaseHas('approval_orders', ['order_id' => $order->id, 'approved_by' => $secondLeader->id]);
    }

    public function test_cancelled_order_is_not_auto_approved_and_new_orders_for_same_customer_are_independent(): void
    {
        [$sale, $leader] = $this->salesUsers();
        $variant = ProductVariant::factory()->create(['kg' => 1, 'is_priced_by_kg' => false]);
        ProductPriceRule::create(['product_variant_id' => $variant->id, 'price' => 85000, 'min_price' => 80000]);
        $cancelled = $this->orderWithItem($sale, $variant, 1, 85000);
        app(ApprovalService::class)->initOrderApproval($cancelled);
        $this->actingAs($sale)->post(route('site.orders.cancel', $cancelled))->assertSessionHas('success');
        OrderAutoApprovalRule::create([
            'user_id' => $leader->id, 'order_type' => 'new_order', 'enabled' => true, 'require_min_price' => true,
        ]);
        $this->assertSame(0, app(\App\Services\OrderAutoApprovalService::class)->processOrder($cancelled));
        $this->assertSame('cancelled', $cancelled->fresh()->status);
        app(\App\Services\CustomerPriorityService::class)->attachSale($cancelled->customer, $sale->id, 1, 'test');
        $payload = [
            'creation_token' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $cancelled->customer_id,
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
        ];
        $route = route('pages.my_orders.monitoring.store');
        $first = $this->postJson($route, $payload)->assertCreated()->json('order.id');
        $this->assertSame('approved', Order::findOrFail($first)->status);
        $this->postJson($route, $payload)->assertCreated()->assertJsonPath('order.id', $first);
        $changed = $payload;
        $changed['items'][0]['quantity'] = 2;
        $this->postJson($route, $changed)->assertStatus(422);
        $this->post(route('site.orders.cancel', $first))->assertSessionHas('success');
        $payload['creation_token'] = (string) \Illuminate\Support\Str::uuid();
        $second = $this->postJson($route, $payload)->assertCreated()->json('order.id');
        $payload['creation_token'] = (string) \Illuminate\Support\Str::uuid();
        $third = $this->postJson($route, $payload)->assertCreated()->json('order.id');
        $this->assertNotSame($first, $second);
        $this->assertNotSame($second, $third);
        $this->assertSame('approved', Order::findOrFail($second)->status);
        $this->assertSame('approved', Order::findOrFail($third)->status);
        $this->assertSame(4, Order::where('customer_id', $cancelled->customer_id)->count());
    }

    private function salesUsers(): array
    {
        $team = Team::query()->create(['name' => 'Phòng kinh doanh kiểm thử']);
        $saleRole = Role::query()->create(['name' => 'sale']);
        $leaderRole = Role::query()->create(['name' => 'leader']);
        $sale = User::factory()->create(['team_id' => $team->id]);
        $leader = User::factory()->create(['team_id' => $team->id]);
        $sale->roles()->attach($saleRole);
        $leader->roles()->attach($leaderRole);

        $workflow = ApprovalWorkflow::query()->create([
            'code' => 'auto-approval-test',
            'name' => 'Quy trình duyệt tự động kiểm thử',
            'is_active' => true,
            'applies_to' => [ApprovalWorkflow::ACTIVITY_ORDER_CREATE],
        ]);
        ApprovalStep::query()->create([
            'approval_flow_id' => $workflow->id,
            'step_order' => 1,
            'role_slug' => 'leader',
        ]);

        return [$sale, $leader];
    }

    private function orderWithItem(User $sale, ProductVariant $variant, int $quantity, float $price): Order
    {
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'name' => 'Khách kiểm thử duyệt tự động',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'AUTO-' . fake()->unique()->numerify('#####'),
            'total' => $quantity * $price,
            'status' => 'pending',
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'price' => $price,
            'total' => $quantity * $price,
        ]);

        return $order;
    }
}
