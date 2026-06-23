<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuckProcessingConversionRate extends Model
{
    protected $fillable = ['live_size', 'processed_size', 'percentage'];
    protected $casts = ['live_size' => 'decimal:1', 'processed_size' => 'decimal:1', 'percentage' => 'decimal:3'];
}
