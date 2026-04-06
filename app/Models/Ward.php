<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'code',
        'name',
        'type',
        'old_code',
        'old_name',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
