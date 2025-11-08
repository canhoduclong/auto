<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'description', 
        'image',
    ];
    
    // Tạo slug tự động nếu chưa có
    public static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . uniqid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function mediaLinks()
    {
        return $this->morphMany(MediaLink::class, 'model');
    }

    public function thumbnail()
    {
        return $this->morphOne(MediaLink::class, 'model')
                    ->where('role', 'thumbnail')
                    ->with('media');
    }

    public function avatar()
    {
        return $this->morphOne(MediaLink::class, 'model')
                    ->where('role', 'avatar')
                    ->with('media');
    }
    
    public function gallery()
    {
        return $this->morphMany(MediaLink::class, 'model')
                    ->where('role', 'gallery')
                    ->with('media');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    
    // lấy biến thể mặc định (nếu chỉ có 1 biến thể)
    public function defaultVariant()
    {
        return $this->variants()->first();
    }




}
