<?php

namespace Tests\Unit;

use App\Http\Controllers\MyDashboardController;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class ManagerDashboardSalesBreakdownTest extends TestCase
{
    public function test_rankings_and_totals_are_reconciled_from_the_same_journal_rows(): void
    {
        $rows = collect([
            $this->row(1, 10, 'Duệ', 100, 'Khách A', 'Vịt nguyên con', 20, 50, 2_000_000),
            $this->row(2, 20, 'Sơn', 200, 'Khách B', 'Vịt nguyên con', 30, 75, 5_250_000),
            $this->row(3, 10, 'Duệ', 100, 'Khách A', 'Vịt bọng', 15, 36, 1_500_000),
            $this->row(2, 20, 'Sơn', 200, 'Khách B', 'Phí Ship', 1, 0, 30_000, 'fee'),
            $this->row(3, 10, 'Duệ', 100, 'Khách A', 'Chiết khấu đơn', 1, 0, -20_000, 'fee'),
        ]);

        $reflection = new ReflectionClass(MyDashboardController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(MyDashboardController::class, 'managerSalesBreakdown');
        $method->setAccessible(true);

        /** @var array{goods_revenue: float, fee_revenue: float, revenue: float, quantity: float, customer_count: int, sales: Collection, products: Collection, customers: Collection} $result */
        $result = $method->invoke($controller, $rows);

        $this->assertSame(8_750_000.0, $result['goods_revenue']);
        $this->assertSame(10_000.0, $result['fee_revenue']);
        $this->assertSame(8_760_000.0, $result['revenue']);
        $this->assertSame(65.0, $result['quantity']);
        $this->assertSame(2, $result['customer_count']);
        $this->assertSame(['Sơn', 'Duệ'], $result['sales']->pluck('sale_name')->all());
        $this->assertSame(5_280_000.0, $result['sales']->first()['revenue']);
        $this->assertSame(30.0, $result['sales']->first()['quantity']);
        $this->assertSame('Vịt nguyên con', $result['products']->first()['name']);
        $this->assertSame(50.0, $result['products']->first()['quantity']);
        $this->assertSame('Khách B', $result['customers']->first()['name']);
        $this->assertSame(5_280_000.0, $result['customers']->first()['revenue']);
    }

    private function row(
        int $orderId,
        int $saleId,
        string $saleName,
        int $customerId,
        string $customerName,
        string $productName,
        float $quantity,
        float $weight,
        float $amount,
        string $entryType = 'product'
    ): object {
        return (object) [
            'order_id' => $orderId,
            'sale_id' => $saleId,
            'sale_name' => $saleName,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'product_name' => $productName,
            'quantity' => $quantity,
            'total_quantity' => $weight,
            'total_amount' => $amount,
            'entry_type' => $entryType,
        ];
    }
}
