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
