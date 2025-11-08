<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'is_published',
        'post_category_id',
        'user_id',
    ];
    protected static function booted()
    {
        static::creating(function ($post) {
            // tự động tạo slug từ title nếu chưa có 
            if (empty($post->slug)) {
                $slug = Str::slug($post->title);
                $original = $slug;
                $counter = 1;

                // Lặp cho đến khi slug unique
                while (Post::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }

                $post->slug = $slug;
            }

            // tự gán user_id nếu chưa có
            if (empty($post->user_id) && Auth::check()) {
                $post->user_id = Auth::id();
            }
        });

        // Khi update
        static::updating(function ($post) {
            if (Auth::check()) {
                $post->updated_by = Auth::id(); // Người sửa
            }
        });
        
    }
    public function category()
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }
}