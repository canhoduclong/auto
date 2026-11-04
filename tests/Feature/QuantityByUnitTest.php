<?php
namespace Tests\Feature;

use App\Support\QuantityByUnit;
use PHPUnit\Framework\TestCase;

class QuantityByUnitTest extends TestCase
{
    public function test_different_units_are_counted_separately_and_unit_spelling_is_normalized(): void
    {
        $rows = [];
        foreach ([80,30,25,30,20,16,40,25,5] as $quantity) $rows[] = ['unit'=>'con','quantity'=>$quantity];
        $rows[] = ['unit'=>'bo','quantity'=>40];
        $rows[] = ['unit'=>'cai','quantity'=>1];
        self::assertSame(['con'=>271.0,'bộ'=>40.0,'cái'=>1.0], QuantityByUnit::totals($rows));
        self::assertSame(['bộ'=>5.0], QuantityByUnit::totals([
            ['unit'=>' BỘ ', 'quantity'=>2], ['unit'=>'bo', 'quantity'=>3],
        ]));
    }

    public function test_received_quantities_and_unknown_units_do_not_merge_into_other_units(): void
    {
        self::assertSame(['con'=>2.5,'cái'=>0.0,'Chưa rõ ĐVT'=>3.0], QuantityByUnit::totals([
            ['unit'=>'con','received_quantity'=>2.5], ['unit'=>'bo','received_quantity'=>null],
            ['unit'=>'cai','received_quantity'=>0], ['quantity'=>10,'received_quantity'=>3],
        ], 'received_quantity'));
        self::assertSame([], QuantityByUnit::totals([]));
    }
    public function test_subtotals_exclude_piece_and_set_weights_but_preserve_money(): void
    {
        $groups = QuantityByUnit::groups([
            ['unit'=>'con', 'quantity'=>80, 'weight'=>204, 'amount'=>11220000, 'received_quantity'=>80, 'received_weight'=>204],
            ['unit'=>'CON', 'quantity'=>30, 'weight'=>82.5, 'amount'=>5115000],
            ['unit'=>'bo', 'quantity'=>40, 'weight'=>40, 'amount'=>400000, 'received_quantity'=>40, 'received_weight'=>40],
            ['unit'=>'cái', 'quantity'=>1, 'weight'=>1, 'amount'=>75000],
        ]);
        self::assertSame(110.0, $groups[0]['quantity']);
        self::assertSame(286.5, $groups[0]['weight']);
        self::assertSame(16335000.0, $groups[0]['amount']);
        self::assertSame(204.0, $groups[0]['received_weight']);
        self::assertNull($groups[1]['weight']);
        self::assertNull($groups[1]['received_weight']);
        self::assertSame(400000.0, $groups[1]['amount']);
        self::assertNull($groups[2]['weight']);
        self::assertSame(16810000.0, array_sum(array_column($groups, 'amount')));
        self::assertSame(286.5, array_sum(array_column($groups, 'weight')));
        self::assertFalse(QuantityByUnit::hasWeight(' BỘ '));
        self::assertFalse(QuantityByUnit::hasWeight('CAI'));
        self::assertTrue(QuantityByUnit::hasWeight('con'));
    }
}
