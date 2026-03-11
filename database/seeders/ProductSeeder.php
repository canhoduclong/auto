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

        $products = [
            ['name' => 'Vịt nguyên con làm sạch', 'description' => 'Vịt nguyên con đã làm sạch, phân loại theo trọng lượng.'],
            ['name' => 'Vịt loại bầm', 'description' => 'Vịt sơ chế theo quy cách loại bầm.'],
            ['name' => 'Vịt bọng', 'description' => 'Vịt bọng với nhiều lựa chọn sơ chế theo yêu cầu.'],
            ['name' => 'Đùi góc tư vịt', 'description' => 'Phần đùi góc tư vịt đã sơ chế.'],
            ['name' => 'Ức vịt', 'description' => 'Ức vịt các dạng dài, ngắn, phi lê.'],
            ['name' => 'Cánh vịt', 'description' => 'Cánh vịt chia 2 khúc hoặc 3 khúc.'],
            ['name' => 'Đầu vịt', 'description' => 'Đầu vịt sơ chế theo lô.'],
            ['name' => 'Đầu cổ vịt', 'description' => 'Đầu cổ vịt đóng theo quy cách sản xuất.'],
            ['name' => 'Phao câu vịt', 'description' => 'Phao câu vịt sơ chế.'],
            ['name' => 'Lòng vịt', 'description' => 'Lòng vịt phân loại theo size lớn, vừa, nhỏ.'],
            ['name' => 'Mề vịt', 'description' => 'Mề vịt sơ chế.'],
            ['name' => 'Huyết vịt', 'description' => 'Huyết vịt tươi theo lô.'],
            ['name' => 'Huyết nếp', 'description' => 'Huyết nếp sơ chế cho sản xuất thực phẩm.'],
            ['name' => 'Thùng xốp đóng hàng', 'description' => 'Thùng xốp phục vụ quy cách đóng hàng 50, 60, 70, 100.'],
        ];

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
                $row['status'] = 1;
            }

            $rows[] = $row;
        }

        DB::table('products')->insert($rows);
    }
}
