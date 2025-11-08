<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tag::create([
            'name' => 'Laravel',
            'slug' => Str::slug('Laravel'),
        ]);

        Tag::create([
            'name' => 'PHP',
            'slug' => Str::slug('PHP'),
        ]);

        Tag::create([
            'name' => 'JavaScript',
            'slug' => Str::slug('JavaScript'),
        ]);
    }
}