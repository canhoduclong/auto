<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'document_number',
        'warehouse_id',
        'document_date',
        'notes',
        'shipping_fee',
        'user_id',
        'edit_count',
    ];

    protected $casts = [
        'document_date' => 'date',
        'shipping_fee' => 'decimal:2',
        'edit_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::created(function (self $doc) {
            if ($doc->document_number) {
                return;
            }
            $prefix = match ($doc->type) {
                'import' => 'PNK',
                'export' => 'PXK',
                default  => 'PDC',
            };
            $doc->updateQuietly([
                'document_number' => $prefix . '-' . now()->format('Ymd') . '-' . str_pad($doc->id, 4, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function items()
    {
        return $this->hasMany(InventoryDocumentItem::class);
    }

    public function edits()
    {
        return $this->hasMany(InventoryDocumentEdit::class)->orderBy('edit_number');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}