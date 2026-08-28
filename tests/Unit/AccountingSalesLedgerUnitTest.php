<?php

namespace Tests\Unit;

use App\Services\AccountingSalesLedgerService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AccountingSalesLedgerUnitTest extends TestCase
{
    #[DataProvider('duckProductNames')]
    public function test_duck_bag_products_are_exported_as_con(string $productName): void
    {
        $this->assertSame('Con', (new AccountingSalesLedgerService)->ledgerUnit($productName, 'Con'));
    }

    public static function duckProductNames(): array
    {
        return [
            ['Vịt bọng không Đầu Cổ Chân'],
            ['Vịt bọng không Đầu Chân'],
            ['Vịt bọng'],
            ['VIT BONG KHONG DAU CHAN'],
        ];
    }
}
