<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingSalesImportBatch extends Model
{
    protected $fillable = ['imported_by', 'source_hash', 'row_count', 'total_amount', 'raw_text'];

    protected $casts = ['total_amount' => 'decimal:2'];

    public function entries()
    {
        return $this->hasMany(AccountingSalesEntry::class, 'import_batch_id');
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
