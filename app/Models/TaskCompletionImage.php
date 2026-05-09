<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCompletionImage extends Model
{
    protected $fillable = [
        'task_id',
        'image_path',
        'original_filename',
        'sort_order',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class, 'task_id');
    }

    public function getImageUrl(): string
    {
        return asset('storage/' . $this->image_path);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
