<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        DB::table('products')->truncate();

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $userId = DB::table('users')->min('id');
        if (!$userId) {
            return;
        }

        $categorySlug = 'vit-tuoi-va-so-che';
        $categoryId = DB::table('categories')->where('slug', $categorySlug)->value('id');

        if (!$categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Vịt tươi và sơ chế',
                'slug' => $categorySlug,
                'description' => 'Nhóm sản phẩm vịt tươi, vịt sơ chế và phụ kiện đóng hàng.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $hasStatus = Schema::hasColumn('products', 'status');

        // name, description, unit, status (0=inactive)
        $products = [
            ['name' => 'Vịt nguyên con',    'description' => 'Vịt nguyên con đã làm sạch, phân loại theo trọng lượng.',  'unit' => 'con',  'status' => 1],
            ['name' => 'Vịt loại 2',        'description' => 'Vịt sơ chế theo quy cách loại bầm.',                       'unit' => 'cai',  'status' => 1],
            ['name' => 'Vịt bọng',          'description' => 'Vịt bọng với nhiều lựa chọn sơ chế theo yêu cầu.',         'unit' => 'con',  'status' => 1],
            ['name' => 'Đùi góc tư vịt',    'description' => 'Phần đùi góc tư vịt đã sơ chế.',                          'unit' => 'kg',   'status' => 1],
            ['name' => 'Ức vịt',            'description' => 'Ức vịt các dạng dài, ngắn, phi lê.',                       'unit' => 'kg',   'status' => 1],
            ['name' => 'Cánh vịt',          'description' => 'Cánh vịt chia 2 khúc hoặc 3 khúc.',                        'unit' => 'cai',  'status' => 1],
            ['name' => 'Đầu vịt',           'description' => 'Đầu vịt sơ chế theo lô.',                                  'unit' => 'cai',  'status' => 1],
            ['name' => 'Đầu cổ vịt',        'description' => 'Đầu cổ vịt đóng theo quy cách sản xuất.',                  'unit' => 'kg',   'status' => 1],
            ['name' => 'Phao câu vịt',      'description' => 'Phao câu vịt sơ chế.',                                     'unit' => 'kg',   'status' => 1],
            ['name' => 'Lòng vịt',          'description' => 'Lòng vịt phân loại theo size lớn, vừa, nhỏ.',              'unit' => 'bo',   'status' => 1],
            ['name' => 'Mề vịt',            'description' => 'Mề vịt sơ chế.',                                           'unit' => 'cai',  'status' => 1],
            ['name' => 'Huyết vịt',         'description' => 'Huyết vịt tươi theo lô.',                                  'unit' => 'banh', 'status' => 1],
            ['name' => 'Thùng xốp',         'description' => 'Thùng xốp phục vụ quy cách đóng hàng 50, 60, 70, 100.',   'unit' => 'cai',  'status' => 0],
        ];

        $hasUnit = Schema::hasColumn('products', 'unit');
        $hasIsKg = Schema::hasColumn('products', 'is_priced_by_kg');

        $rows = [];
        foreach ($products as $product) {
            $row = [
                'name' => $product['name'],
                'slug' => Str::slug($product['name']),
                'description' => $product['description'],
                'price' => 0,
                'stock' => 0,
                'image' => null,
                'user_id' => $userId,
                'category_id' => $categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($hasStatus) {
                $row['status'] = $product['status'];
            }

            if ($hasUnit) {
                $row['unit'] = $product['unit'];
            }

            if ($hasIsKg) {
                $row['is_priced_by_kg'] = 1;
                $row['kg'] = 1;
            }

            $rows[] = $row;
        }

        DB::table('products')->insert($rows);
    }
}
