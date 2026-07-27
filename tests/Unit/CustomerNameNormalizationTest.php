<?php

namespace Tests\Unit;

use App\Models\Customer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerNameNormalizationTest extends TestCase
{
    #[DataProvider('names')]
    public function test_customer_names_have_a_stable_case_insensitive_comparison_value(
        string $name,
        string $expected
    ): void {
        $this->assertSame($expected, Customer::normalizeName($name));
    }

    public static function names(): array
    {
        return [
            'uppercase' => ['CHỊ LỆ', 'chị lệ'],
            'mixed case' => ['Chị Lệ', 'chị lệ'],
            'extra whitespace' => ["  Chị   Lệ\n", 'chị lệ'],
        ];
    }
}
