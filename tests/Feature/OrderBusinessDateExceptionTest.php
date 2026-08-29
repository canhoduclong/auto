<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderBusinessDateExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_order_created_for_a_non_current_business_date_is_an_exception(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00');
        $customer = Customer::query()->create([
            'name' => 'Khách kiểm thử ngày ngoại lệ',
            'status' => 'active',
        ]);

        foreach (['2026-08-25 09:00:00', '2026-08-31 09:00:00'] as $businessCreatedAt) {
            $order = new Order();
            $order->forceFill([
                'customer_id' => $customer->id,
                'status' => 'pending',
                'total' => 0,
                'created_at' => $businessCreatedAt,
                'skip_auto_cancel' => false,
            ]);
            $order->save();

            $this->assertTrue($order->fresh()->skip_auto_cancel);
        }

        $todayOrder = new Order();
        $todayOrder->forceFill([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 0,
            'created_at' => '2026-08-29 09:00:00',
            'skip_auto_cancel' => false,
        ]);
        $todayOrder->save();

        $this->assertFalse($todayOrder->fresh()->skip_auto_cancel);
    }

    public function test_changing_a_newly_created_order_to_an_old_business_date_marks_it_as_an_exception(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00');
        $customer = Customer::query()->create([
            'name' => 'Khách đổi ngày nghiệp vụ',
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 0,
        ]);

        $this->assertFalse((bool) $order->skip_auto_cancel);

        $order->forceFill(['created_at' => '2026-08-20 08:00:00'])->save();

        $this->assertTrue($order->fresh()->skip_auto_cancel);
    }
}
