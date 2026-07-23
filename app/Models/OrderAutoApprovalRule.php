<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAutoApprovalRule extends Model
{
    use HasFactory;

    public const TYPE_NEW_ORDER = 'new_order';
    public const TYPE_ORDER_ADJUSTMENT = 'order_adjustment';

    protected $fillable = [
        'user_id',
        'order_type',
        'enabled',
        'require_min_price',
        'allow_bulk_below_min',
        'bulk_min_quantity',
        'bulk_below_min_amount',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'require_min_price' => 'boolean',
        'allow_bulk_below_min' => 'boolean',
        'bulk_min_quantity' => 'integer',
        'bulk_below_min_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
