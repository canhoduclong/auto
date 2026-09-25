<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCuttingBatch extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'warehouse_id',
        'target_product_variant_id',
        'order_id',
        'status',
        'source_materials',
        'picked_material_verifications',
        'performed_by',
        'completed_by',
        'completed_at',
        'export_document_id',
        'finished_import_document_id',
        'component_import_document_id',
        'input_weight',
        'planned_finished_weight',
        'actual_finished_weight',
        'actual_component_weight',
        'loss_weight',
        'loss_percent',
        'planned_components',
        'actual_components',
        'note',
    ];

    protected $casts = [
        'planned_finished_weight' => 'float',
        'actual_finished_weight' => 'float',
        'input_weight' => 'float',
        'actual_component_weight' => 'float',
        'loss_weight' => 'float',
        'loss_percent' => 'float',
        'source_materials' => 'array',
        'picked_material_verifications' => 'array',
        'planned_components' => 'array',
        'actual_components' => 'array',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function targetVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'target_product_variant_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function exportDocument()
    {
        return $this->belongsTo(InventoryDocument::class, 'export_document_id');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
