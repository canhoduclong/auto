<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseDispatchSlip extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'business_date', 'source_warehouse_id', 'target_warehouse_id',
        'shipper_id', 'status', 'notes', 'created_by', 'finalized_by',
        'finalized_at', 'print_count',
    ];

    protected $casts = [
        'business_date' => 'date',
        'finalized_at' => 'datetime',
        'print_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::created(function (self $slip): void {
            if ($slip->code) {
                return;
            }

            $slip->updateQuietly([
                'code' => 'PXKT-'.$slip->business_date->format('Ymd').'-'.str_pad((string) $slip->id, 5, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function viewers()
    {
        return $this->belongsToMany(User::class, 'warehouse_dispatch_slip_views')->withPivot('viewed_at');
    }

    public function transportProgress(): array
    {
        $statuses = $this->entries->flatMap(function ($entry) {
            if ($entry->warehouse_transfer_id) {
                return [$entry->warehouseTransfer?->status ?? 'unknown'];
            }
            if ($entry->inventory_transfer_id) {
                return [$entry->inventoryTransfer?->status ?? 'unknown'];
            }
            $orders = $entry->orderTransfer?->orders ?? collect();
            return $orders->isEmpty() ? ['unknown'] : $orders->map(
                fn ($order) => $order->warehouseTransfers->sortByDesc('id')->first()?->status ?? 'unknown'
            );
        });
        $active = $statuses->reject(fn ($status) => $status === 'cancelled');
        $completed = $active->filter(fn ($status) => $status === 'received_completed')->count();
        $key = match (true) {
            $this->status === self::STATUS_CANCELLED => 'cancelled',
            $statuses->isNotEmpty() && $active->isEmpty() => 'cancelled',
            $active->isNotEmpty() && $completed === $active->count() => 'completed',
            $active->contains('in_transit') => 'transit',
            $active->contains('pending_shipper_pickup') && $active->unique()->count() === 1 => 'pending',
            $active->contains('pending_shipper_pickup') => 'transit',
            $active->contains('delivered_waiting_receive') || $active->contains('pending_receive') => 'waiting',
            default => 'pending',
        };
        $labels = ['pending' => 'Chờ nhận hàng', 'transit' => 'Đang thực hiện', 'waiting' => 'Đã giao · Chờ kho nhận', 'completed' => 'Hoàn tất · Kho đã nhận', 'cancelled' => 'Đã hủy'];
        return ['key' => $key, 'label' => $labels[$key], 'completed' => $completed, 'total' => $active->count()];
    }

    public function entries()
    {
        return $this->hasMany(WarehouseDispatchSlipEntry::class);
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function targetWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
