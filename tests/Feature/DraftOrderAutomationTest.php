<?php

namespace Tests\Feature;

use App\Http\Controllers\OrderController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderSchedule;
use App\Models\ProductPriceRule;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\TextOrderDraft;
use App\Models\User;
use App\Services\DraftOrderAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class DraftOrderAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_can_select_only_one_automation_mode_for_a_template(): void
    {
        [$sale, $draft] = $this->saleAndDraft();
        $dates = [now()->addDay()->toDateString(), now()->addDays(3)->toDateString()];

        $this->actingAs($sale)->putJson(route('pages.my_order_drafts.automation', $draft), [
            'automation_mode' => TextOrderDraft::AUTOMATION_SCHEDULED,
            'automation_enabled' => false,
            'automation_dates' => $dates,
        ])->assertOk();

        $draft->refresh();
        $this->assertSame(TextOrderDraft::AUTOMATION_SCHEDULED, $draft->automation_mode);
        $this->assertSame($dates, $draft->automation_dates);

        $this->actingAs($sale)->putJson(route('pages.my_order_drafts.automation', $draft), [
            'automation_mode' => TextOrderDraft::AUTOMATION_DAILY,
            'automation_enabled' => false,
            'automation_dates' => $dates,
        ])->assertOk();

        $draft->refresh();
        $this->assertSame(TextOrderDraft::AUTOMATION_DAILY, $draft->automation_mode);
        $this->assertNull($draft->automation_dates);
        $this->assertFalse($draft->automation_enabled);
    }

    public function test_automation_uses_current_price_and_does_not_duplicate_a_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-22 08:00:00', 'Asia/Bangkok'));
        [$sale, $draft] = $this->saleAndDraft();
        $customer = Customer::query()->create([
            'user_id' => $sale->id,
            'assigned_to' => $sale->id,
            'name' => 'Khách lịch mẫu',
            'status' => 'active',
        ]);
        $variant = ProductVariant::factory()->create(['kg' => 2]);
        ProductPriceRule::query()->create([
            'product_variant_id' => $variant->id,
            'price' => 125000,
            'start_date' => '2026-07-01',
            'created_by' => $sale->id,
        ]);
        $draft->update([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'parsed_items' => [[
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'size_kg' => 2,
                'unit_price' => 90000,
            ]],
            'automation_mode' => TextOrderDraft::AUTOMATION_DAILY,
            'automation_enabled' => true,
        ]);

        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => 'AUTO-TEST-1',
            'status' => 'pending',
        ]);
        $controller = Mockery::mock(OrderController::class);
        $controller->shouldReceive('createOrderFromSchedule')
            ->once()
            ->withArgs(function (array $items, array $orderData) {
                $this->assertSame(125000.0, $items[0]['base_price']);
                $this->assertSame(2, $items[0]['quantity']);
                $this->assertSame('2026-07-22', $orderData['delivery_date']);

                return true;
            })
            ->andReturn($order);
        $this->app->instance(OrderController::class, $controller);

        $service = $this->app->make(DraftOrderAutomationService::class);
        $first = $service->generate($draft, '2026-07-22');
        $second = $service->generate($draft, '2026-07-22');

        $this->assertSame($first->id, $second->id);
        $this->assertSame($order->id, $first->generated_order_id);
        $this->assertSame(1, OrderSchedule::query()->where('text_order_draft_id', $draft->id)->count());
    }

    private function saleAndDraft(): array
    {
        $role = Role::query()->firstOrCreate(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($role);
        $draft = TextOrderDraft::query()->create([
            'created_by' => $sale->id,
            'draft_scope' => TextOrderDraft::SCOPE_SALE_PRIVATE,
            'sale_id' => $sale->id,
            'raw_text' => '',
            'status' => 'draft',
        ]);

        return [$sale, $draft];
    }
}
