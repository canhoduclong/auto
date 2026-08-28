<?php

namespace Tests\Unit;

use App\Services\GoogleSheetsJournalService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GoogleSheetsJournalServiceTest extends TestCase
{
    public function test_journal_row_includes_converted_date_value_in_column_o(): void
    {
        $row = (object) [
            'entry_date' => '2026-08-22',
            'entry_month' => 8,
            'customer_code' => 'KH001',
            'customer_name' => 'Khách hàng',
            'sale_name' => 'NVKD',
            'unit' => 'Con',
            'quantity' => 10,
            'unit_weight' => 2.5,
            'total_quantity' => 25,
            'unit_price' => 70000,
            'total_amount' => 1750000,
        ];

        $method = new ReflectionMethod(GoogleSheetsJournalService::class, 'journalValues');
        $method->setAccessible(true);

        /** @var Collection<int, array<int, mixed>> $values */
        $values = $method->invoke(new GoogleSheetsJournalService, collect([$row]));
        $exportedRow = $values->first();

        $this->assertCount(15, $exportedRow);
        $this->assertSame(['', '', ''], array_slice($exportedRow, 11, 3));
        $this->assertSame(46256, $exportedRow[14]);
    }
}
