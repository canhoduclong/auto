<?php
use App\Models\{OrderAdjustment, OrderAdjustmentItem, OrderItem, ProductVariant, Product};
use App\Support\OrderAdjustmentComparison;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class OrderAdjustmentComparisonTest extends TestCase
{
    private function row(array $values = [], bool $byKg = true): array
    {
        $product = new Product(['name'=>'Vịt','unit'=>$byKg ? 'kg' : 'cai']);
        $variant = new ProductVariant(['name'=>'MOC 2.3','size'=>'2.3','is_priced_by_kg'=>$byKg]);
        $variant->setRelation('product',$product);
        $orderItem = new OrderItem(['is_priced_by_kg'=>$byKg,'packed_quantity'=>15,'packed_weight'=>36]);
        $orderItem->setRelation('variant',$variant)->setRelation('product',$product);
        $item = new OrderAdjustmentItem(array_merge(['order_item_id'=>1,'original_quantity'=>15,'adjusted_quantity'=>15,'original_price'=>63000,'adjusted_price'=>63000,'original_weight'=>36,'adjusted_weight'=>35.85],$values));
        $item->setRelation('variant',$variant)->setRelation('orderItem',$orderItem);
        $adjustment = new OrderAdjustment;
        $adjustment->setRelation('items',new Collection([$item]));
        return OrderAdjustmentComparison::rows($adjustment)[0];
    }
    public function test_weight_only_change_shows_old_and_new_actual_size(): void
    {
        $row=$this->row();
        self::assertTrue($row['changed']);
        self::assertEquals(2.4,$row['originalSize']);
        self::assertEqualsWithDelta(2.39,$row['adjustedSize'],0.00001);
        self::assertEquals('2.3',$row['size']);
    }
    public function test_missing_packed_measurement_does_not_claim_estimated_size_as_actual(): void
    {
        $adjustment = new OrderAdjustment;
        $item = new OrderAdjustmentItem(['order_item_id'=>1,'original_quantity'=>15,'adjusted_quantity'=>15,'original_weight'=>36,'adjusted_weight'=>36]);
        $orderItem = new OrderItem(['is_priced_by_kg'=>true]);
        $orderItem->setRelation('product',null)->setRelation('variant',null);
        $item->setRelation('orderItem',$orderItem)->setRelation('variant',null);
        $adjustment->setRelation('items',new Collection([$item]));
        self::assertNull(OrderAdjustmentComparison::rows($adjustment)[0]['originalSize']);
    }
    public function test_unchanged_row_is_not_in_changes(): void
    {
        self::assertFalse($this->row(['adjusted_weight'=>36])['changed']);
    }
    public function test_zero_quantity_has_zero_weight_and_no_division_by_zero(): void
    {
        $row=$this->row(['adjusted_quantity'=>0,'adjusted_weight'=>36]);
        self::assertEquals(0,$row['adjustedWeight']);
        self::assertNull($row['adjustedSize']);
    }
    public function test_discrete_item_ignores_legacy_weight_values(): void
    {
        $row=$this->row(['original_quantity'=>1,'adjusted_quantity'=>1,'original_weight'=>1,'adjusted_weight'=>0],false);
        self::assertFalse($row['changed']);
        self::assertFalse($row['byKg']);
        self::assertNull($row['originalSize']);
    }
    public function test_added_item_and_null_adjusted_weight_are_handled(): void
    {
        self::assertTrue($this->row(['order_item_id'=>null,'original_quantity'=>0,'original_weight'=>0])['new']);
        self::assertEquals(36,$this->row(['adjusted_weight'=>null])['adjustedWeight']);
    }
    public function test_complete_preview_keeps_unchanged_rows_and_calculates_each_state_with_shipping_once(): void
    {
        $order = new \App\Models\Order(['charge_shipping_fee'=>false,'shipping_fee'=>0]);
        $order->setRelation('additionalFees',new Collection);
        $product = new Product(['name'=>'Thùng xốp','unit'=>'cai']);
        $variant = new ProductVariant(['name'=>'Thùng xốp','size'=>'1']);
        $variant->setRelation('product',$product);
        $unchanged = new OrderItem(['quantity'=>1,'price'=>75000,'is_priced_by_kg'=>false]);
        $unchanged->id=2;
        $unchanged->setRelation('variant',$variant)->setRelation('product',$product);
        $order->setRelation('items',new Collection([$unchanged]));
        $changed = new OrderAdjustmentItem(['order_item_id'=>1,'original_quantity'=>30,'adjusted_quantity'=>25,'original_price'=>63000,'adjusted_price'=>63000,'original_weight'=>76.8,'adjusted_weight'=>76.8]);
        $changed->setRelation('variant',null)->setRelation('orderItem',null);
        $adjustment = new OrderAdjustment(['fee_changes'=>['shipping'=>['name'=>'Phí Ship','original'=>['enabled'=>false,'value'=>0],'adjusted'=>['enabled'=>true,'value'=>120000]]]]);
        $adjustment->setRelation('order',$order)->setRelation('items',new Collection([$changed]));
        $rows = OrderAdjustmentComparison::completeRows($adjustment);
        self::assertCount(2,$rows);
        self::assertFalse($rows[1]['changed']);
        self::assertEquals(75000,$rows[1]['adjustedTotal']);
        $old = OrderAdjustmentComparison::totals($adjustment,$rows,'original');
        $new = OrderAdjustmentComparison::totals($adjustment,$rows,'adjusted');
        self::assertEquals(4913400,$old['total']);
        self::assertEquals(5033400,$new['total']);
        self::assertCount(1,$new['fees']);
        self::assertEquals(4913400,$new['subtotal']);
    }

    public function test_existing_customer_shipping_is_replaced_instead_of_added_twice(): void
    {
        $order = new \App\Models\Order(['collect_customer_shipping_fee'=>true,'customer_shipping_fee'=>120000,'charge_shipping_fee'=>false,'shipping_fee'=>0]);
        $order->setRelation('additionalFees',new Collection);
        $adjustment = new OrderAdjustment(['fee_changes'=>['shipping'=>['original'=>['enabled'=>true,'value'=>0],'adjusted'=>['enabled'=>true,'value'=>120000]]]]);
        $adjustment->setRelation('order',$order);
        $rows = [['new'=>false,'originalTotal'=>4913400,'adjustedTotal'=>4913400]];
        foreach (['original','adjusted'] as $state) {
            $total = OrderAdjustmentComparison::totals($adjustment,$rows,$state);
            self::assertEquals(5033400,$total['total']);
            self::assertCount(1,$total['fees']);
            self::assertSame('Phí giao hàng thu khách',$total['fees'][0]['name']);
        }
    }
    public function test_shipping_application_preserves_shipper_cost_and_updates_only_customer_charge(): void
    {
        $order = new class extends \App\Models\Order {
            public function save(array $options = []) { return true; }
        };
        $order->forceFill(['collect_customer_shipping_fee'=>true,'customer_shipping_fee'=>120000,'charge_shipping_fee'=>false,'shipping_fee'=>80000]);
        (new \App\Services\OrderFeeService)->applySystemChanges($order,['shipping'=>['original'=>['enabled'=>false,'value'=>0],'adjusted'=>['enabled'=>true,'value'=>150000]]]);
        self::assertEquals(150000,$order->customer_shipping_fee);
        self::assertEquals(80000,$order->shipping_fee);
        self::assertFalse((bool)$order->charge_shipping_fee);
        self::assertTrue((bool)$order->collect_customer_shipping_fee);
    }

}
