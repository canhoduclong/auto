<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCuttingBatch extends Model
{
    protected $fillable = [
        'warehouse_id',
        'target_product_variant_id',
        'performed_by',
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
        'planned_components' => 'array',
        'actual_components' => 'array',
    ];
}
