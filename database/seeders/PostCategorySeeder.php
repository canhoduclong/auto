<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PostCategory::updateOrCreate(
            ['slug' => Str::slug('News')],
            [
            'name' => 'News',
            'slug' => Str::slug('News'),
            ]
        );

        PostCategory::updateOrCreate(
            ['slug' => Str::slug('Tutorials')],
            [
            'name' => 'Tutorials',
            'slug' => Str::slug('Tutorials'),
            ]
        );
    }
}