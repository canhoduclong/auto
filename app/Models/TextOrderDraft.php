<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TextOrderDraft extends Model
{
    protected $fillable = [
        'created_by', 'sale_id', 'customer_id', 'product_variant_id', 'order_id',
        'zalo_name', 'customer_name', 'phone', 'address', 'product_text',
        'parsed_items', 'quantity', 'size_kg', 'unit_price', 'delivery_date', 'delivery_time',
        'note', 'raw_text', 'status', 'error_message',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'size_kg' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'parsed_items' => 'array',
    ];

    public function sale() { return $this->belongsTo(User::class, 'sale_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function order() { return $this->belongsTo(Order::class); }
}
