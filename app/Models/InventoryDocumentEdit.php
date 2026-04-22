<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDocumentEdit extends Model
{
    protected $fillable = [
        'inventory_document_id',
        'user_id',
        'edit_number',
        'notes',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(InventoryDocument::class, 'inventory_document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
