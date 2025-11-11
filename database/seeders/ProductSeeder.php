<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        */
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Product::truncate();

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }


        $products = [
            'Gương chiếu hậu ô tô',
            'Đèn pha LED siêu sáng',
            'camara hành trình 4K',
            'Cảm biến áp suất lốp',
            'vali kéo du lịch',
            'Nước rửa xe ô tô',
        ];

        foreach ($products as $productName) {
            
            Product::create([
                'name' => $productName,
                'slug' => Str::slug($productName),
                'description' => $productName,
                'price' => rand(50000, 200000),
                'stock' => rand(10, 100),
                'user_id' => 1, // Assuming user with ID 1 is an admin or a valid user
                'category_id' => rand(1, 5), // Assuming categories with these IDs exist
            ]);
        }
    }
}
