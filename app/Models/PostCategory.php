<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PostCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
    ];

    protected static function booted()
    {
        static::creating(function ($category) {
            // Tự động tạo slug từ name nếu chưa có 
            if (empty($category->slug)) {
                $slug = Str::slug($category->name);
                $original = $slug;
                $counter = 1;

                // Lặp cho đến khi slug unique trong bảng post_categories
                while (PostCategory::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }

                $category->slug = $slug;
            }

            // Tự gán user_id nếu chưa có
            if (empty($category->user_id) && Auth::check()) {
                $category->user_id = Auth::id();
            }
        });
         // Khi update
        static::updating(function ($category) {
            if (Auth::check()) {
                $category->updated_by = Auth::id(); // Người sửa
            }
        });
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
