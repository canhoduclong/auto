<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Thịt vịt tươi',
            'Thịt Bò',
            'Thịt Gà',
            'Thịt Heo', 
        ];

        foreach ($categories as $categoryName) {
            Category::updateOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName)]
            );
        }
    }
}
