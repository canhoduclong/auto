<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'weight_kg',
        'reserved_quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'quantity' => 'float',
        'weight_kg' => 'float',
        'reserved_quantity' => 'float',
        'low_stock_threshold' => 'float',
    ];

    protected static function booted(): void
    {
        static::updating(function (Inventory $inventory): void {
            if (! $inventory->isDirty('quantity')) return;
            $packed = (float) $inventory->reservations()->whereNotNull('packed_at')->sum('quantity');
            $decrease = (float) $inventory->getOriginal('quantity') - (float) $inventory->quantity;
            $released = max(0, (float) $inventory->getOriginal('reserved_quantity') - (float) $inventory->reserved_quantity);
            $actualReserved = (float) $inventory->reservations()->sum('quantity');
            if ((float) $inventory->reserved_quantity + 0.00001 < $actualReserved) $released = 0;
            // Receiving more goods must not be blocked by an older discrepancy.
            // Consuming a matching sealed reservation preserves that discrepancy
            // without spending any other order's goods.
            if ($decrease > 0 && (float) $inventory->quantity < $packed && $released + 0.00001 < $decrease) {
                throw new \RuntimeException('Không thể giảm tồn kho xuống dưới '.$packed.' hàng đã đóng và giữ riêng cho đơn.');
            }
        });
    }

    public function getOnHandAttribute(): float
    {
        return (float) $this->quantity;
    }

    public function getReservedAttribute(): float
    {
        return (float) $this->reserved_quantity;
    }

    public function getAvailableAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->reserved_quantity);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function reservations()
    {
        return $this->hasMany(InventoryReservation::class);
    }
}
