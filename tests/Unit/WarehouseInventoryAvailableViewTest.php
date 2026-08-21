<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarehouseInventoryAvailableViewTest extends TestCase
{
    public function test_warehouse_dashboard_shows_available_stock_for_products_and_variants(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/warehouse/_inventory_summary.blade.php'
        );
        $service = file_get_contents(
            dirname(__DIR__, 2) . '/app/Services/WarehouseInventorySummaryService.php'
        );

        $this->assertStringContainsString('>Khả dụng</div>', $view);
        $this->assertStringContainsString("number_format(\$row['available'])", $view);
        $this->assertStringContainsString("number_format(\$variantRow['available'])", $view);
        $this->assertStringContainsString("'available' => max(0, \$closing - \$reserved)", $service);
        $this->assertStringContainsString("'available' => (float) \$variantRows->sum('available')", $service);
    }
}
