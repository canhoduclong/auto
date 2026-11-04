<?php
namespace Tests\Feature;

use App\Support\DailySalesUnitSummary;
use PHPUnit\Framework\TestCase;

class DailySalesUnitSummaryTest extends TestCase
{
    public function test_page_totals_are_grouped_by_unit_and_money_and_shipping_are_preserved(): void
    {
        $rows = [
            (object)['product_unit'=>'con','eff_qty'=>20,'eff_weight'=>54,'eff_total'=>3132000,'price_adjustment'=>-324000],
            (object)['product_unit'=>'con','eff_qty'=>15,'eff_weight'=>34.5,'eff_total'=>2070000,'customer_shipping_fee'=>50000,'price_adjustment'=>-138000],
            (object)['product_unit'=>'kg','eff_qty'=>120,'eff_weight'=>300,'eff_total'=>16500000],
            (object)['product_unit'=>'cai','eff_qty'=>2,'eff_weight'=>2,'eff_total'=>150000,'actual_weight'=>2,'actual_total'=>150000],
            (object)['product_unit'=>'bo','eff_qty'=>40,'eff_weight'=>40,'eff_total'=>400000],
        ];
        $groups = array_column(DailySalesUnitSummary::groups($rows), null, 'unit');
        self::assertSame(35.0, $groups['con']['eff_qty']);
        self::assertSame(88.5, $groups['con']['eff_weight']);
        self::assertSame(5202000.0, $groups['con']['eff_total']);
        self::assertSame(50000.0, $groups['con']['customer_shipping_fee']);
        self::assertSame(-462000.0, $groups['con']['price_adjustment']);
        self::assertSame(0.0, $groups['cái']['eff_weight']);
        self::assertSame(0.0, $groups['cái']['actual_weight']);
        self::assertSame(0.0, $groups['bộ']['eff_weight']);
        self::assertSame(22252000.0, array_sum(array_column($groups, 'eff_total')));
        self::assertSame(150000.0, $groups['cái']['actual_total']);
        self::assertSame([], DailySalesUnitSummary::groups([]));
    }
    public function test_product_groups_merge_sizes_but_keep_different_products_and_units_separate(): void
    {
        $rows = [
            (object)['product_id'=>1,'product_name'=>'Vịt Nguyên Con','variant_size'=>'2.7','product_unit'=>'con','eff_qty'=>20,'eff_weight'=>54,'eff_total'=>3132000,'customer_shipping_fee'=>50000],
            (object)['product_id'=>1,'product_name'=>'Vịt Nguyên Con','variant_size'=>'2.8','product_unit'=>'con','eff_qty'=>40,'eff_weight'=>112,'eff_total'=>6720000],
            (object)['product_id'=>2,'product_name'=>'Vịt Bọng không đầu chân','product_unit'=>'kg','eff_qty'=>120,'eff_weight'=>300,'eff_total'=>16500000],
            (object)['product_id'=>3,'product_name'=>'Ức vịt','product_unit'=>'kg','eff_qty'=>102,'eff_weight'=>102,'eff_total'=>6120000],
            (object)['product_id'=>4,'product_name'=>'Thùng Xốp','product_unit'=>'cai','eff_qty'=>2,'eff_weight'=>2,'eff_total'=>150000],
            (object)['product_id'=>1,'product_name'=>'Vịt Nguyên Con','product_unit'=>'kg','eff_qty'=>3,'eff_weight'=>3,'eff_total'=>100000],
        ];
        $groups = DailySalesUnitSummary::productGroups($rows);
        self::assertCount(5, $groups);
        self::assertSame('Vịt Nguyên Con', $groups[0]['product_name']);
        self::assertSame(60.0, $groups[0]['eff_qty']);
        self::assertSame(166.0, $groups[0]['eff_weight']);
        self::assertSame(9852000.0, $groups[0]['eff_total']);
        self::assertSame(0.0, $groups[3]['eff_weight']);
        self::assertSame('kg', $groups[4]['unit']);
        self::assertSame(32722000.0, array_sum(array_column($groups, 'eff_total')));
        self::assertSame(50000.0, array_sum(array_column($groups, 'customer_shipping_fee')));
        self::assertSame([], DailySalesUnitSummary::productGroups([]));
    }
}
