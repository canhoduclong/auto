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
            'Âm thanh xe hơi',
            'Camera hành trình',
            'Thiết bị máy lạnh',
            'Đèn xe',
            'Ghế lái & nội thất',
            'Phanh',
            'Lốp xe',
            'Phụ kiện trang trí',
            'Thiết bị an ninh',
            'Đồ chơi xe hơi',
            'dau nhot xe hoi',
            'Phụ tùng thay thế',
            'Thiết bị hỗ trợ lái xe',
            'Thiết bị điện tử',
        ];

        foreach ($categories as $categoryName) {
            Category::updateOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName)]
            );
        }
    }
}
