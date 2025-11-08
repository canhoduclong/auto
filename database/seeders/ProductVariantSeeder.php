<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_variants')->truncate();
        DB::table('product_variant_values')->truncate();
        DB::table('product_attribute_values')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $products = \DB::table('products')->get();
        $data = [];
        $skuBase = 'SKU';
        foreach ($products as $i => $product) {
            for ($j = 1; $j <= 2; $j++) {
                // Giá mặc định cho biến thể, có thể lấy từ product hoặc random
                $defaultPrice = $product->price ? $product->price + rand(-100000, 100000) : rand(100000, 1000000);
                
                $size = $j == 1 ? 'M' : 'L';
                $quality = $j == 1 ? 'Loại 1' : 'Loại 2';

                $slugString = $product->name . ' ' . $size . ' ' . $quality;

                $name = $product->name . ' ' . $size . ' ' . $quality;
                $data[] = [
                    'product_id' => $product->id,
                    'sku' => $skuBase . ($i+1) . $j,
                    'name' => $name,
                    'slug' => Str::slug($slugString),
                    'size' => $size,
                    'quality' => $quality,
                    'production_date' => now()->subMonths($j),
                    'stock' => rand(5, 20),
                    'price' => $defaultPrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        // Chunk the inserts to avoid issues with too many placeholders
        foreach (array_chunk($data, 200) as $chunk) {
            \DB::table('product_variants')->insert($chunk);
        }
    }
}