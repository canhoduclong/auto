<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarehouseGoogleSheetInventoryButtonTest extends TestCase
{
    public function test_dashboard_has_load_inventory_button_next_to_stocktake(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/warehouse/dashboard.blade.php');
        $stocktake = strpos($view, "route('warehouse.stocktakes.index')");
        $sheetImport = strpos($view, "route('warehouse.google-sheet-inventory.index')");

        $this->assertNotFalse($stocktake);
        $this->assertNotFalse($sheetImport);
        $this->assertGreaterThan($stocktake, $sheetImport);
        $this->assertStringContainsString('Load tồn kho', $view);
        $this->assertStringContainsString('Ghi tồn kho', $view);
        $this->assertStringContainsString("route('warehouse.google-sheet-inventory.export.index')", $view);
    }
}
