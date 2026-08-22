<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCancelOverdueOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_order_created_yesterday_is_not_cancelled_before_six_hours_after_delivery_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 15:59:00', 'Asia/Bangkok'));
        $order = $this->createOrder('2026-08-22', '8h-10h', '2026-08-21 09:00:00');

        $this->artisan('orders:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame('packing', $order->fresh()->status);
    }

    public function test_order_is_cancelled_only_after_six_hours_from_end_of_delivery_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 16:01:00', 'Asia/Bangkok'));
        $order = $this->createOrder('2026-08-22', '8h-10h', '2026-08-21 09:00:00');

        $this->artisan('orders:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_customer_delivery_time_is_used_when_order_delivery_time_is_empty(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 20:01:00', 'Asia/Bangkok'));
        $order = $this->createOrder('2026-08-22', null, '2026-08-21 09:00:00', '2h chiều');

        $this->artisan('orders:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_missing_delivery_time_uses_end_of_delivery_day_to_avoid_early_cancellation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-23 05:59:00', 'Asia/Bangkok'));
        $order = $this->createOrder('2026-08-22', null, '2026-08-21 09:00:00');

        $this->artisan('orders:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame('packing', $order->fresh()->status);
    }

    public function test_order_without_delivery_time_is_cancelled_after_end_of_day_grace_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-23 06:01:00', 'Asia/Bangkok'));
        $order = $this->createOrder('2026-08-22', null, '2026-08-21 09:00:00');

        $this->artisan('orders:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    private function createOrder(
        string $deliveryDate,
        ?string $deliveryTime,
        string $createdAt,
        ?string $customerDeliveryTime = null
    ): Order {
        $customer = Customer::query()->create([
            'name' => 'Khách kiểm thử tự động hủy',
            'status' => 'active',
            'delivery_time' => $customerDeliveryTime,
        ]);

        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'status' => 'packing',
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
        ]);

        $order->forceFill([
            'created_at' => Carbon::parse($createdAt, 'Asia/Bangkok')->utc(),
            'updated_at' => Carbon::parse($createdAt, 'Asia/Bangkok')->utc(),
        ])->saveQuietly();

        return $order;
    }
}
