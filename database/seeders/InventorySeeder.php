<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Inventory data keyed by SKU so it is warehouse-agnostic across fresh seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::all()->keyBy('name');
        $variants   = ProductVariant::all()->keyBy('sku');

        if ($warehouses->isEmpty() || $variants->isEmpty()) {
            $this->command->info('No warehouses or product variants found.');
            return;
        }

        // [sku, warehouse_name, quantity, low_stock_threshold]
        $rows = [
            ['MOC - 3.2', 'Kho Chiến Lược', 98,  20],
            ['MOC - 3.1', 'Kho Long An',    16,  16],
            ['MOC - 3.0', 'Kho Chiến Lược', 79,   9],
            ['MOC - 2.9', 'Kho Long An',    106,  9],
            ['MOC - 2.8', 'Kho Long An',    65,  14],
            ['MOC - 2.7', 'Kho Chiến Lược', 79,  20],
            ['MOC - 2.6', 'Kho Long An',    24,  10],
            ['MOC - 2.5', 'Kho Chiến Lược', 58,   6],
            ['MOC - 2.4', 'Kho Chiến Lược', 124, 15],
            ['MOC - 2.3', 'Kho Chiến Lược', 151,  5],
            ['MOC - 2.2', 'Kho Chiến Lược', 50,  16],
            ['MOC-2.1',   'Kho Long An',    44,   8],
            ['9VAHKACWDF', 'Kho Chiến Lược', 190,  9],
            ['WCSF2K0TPH', 'Kho Long An',    31,  17],
            ['PYALNS1IRU', 'Kho Chiến Lược', 162, 12],
            ['IIQ8ALM5PA', 'Kho Chiến Lược', 88,  11],
            ['CWVN9PZ56M', 'Kho Long An',    197,  7],
            ['VGVP4VT2XB', 'Kho Chiến Lược', 189, 13],
            ['GJUT0GFRTY', 'Kho Long An',    29,   5],
            ['EEBTAH6942', 'Kho Long An',    23,   6],
            ['EVIG5QNPRO', 'Kho Long An',    164,  8],
            ['RDDSVJKDIM', 'Kho Long An',    132, 13],
            ['IHP6BHFFUK', 'Kho Chiến Lược', 105, 13],
            ['MVLCDWRJHD', 'Kho Long An',    86,  19],
            ['VIT-00036',  'Kho Chiến Lược', 74,  13],
            ['VIT-00037',  'Kho Long An',    98,   7],
            ['VIT-00038',  'Kho Long An',    131,  5],
            ['VIT-00039',  'Kho Chiến Lược', 41,  15],
            ['VIT-00040',  'Kho Long An',    173,  6],
        ];

        $inventories = [];
        foreach ($rows as [$sku, $warehouseName, $qty, $threshold]) {
            $variant   = $variants->get($sku);
            $warehouse = $warehouses->get($warehouseName);

            if (!$variant || !$warehouse) {
                continue;
            }

            $inventories[] = [
                'product_variant_id' => $variant->id,
                'warehouse_id'       => $warehouse->id,
                'quantity'           => $qty,
                'reserved_quantity'  => 0,
                'low_stock_threshold'=> $threshold,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        Inventory::upsert(
            $inventories,
            ['product_variant_id', 'warehouse_id'],
            ['quantity', 'low_stock_threshold', 'updated_at']
        );
    }
}
