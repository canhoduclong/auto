<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class PackingGracePeriodTest extends TestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Ho_Chi_Minh');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    private function order(): Order
    {
        $order = new class extends Order {
            public function getDateFormat() { return 'Y-m-d H:i:s'; }
        };
        $order->forceFill(['created_at' => Carbon::parse('2026-09-30 14:16:00', 'Asia/Bangkok'), 'status' => 'ready_to_pack']);
        $order->setRelation('adjustments', collect());
        return $order;
    }

    public function test_previous_day_order_can_start_until_next_noon(): void
    {
        foreach (['2026-09-30 14:16:00', '2026-10-01 00:01:00', '2026-10-01 08:33:00', '2026-10-01 12:00:00'] as $time) {
            Carbon::setTestNow(Carbon::parse($time, 'Asia/Bangkok'));
            self::assertTrue($this->order()->canProcessPackingOnCurrentRun(), $time);
        }
    }

    public function test_expired_order_needs_override_but_packing_can_finish(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:01', 'Asia/Bangkok'));
        $order = $this->order();
        self::assertFalse($order->canProcessPackingOnCurrentRun());
        $order->status = Order::STATUS_PACKING;
        self::assertTrue($order->canProcessPackingOnCurrentRun());
        $order->status = 'ready_to_pack';
        $order->skip_auto_cancel = true;
        self::assertTrue($order->canProcessPackingOnCurrentRun());
    }
}
