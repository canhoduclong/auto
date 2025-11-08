<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $newsCategory = PostCategory::where('slug', 'news')->first();
        $tutorialsCategory = PostCategory::where('slug', 'tutorials')->first();

        $laravelTag = Tag::where('slug', 'laravel')->first();
        $phpTag = Tag::where('slug', 'php')->first();
        $jsTag = Tag::where('slug', 'javascript')->first();

        $post1 = Post::create([
            'title' => 'First Post',
            'slug' => Str::slug('First Post'),
            'content' => 'This is the content of the first post.',
            'is_published' => true,
            'post_category_id' => $newsCategory->id,
            'user_id' => 1, // Assuming user with id 1 exists
        ]);

        $post2 = Post::create([
            'title' => 'Second Post',
            'slug' => Str::slug('Second Post'),
            'content' => 'This is the content of the second post.',
            'is_published' => true,
            'post_category_id' => $tutorialsCategory->id,
            'user_id' => 1, // Assuming user with id 1 exists
        ]);

        $post1->tags()->attach([$laravelTag->id, $phpTag->id]);
        $post2->tags()->attach([$jsTag->id]);
    }
}