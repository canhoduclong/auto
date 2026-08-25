<?php

namespace Tests\Unit;

use App\Models\OrderAdjustment;
use App\Models\OrderAdjustmentItem;
use App\Models\OrderAutoApprovalRule;
use App\Models\ProductPriceRule;
use App\Models\ProductVariant;
use App\Services\ApprovalService;
use App\Services\OrderAutoApprovalService;
use ReflectionMethod;
use Tests\TestCase;

class OrderAdjustmentAutoApprovalGuardTest extends TestCase
{
    public function test_price_only_adjustment_can_match_the_min_price_rule(): void
    {
        $adjustment = $this->adjustment(100, 78000, 50, 50);

        $this->assertTrue($this->matchesRule($adjustment));
    }

    public function test_fee_or_discount_change_always_requires_manual_approval(): void
    {
        $shippingFee = $this->adjustment(100, 78000, 50, 50, [
            'shipping' => [
                'original' => ['enabled' => false, 'value' => 0],
                'adjusted' => ['enabled' => true, 'value' => 80000],
            ],
        ]);
        $discount = $this->adjustment(100, 78000, 50, 50, [
            'discount' => [
                'original' => ['enabled' => false, 'value' => 0],
                'adjusted' => ['enabled' => true, 'value' => 50000],
            ],
        ]);

        $this->assertFalse($this->matchesRule($shippingFee));
        $this->assertFalse($this->matchesRule($discount));
    }

    public function test_reweighed_adjustment_always_requires_manual_approval(): void
    {
        $adjustment = $this->adjustment(100, 78000, 50, 52.5);

        $this->assertFalse($this->matchesRule($adjustment));
    }

    public function test_below_min_price_without_bulk_quantity_remains_manual(): void
    {
        $adjustment = $this->adjustment(99, 78000, 50, 50);

        $this->assertFalse($this->matchesRule($adjustment));
    }

    private function matchesRule(OrderAdjustment $adjustment): bool
    {
        $service = new OrderAutoApprovalService(new ApprovalService());
        $method = new ReflectionMethod($service, 'adjustmentMatchesRule');
        $method->setAccessible(true);

        return $method->invoke($service, $adjustment, new OrderAutoApprovalRule([
            'require_min_price' => true,
            'allow_bulk_below_min' => true,
            'bulk_min_quantity' => 100,
            'bulk_below_min_amount' => 2000,
        ]));
    }

    private function adjustment(
        int $quantity,
        float $price,
        float $originalWeight,
        float $adjustedWeight,
        array $feeChanges = []
    ): OrderAdjustment {
        $priceRule = new ProductPriceRule(['min_price' => 80000]);
        $variant = new ProductVariant();
        $variant->setRelation('latestPriceRule', $priceRule);

        $item = new OrderAdjustmentItem([
            'adjusted_quantity' => $quantity,
            'adjusted_price' => $price,
            'original_weight' => $originalWeight,
            'adjusted_weight' => $adjustedWeight,
        ]);
        $item->setRelation('variant', $variant);

        $adjustment = new OrderAdjustment(['fee_changes' => $feeChanges]);
        $adjustment->setRelation('items', collect([$item]));

        return $adjustment;
    }
}
