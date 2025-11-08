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
        PostCategory::create([
            'name' => 'News',
            'slug' => Str::slug('News'),
        ]);

        PostCategory::create([
            'name' => 'Tutorials',
            'slug' => Str::slug('Tutorials'),
        ]);
    }
}