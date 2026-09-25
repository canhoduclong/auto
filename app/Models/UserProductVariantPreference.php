<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProductVariantPreference extends Model
{
    protected $fillable = [
        'user_id',
        'product_variant_id',
        'is_pinned',
        'sort_order',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
