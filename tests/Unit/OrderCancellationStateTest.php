<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderCancellationStateTest extends TestCase
{
    #[DataProvider('cancellableStatuses')]
    public function test_order_can_be_cancelled_from_supported_statuses(string $status): void
    {
        $this->assertTrue((new Order(['status' => $status]))->canBeCancelled());
    }

    public static function cancellableStatuses(): array
    {
        return array_map(
            static fn (string $status): array => [$status],
            Order::CANCELLABLE_STATUSES
        );
    }

    #[DataProvider('nonCancellableStatuses')]
    public function test_order_cannot_be_cancelled_after_it_leaves_supported_statuses(string $status): void
    {
        $this->assertFalse((new Order(['status' => $status]))->canBeCancelled());
    }

    public static function nonCancellableStatuses(): array
    {
        return [
            [Order::STATUS_READY_TO_PACK],
            [Order::STATUS_READY_TO_SHIP],
            [Order::STATUS_DELIVERING],
            [Order::STATUS_DELIVERED],
            [Order::STATUS_COMPLETED],
            [Order::STATUS_CANCELLED],
            [Order::STATUS_RETURNING],
            [Order::STATUS_RETURNED_COMPLETED],
        ];
    }
}
