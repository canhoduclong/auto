<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderSchedule;
use PHPUnit\Framework\TestCase;

class DraftOrderRecreationTest extends TestCase
{
    public function test_cancelled_missing_or_trashed_order_does_not_block_recreation(): void
    {
        $trashed = new class extends Order
        {
            public function getDateFormat()
            {
                return 'Y-m-d H:i:s';
            }
        };
        $trashed->forceFill(['status' => 'pending', 'trash_at' => '2026-09-30 09:00:00']);
        foreach ([null, new Order(['status' => 'cancelled']), $trashed] as $order) {
            $schedule = new OrderSchedule(['generated_order_id' => 123]);
            $schedule->setRelation('generatedOrder', $order);
            self::assertFalse($schedule->hasActiveGeneratedOrder());
        }
    }

    public function test_existing_active_and_completed_orders_still_block_duplicates(): void
    {
        foreach (['pending', 'approved', 'packed', 'delivered', 'completed'] as $status) {
            $schedule = new OrderSchedule(['generated_order_id' => 123]);
            $schedule->setRelation('generatedOrder', new Order(['status' => $status]));
            self::assertTrue($schedule->hasActiveGeneratedOrder());
        }
    }
}
