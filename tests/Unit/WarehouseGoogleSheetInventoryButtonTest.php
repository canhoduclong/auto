<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarehouseGoogleSheetInventoryButtonTest extends TestCase
{
    public function test_dashboard_has_separate_stocktake_and_sheet_load_buttons(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/warehouse/dashboard.blade.php');
        $stocktake = strpos($view, "route('warehouse.stocktakes.index')");
        $sheetStocktake = strpos($view, "'load_sheet_closing' => 1");
        $sheetImport = strpos($view, "route('warehouse.google-sheet-inventory.index')");

        $this->assertNotFalse($stocktake);
        $this->assertNotFalse($sheetStocktake);
        $this->assertNotFalse($sheetImport);
        $this->assertGreaterThan($stocktake, $sheetStocktake);
        $this->assertGreaterThan($sheetStocktake, $sheetImport);
        $this->assertStringContainsString('Kiểm kê kho', $view);
        $this->assertStringContainsString('Load kiểm kê', $view);
        $this->assertStringContainsString('Nhập SX = Thu Mua', $view);
        $this->assertStringContainsString('Ghi tồn kho', $view);
        $this->assertStringContainsString("route('warehouse.google-sheet-inventory.export.index')", $view);
    }
}
