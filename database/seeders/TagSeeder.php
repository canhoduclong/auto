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
        Tag::updateOrCreate(
            ['slug' => Str::slug('Laravel')],
            [
            'name' => 'Laravel',
            'slug' => Str::slug('Laravel'),
            ]
        );

        Tag::updateOrCreate(
            ['slug' => Str::slug('PHP')],
            [
            'name' => 'PHP',
            'slug' => Str::slug('PHP'),
            ]
        );

        Tag::updateOrCreate(
            ['slug' => Str::slug('JavaScript')],
            [
            'name' => 'JavaScript',
            'slug' => Str::slug('JavaScript'),
            ]
        );
    }
}