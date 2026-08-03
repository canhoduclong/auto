<?php

namespace Tests\Unit;

use Tests\TestCase;

class MobileProductGroupPickerTest extends TestCase
{
    public function test_mobile_order_picker_loads_products_before_variants(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Api/Mobile/SaleApiController.php'));
        $service = file_get_contents(base_path('my_app/lib/services/sale_service.dart'));
        $screen = file_get_contents(base_path('my_app/lib/screens/sale/sale_screen.dart'));

        $this->assertStringContainsString('public function productGroups', $controller);
        $this->assertStringContainsString("'/sale/product-groups'", $service);
        $this->assertStringContainsString('ExpansionTile(', $screen);
        $this->assertStringContainsString("'Chọn biến thể'", $screen);
        $this->assertStringContainsString('variant[\'available_stock\']', $screen);
        $this->assertStringContainsString('Navigator.pop(', $screen);
    }
}
