<?php

namespace Tests\Unit;

use App\Services\CustomerClassificationService;
use PHPUnit\Framework\TestCase;

class CustomerClassificationServiceTest extends TestCase
{
    public function test_default_weights_total_one_hundred_percent(): void
    {
        $config = CustomerClassificationService::defaults();
        $criteria = ['volume', 'frequency', 'trend', 'payment', 'debt', 'history', 'relationship'];

        $this->assertSame(100, array_sum(array_map(
            fn (string $criterion): int => $config[$criterion]['weight'],
            $criteria
        )));
        $this->assertSame([25, 20, 15, 20, 10, 5, 5], array_map(
            fn (string $criterion): int => $config[$criterion]['weight'],
            $criteria
        ));
    }

    public function test_default_thresholds_match_customer_classification_table(): void
    {
        $config = CustomerClassificationService::defaults();

        $this->assertSame([50, 20, 5], array_values(array_intersect_key(
            $config['volume'],
            array_flip(['a_min', 'b_min', 'c_min'])
        )));
        $this->assertSame([0, 15, 30], array_values(array_intersect_key(
            $config['debt'],
            array_flip(['a_max', 'b_max', 'c_max'])
        )));
        $this->assertSame(2, $config['inactivity_risk_months']);
    }
}
