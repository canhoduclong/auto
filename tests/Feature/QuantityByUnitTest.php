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
}
