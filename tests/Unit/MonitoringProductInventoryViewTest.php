<?php

namespace Tests\Unit;

use Tests\TestCase;

class MonitoringProductInventoryViewTest extends TestCase
{
    public function test_product_summary_groups_variants_and_renders_dynamic_warehouse_stock_columns(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PageController.php'));
        $template = file_get_contents(resource_path('views/site/orders/monitoring.blade.php'));

        $this->assertStringContainsString("->groupBy('product_variant_id')", $controller);
        $this->assertStringContainsString("'variants' => \$variants", $controller);
        $this->assertStringContainsString("'warehouse_stocks'", $controller);
        $this->assertStringContainsString('@foreach($monitoringWarehouses as $warehouse)', $template);
        $this->assertStringContainsString('@foreach($row[\'variants\'] as $variant)', $template);
        $this->assertStringContainsString('Sản phẩm / Biến thể', $template);
        $this->assertStringContainsString('Khả dụng:', $template);
    }
}
