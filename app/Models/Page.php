<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'user_id'];

    protected static function booted()
    {
        static::creating(function ($page) {
            // Tự động tạo slug từ title nếu chưa có 
            if (empty($page->slug)) {
                $slug = Str::slug($page->title);
                $original = $slug;
                $counter = 1;

                // Lặp cho đến khi slug unique trong bảng pages
                while (Page::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }

                $page->slug = $slug;
            }

            // Tự gán user_id nếu chưa có
            if (empty($page->user_id) && Auth::check()) {
                $page->user_id = Auth::id();
            }
        });
         // Khi update
        static::updating(function ($page) {
            if (Auth::check()) {
                $page->updated_by = Auth::id(); // Người sửa
            }
        });

    }
}
