<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Thịt vịt tươi',       'slug' => 'thit-vit-tuoi'],
            ['name' => 'Thịt Bò',              'slug' => 'thit-bo'],
            ['name' => 'Thịt Gà',              'slug' => 'thit-ga'],
            ['name' => 'Thịt Heo',             'slug' => 'thit-heo'],
            ['name' => 'Vịt tươi và sơ chế',   'slug' => 'vit-tuoi-va-so-che'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name']]
            );
        }
    }
}
