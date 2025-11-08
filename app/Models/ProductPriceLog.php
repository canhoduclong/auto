<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'price_rule_id',
        'old_price',
        'new_price',
        'applied_at',
        'applied_by',
        'user_id',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function priceRule()
    {
        return $this->belongsTo(ProductPriceRule::class, 'price_rule_id');
    }

    /**
     * Người đã áp dụng (applied_by)
     * Tên method: appliedBy() để khớp với with(['appliedBy'])
     */
    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Giữ thêm alias user() nếu có chỗ khác dùng ->user
     */
    public function user()
    {
        return $this->appliedBy();
        // return $this->belongsTo(User::class, 'applied_by');
    }
}
