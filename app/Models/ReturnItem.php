<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_return_id',
        'product_variant_id',
        'quantity',
        'condition',
        'original_weight',
        'received_weight',
        'weight_loss',
        'weight_confirmed_at',
    ];

    protected $casts = [
        'weight_confirmed_at' => 'datetime',
    ];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Calculate weight loss when received weight is set
     */
    public function calculateWeightLoss()
    {
        if ($this->original_weight && $this->received_weight) {
            $this->weight_loss = max(0, $this->original_weight - $this->received_weight);
        }
        return $this;
    }

    /**
     * Get weight loss percentage
     */
    public function getWeightLossPercentageAttribute()
    {
        if (!$this->original_weight || $this->original_weight == 0) {
            return 0;
        }
        return ($this->weight_loss / $this->original_weight) * 100;
    }

    /**
     * Check if weight has been recorded
     */
    public function hasWeightRecorded()
    {
        return !is_null($this->received_weight);
    }
}