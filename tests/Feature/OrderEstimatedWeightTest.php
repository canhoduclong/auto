<?php

namespace Tests\Feature;

use App\Models\OrderItem;
use App\Support\OrderEstimatedLine;
use PHPUnit\Framework\TestCase;

class OrderEstimatedWeightTest extends TestCase
{
    public function test_163_items_at_size_2_5_can_be_priced_using_406_kg(): void
    {
        $amounts = OrderEstimatedLine::amounts(163, 406, true, 65000, 2000, 'decrease');
        self::assertSame(63000.0, $amounts['price']);
        self::assertSame(25578000.0, $amounts['total']);
        self::assertSame(26390000.0, $amounts['subtotal']);
        self::assertSame(812000.0, $amounts['discount_total']);
        $item = new OrderItem(['quantity' => 163, 'unit_weight' => 2.5, 'total_weight' => 406, 'is_priced_by_kg' => true]);
        self::assertSame(406.0, $item->display_total_value);
        self::assertSame(2.5, $item->effective_unit_weight);
    }

    public function test_piece_pricing_keeps_quantity_as_billing_basis(): void
    {
        $amounts = OrderEstimatedLine::amounts(163, 406, false, 63000, 1000, 'increase');
        self::assertSame(10432000.0, $amounts['total']);
        self::assertSame(-163000.0, $amounts['discount_total']);
        $item = new OrderItem(['quantity' => 163, 'unit_weight' => 2.5, 'total_weight' => 406, 'is_priced_by_kg' => false]);
        self::assertSame(163.0, $item->display_total_value);
    }

    public function test_three_decimal_weight_is_not_rounded_via_unit_weight(): void
    {
        $amounts = OrderEstimatedLine::amounts(163, 406.123, true, 63000, 0, 'decrease');
        self::assertSame(25585749.0, $amounts['total']);
    }

    public function test_older_items_without_total_weight_still_use_size_times_quantity(): void
    {
        $item = new OrderItem(['quantity' => 163, 'unit_weight' => 2.5, 'is_priced_by_kg' => true]);
        self::assertSame(407.5, $item->display_total_value);
    }

    public function test_zero_legacy_weight_falls_back_and_measured_weights_keep_priority(): void
    {
        $item = new OrderItem(['quantity' => 163, 'unit_weight' => 2.5, 'total_weight' => 0, 'is_priced_by_kg' => true]);
        self::assertSame(407.5, $item->display_total_value);
        $item->total_weight = 406;
        $item->packed_weight = 405;
        $item->actual_weight = 404;
        self::assertSame(406.0, $item->displayValueForStage('pending'));
        self::assertSame(405.0, $item->displayValueForStage('packed'));
        self::assertSame(404.0, $item->displayValueForStage('delivered'));
    }
}
