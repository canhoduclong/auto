<?php

namespace Tests\Unit;

use App\Http\Controllers\CeoDashboardController;
use App\Models\ProductPriceRule;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CeoLossReportValuationTest extends TestCase
{
    public function test_it_uses_the_min_price_that_existed_when_loss_occurred(): void
    {
        $occurredAt = Carbon::parse('2026-07-10 10:00:00');
        $rules = collect([
            10 => collect([
                $this->rule(10, 35_000, '2026-07-01', null, '2026-07-11 08:00:00'),
                $this->rule(10, 28_000, '2026-07-01', null, '2026-07-01 08:00:00'),
            ]),
        ]);

        $price = $this->invokeControllerMethod('minPriceAt', [10, $occurredAt, $rules]);

        $this->assertSame(28_000.0, $price);
    }

    public function test_it_values_multi_product_loss_with_weighted_min_price(): void
    {
        $occurredAt = Carbon::parse('2026-07-10 10:00:00');
        $rules = collect([
            10 => collect([$this->rule(10, 20_000, '2026-07-01', null, '2026-07-01 08:00:00')]),
            20 => collect([$this->rule(20, 50_000, '2026-07-01', null, '2026-07-01 08:00:00')]),
        ]);
        $materials = collect([
            ['variant_id' => 10, 'weight' => 2.0],
            ['variant_id' => 20, 'weight' => 1.0],
        ]);

        [$averagePrice, $lossValue] = $this->invokeControllerMethod(
            'valueLossByMaterials',
            [$materials, 3.0, $occurredAt, $rules]
        );

        $this->assertSame(30_000.0, $averagePrice);
        $this->assertSame(90_000.0, $lossValue);
    }

    private function rule(
        int $variantId,
        float $minPrice,
        ?string $startDate,
        ?string $endDate,
        string $createdAt
    ): ProductPriceRule {
        $rule = new ProductPriceRule([
            'product_variant_id' => $variantId,
            'price' => $minPrice,
            'min_price' => $minPrice,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        $rule->setDateFormat('Y-m-d H:i:s');
        $rule->created_at = Carbon::parse($createdAt);

        return $rule;
    }

    private function invokeControllerMethod(string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod(CeoDashboardController::class, $method);

        return $reflection->invoke(new CeoDashboardController, ...$arguments);
    }
}
