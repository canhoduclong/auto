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
                ['name' => '2.0 kg', 'size' => '2.0'],
                ['name' => '2.1 kg', 'size' => '2.1'],
                ['name' => '2.2 kg', 'size' => '2.2'],
                ['name' => '2.3 kg', 'size' => '2.3'],
                ['name' => '2.4 kg', 'size' => '2.4'],
                ['name' => '2.5 kg', 'size' => '2.5'],
                ['name' => '2.6 kg', 'size' => '2.6'],
                ['name' => '2.7 kg', 'size' => '2.7'],
                ['name' => '2.8 kg', 'size' => '2.8'],
                ['name' => '2.9 kg', 'size' => '2.9'],
                ['name' => '3.0 kg', 'size' => '3.0'],
                ['name' => 'Lớn hơn 3.0 kg', 'size' => '> 3.0'],
            ],
            'Vịt loại bầm' => [
                ['name' => 'Vịt loại bầm', 'size' => null],
            ],
            'Vịt bọng' => [
                ['name' => 'Vịt bọng tiêu chuẩn', 'size' => null],
                ['name' => 'Vịt bọng (không mẩu cánh)', 'size' => null],
                ['name' => 'Vịt bọng (không đầu, cổ, chân)', 'size' => null],
                ['name' => 'Vịt bọng (không cổ, không mẩu cánh)', 'size' => null],
                ['name' => 'Vịt bọng (không cổ, không cánh)', 'size' => null],
            ],
            'Đùi góc tư vịt' => [
                ['name' => 'Đùi góc tư vịt', 'size' => null],
            ],
            'Ức vịt' => [
                ['name' => 'Ức vịt dài', 'size' => null],
                ['name' => 'Ức vịt dài (không cánh)', 'size' => null],
                ['name' => 'Ức vịt ngắn', 'size' => null],
                ['name' => 'Ức vịt ngắn (không cánh)', 'size' => null],
                ['name' => 'Ức vịt phi lê', 'size' => null],
                ['name' => 'Ức vịt phi lê không da', 'size' => null],
            ],
            'Cánh vịt' => [
                ['name' => 'Cánh 3 khúc', 'size' => null],
                ['name' => 'Cánh 2 khúc', 'size' => null],
            ],
            'Đầu vịt' => [
                ['name' => 'Đầu vịt', 'size' => null],
            ],
            'Đầu cổ vịt' => [
                ['name' => 'Đầu cổ vịt', 'size' => null],
            ],
            'Phao câu vịt' => [
                ['name' => 'Phao câu vịt', 'size' => null],
            ],
            'Lòng vịt' => [
                ['name' => 'Size lớn', 'size' => 'Size lớn'],
                ['name' => 'Size vừa', 'size' => 'Size vừa'],
                ['name' => 'Size nhỏ', 'size' => 'Size nhỏ'],
            ],
            'Mề vịt' => [
                ['name' => 'Mề vịt', 'size' => null],
            ],
            'Huyết vịt' => [
                ['name' => 'Huyết vịt', 'size' => null],
            ],
            'Huyết nếp' => [
                ['name' => 'Huyết nếp', 'size' => null],
            ],
            'Thùng xốp đóng hàng' => [
                ['name' => 'Thùng xốp 50', 'size' => '50'],
                ['name' => 'Thùng xốp 60', 'size' => '60'],
                ['name' => 'Thùng xốp 70', 'size' => '70'],
                ['name' => 'Thùng xốp 100', 'size' => '100'],
            ],
        ];

        $hasStatus = Schema::hasColumn('product_variants', 'status');
        $rows = [];
        $skuIndex = 1;

        foreach ($variantMap as $productName => $variants) {
            $product = $products->get($productName);
            if (!$product) {
                continue;
            }

            foreach ($variants as $variant) {
                $variantName = $variant['name'];
                $slug = Str::slug($productName . '-' . $variantName . '-' . $skuIndex);

                $row = [
                    'product_id' => $product->id,
                    'sku' => 'VIT-' . str_pad((string) $skuIndex, 5, '0', STR_PAD_LEFT),
                    'name' => $variantName,
                    'slug' => $slug,
                    'size' => $variant['size'],
                    'quality' => null,
                    'production_date' => null,
                    'stock' => 0,
                    'price' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($hasStatus) {
                    $row['status'] = 1;
                }

                $rows[] = $row;
                $skuIndex++;
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('product_variants')->insert($chunk);
        }
    }
}