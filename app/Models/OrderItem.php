<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
        'base_price',
        'unit_discount',
        'discount_type',
        'discount_total',
        'unit_weight',
        'is_priced_by_kg',
        'total_weight',
        'actual_weight',
        'total',
    ];

    protected $casts = [
        'actual_weight' => 'decimal:3',
        'is_priced_by_kg' => 'boolean',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}
