<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        DB::table('product_variants')->truncate();
        DB::table('product_variant_values')->truncate();
        DB::table('product_attribute_values')->truncate();

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $productNames = [
            'Vịt nguyên con làm sạch',
            'Vịt loại bầm',
            'Vịt bọng',
            'Đùi góc tư vịt',
            'Ức vịt',
            'Cánh vịt',
            'Đầu vịt',
            'Đầu cổ vịt',
            'Phao câu vịt',
            'Lòng vịt',
            'Mề vịt',
            'Huyết vịt',
            'Huyết nếp',
            'Thùng xốp đóng hàng',
        ];

        $products = DB::table('products')
            ->whereIn('name', $productNames)
            ->get()
            ->keyBy('name');

        $variantMap = [
            'Vịt nguyên con làm sạch' => [
                ['name' => '2.0 kg',          'sku' => 'MOC - 3.2', 'size' => '3.20', 'kg' => '3.20', 'status' => 1],
                ['name' => '2.1 kg',          'sku' => 'MOC - 3.1', 'size' => '3.10', 'kg' => '3.10', 'status' => 1],
                ['name' => '2.2 kg',          'sku' => 'MOC - 3.0', 'size' => '3.00', 'kg' => '3.00', 'status' => 1],
                ['name' => '2.3 kg',          'sku' => 'MOC - 2.9', 'size' => '2.90', 'kg' => '2.90', 'status' => 1],
                ['name' => '2.4 kg',          'sku' => 'MOC - 2.8', 'size' => '2.80', 'kg' => '2.80', 'status' => 1],
                ['name' => '2.5 kg',          'sku' => 'MOC - 2.7', 'size' => '2.70', 'kg' => '2.70', 'status' => 1],
                ['name' => '2.6 kg',          'sku' => 'MOC - 2.6', 'size' => '2.60', 'kg' => '2.60', 'status' => 1],
                ['name' => '2.7 kg',          'sku' => 'MOC - 2.5', 'size' => '2.50', 'kg' => '2.50', 'status' => 1],
                ['name' => '2.8 kg',          'sku' => 'MOC - 2.4', 'size' => '2.40', 'kg' => '2.40', 'status' => 1],
                ['name' => '2.9 kg',          'sku' => 'MOC - 2.3', 'size' => '2.30', 'kg' => '2.30', 'status' => 1],
                ['name' => '3.0 kg',          'sku' => 'MOC - 2.2', 'size' => '2.20', 'kg' => '2.20', 'status' => 1],
                ['name' => 'Lớn hơn 3.0 kg', 'sku' => 'MOC-2.1',   'size' => '2.10', 'kg' => '2.10', 'status' => 1],
            ],
            'Vịt loại bầm' => [
                ['name' => 'Vịt loại bầm',    'sku' => '9VAHKACWDF', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Vịt bọng' => [
                ['name' => 'Vịt bọng tiêu chuẩn', 'sku' => 'WCSF2K0TPH', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Đùi góc tư vịt' => [
                ['name' => 'Đùi góc tư vịt',  'sku' => 'PYALNS1IRU', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Ức vịt' => [
                ['name' => 'Ức vịt dài',      'sku' => 'IIQ8ALM5PA', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Cánh vịt' => [
                ['name' => 'Cánh 3 khúc',     'sku' => 'CWVN9PZ56M', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Đầu vịt' => [
                ['name' => 'Đầu vịt',         'sku' => 'VGVP4VT2XB', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Đầu cổ vịt' => [
                ['name' => 'Đầu cổ vịt',      'sku' => 'GJUT0GFRTY', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Phao câu vịt' => [
                ['name' => 'Phao câu vịt',    'sku' => 'EEBTAH6942', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Lòng vịt' => [
                ['name' => 'Size lớn',        'sku' => 'EVIG5QNPRO', 'size' => null, 'kg' => '1.00', 'status' => 1],
                ['name' => 'Size vừa',        'sku' => 'RDDSVJKDIM', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Mề vịt' => [
                ['name' => 'Mề vịt',          'sku' => 'IHP6BHFFUK', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Huyết vịt' => [
                ['name' => 'Huyết vịt',       'sku' => 'MVLCDWRJHD', 'size' => null, 'kg' => '1.00', 'status' => 1],
            ],
            'Huyết nếp' => [
                ['name' => 'Huyết nếp',       'sku' => 'VIT-00036', 'size' => null, 'kg' => '1.00', 'status' => 0],
            ],
            'Thùng xốp đóng hàng' => [
                ['name' => 'Thùng xốp 50',    'sku' => 'VIT-00037', 'size' => '50', 'kg' => '1.00', 'status' => 0],
                ['name' => 'Thùng xốp 60',    'sku' => 'VIT-00038', 'size' => '60', 'kg' => '1.00', 'status' => 0],
                ['name' => 'Thùng xốp 70',    'sku' => 'VIT-00039', 'size' => '70', 'kg' => '1.00', 'status' => 0],
                ['name' => 'Thùng xốp 100',   'sku' => 'VIT-00040', 'size' => '100','kg' => '1.00', 'status' => 0],
            ],
        ];

        $hasStatus = Schema::hasColumn('product_variants', 'status');
        $hasKg     = Schema::hasColumn('product_variants', 'kg');
        $hasIsKg   = Schema::hasColumn('product_variants', 'is_priced_by_kg');
        $rows = [];

        foreach ($variantMap as $productName => $variants) {
            $product = $products->get($productName);
            if (!$product) {
                continue;
            }

            foreach ($variants as $variant) {
                $variantName = $variant['name'];
                $slug = Str::slug($productName . '-' . $variantName . '-' . $variant['sku']);

                $row = [
                    'product_id'     => $product->id,
                    'sku'            => $variant['sku'],
                    'name'           => $variantName,
                    'slug'           => $slug,
                    'size'           => $variant['size'],
                    'quality'        => null,
                    'production_date'=> null,
                    'stock'          => 0,
                    'price'          => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];

                if ($hasStatus) {
                    $row['status'] = $variant['status'];
                }

                if ($hasKg) {
                    $row['kg'] = $variant['kg'];
                }

                if ($hasIsKg) {
                    $row['is_priced_by_kg'] = 1;
                }

                $rows[] = $row;
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('product_variants')->insert($chunk);
        }
    }
}