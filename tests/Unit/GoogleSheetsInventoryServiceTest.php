<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\GoogleSheetsInventoryService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class GoogleSheetsInventoryServiceTest extends TestCase
{
    public function test_it_reads_the_closing_column_for_date_and_maps_sheet_m20_to_system_moc20(): void
    {
        $values = [
            ['0'],
            ['SIZE/Ngày Tháng', '31/07/2026', '01/08/2026', '', '', ''],
            ['QUAY LÔNG', 'Tồn', 'Nhập SX', 'Nhập Từ KCL', 'Xuất', 'Tồn'],
            ['HÀNG MÓC'],
            ['M 2', '7', '', '', '', '5'],
            ['M 2,1', '', '', '', '', '8'],
            ['CHIẾN LƯỢC'],
            ['HÀNG MÓC'],
            ['M 2', '', '', '', '', '-'],
        ];
        $variants = new Collection([
            $this->variant(10, '2.00', 'MOC - 2.00', '2.0 kg', 'M2.0'),
            $this->variant(11, '2.10', 'MOC - 2.1', '2.1 kg'),
        ]);

        $result = (new GoogleSheetsInventoryService)->parseValues(
            $values,
            new Warehouse(['name' => 'Kho Long An']),
            '2026-08-01',
            $variants
        );

        $this->assertSame(5.0, $result['rows'][0]['quantity']);
        $this->assertSame(10, $result['rows'][0]['variant_id']);
        $this->assertSame('inventory_name', $result['rows'][0]['match_method']);
        $this->assertSame('MOC - 2.0', $result['rows'][0]['normalized_code']);
        $this->assertSame(13.0, $result['total_quantity']);
        $this->assertFalse($result['has_blocking_errors']);
    }

    public function test_strategic_warehouse_reads_only_the_strategic_section(): void
    {
        $values = [
            ['0'],
            ['SIZE/Ngày Tháng', '01/08/2026'],
            ['QUAY LÔNG', 'Tồn'],
            ['HÀNG MÓC'],
            ['M 2', '5'],
            ['CHIẾN LƯỢC'],
            ['HÀNG MÓC'],
            ['M 2', '3'],
        ];

        $result = (new GoogleSheetsInventoryService)->parseValues(
            $values,
            new Warehouse(['name' => 'Kho Chiến Lược']),
            '2026-08-01',
            new Collection([$this->variant(10, '2.00', 'MOC - 2.00', '2.0 kg')])
        );

        $this->assertSame('strategic', $result['warehouse_section']);
        $this->assertSame(3.0, $result['total_quantity']);
    }

    private function variant(int $id, string $size, string $sku, string $name, ?string $inventoryName = null): ProductVariant
    {
        $variant = new ProductVariant(['size' => $size, 'sku' => $sku, 'name' => $name, 'inventory_name' => $inventoryName]);
        $variant->id = $id;
        $variant->setRelation('product', new Product(['name' => 'Vịt Nguyên Con']));

        return $variant;
    }
}
