<?php

namespace Tests\Unit;

use App\Console\Commands\AutoCancelOverdueOrders;
use App\Models\Order;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class AutoCancellationDeadlineTest extends TestCase
{
    #[DataProvider('deadlines')]
    public function test_deadline_uses_next_business_day_or_later_delivery_date(
        string $createdAt, ?string $deliveryDate, string $deliveryTime, string $now, bool $expired
    ): void {
        $order = new Order;
        $order->forceFill([
            'created_at' => Carbon::parse($createdAt, 'Asia/Bangkok')->utc(),
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
        ]);
        $method = new ReflectionMethod(AutoCancelOverdueOrders::class, 'isPastCancellationDeadline');
        $this->assertSame($expired, $method->invoke(
            new AutoCancelOverdueOrders, $order, Carbon::parse($now, 'Asia/Bangkok')
        ));
    }

    public static function deadlines(): array
    {
        return [
            'Ken on creation day' => ['2026-09-07 16:45', '2026-09-07', '4:00', '2026-09-07 23:59', false],
            'Ken at deadline' => ['2026-09-07 16:45', '2026-09-07', '4:00', '2026-09-08 10:00', false],
            'Ken overdue' => ['2026-09-07 16:45', '2026-09-07', '4:00', '2026-09-08 10:01', true],
            'Truong Hung window' => ['2026-09-07 09:48', '2026-09-07', '5:30 - 6:00', '2026-09-08 11:59', false],
            'Truong Hung overdue' => ['2026-09-07 09:48', '2026-09-07', '5:30 - 6:00', '2026-09-08 12:01', true],
            'later delivery respected' => ['2026-09-07 09:48', '2026-09-10', '6:00', '2026-09-08 12:01', false],
            'local date after midnight' => ['2026-09-07 00:30', null, '4:00', '2026-09-07 12:01', false],
            'free form time safe fallback' => ['2026-09-07 09:48', '2026-09-07', 'GIAO TRỄ', '2026-09-09 05:59', false],
            'free form overdue' => ['2026-09-07 09:48', '2026-09-07', 'GIAO TRỄ', '2026-09-09 06:01', true],
        ];
    }
}
