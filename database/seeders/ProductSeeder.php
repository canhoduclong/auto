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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $products = [
            'Vịt bọng - không sọ, không chân',
            'Vịt bọng ngắn - không sọ, không cổ, không chân',
            'Vịt tươi nguyên con',
            'Vịt quay lông nguyên con',
            'Lòng vịt làm sạch',
            'Mề vịt làm sạch',
            'Huyết vịt',
            'Đầu vịt',
            'Chân Vịt',
            'Cánh vịt',
            'Cánh tỏi vịt',
            'Đùi vịt góc tư',
            'Lòng vịt & mề làm sạch', 
            'Tim vịt',
            'Lông vịt',
            'Trưng vịt',
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
