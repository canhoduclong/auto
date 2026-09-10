<?php

namespace Tests\Unit;

use App\Exports\DispatchHistoryExport;
use App\Models\ShipperDispatchHistory;
use PHPUnit\Framework\TestCase;

class DispatchHistoryExportTest extends TestCase
{
    public function test_export_contains_all_shippers_and_totals_from_the_selected_snapshot(): void
    {
        $history = (new ShipperDispatchHistory())->setDateFormat('Y-m-d H:i:s')->fill(['schedule_date' => '2026-09-09', 'version' => 3, 'route_plan' => [
            ['shipper_name' => 'Ship A', 'routes' => [['name' => 'Chuyến 1', 'orders' => [
                ['order_id' => 10, 'customer_name' => '=1+1', 'final_fee' => 25000],
            ]]]],
            ['shipper_name' => 'Ship B', 'routes' => [['name' => 'Chuyến 2', 'orders' => [
                ['order_id' => 20, 'final_fee' => 40000],
            ]]]],
        ]]);
        $export = new DispatchHistoryExport($history);
        $rows = $export->array();
        $this->assertCount(3, $rows);
        $this->assertSame('Ship A', $rows[0][2]);
        $this->assertSame('Ship B', $rows[1][2]);
        $this->assertEquals(65000, $rows[2][12]);
        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $cell = $sheet->getActiveSheet()->getCell('A1');
        $export->bindValue($cell, $rows[0][7]);
        $this->assertSame('s', $cell->getDataType());
    }
}
