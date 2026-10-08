<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessDefinition extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['configuration' => 'array', 'is_active' => 'boolean'];

    public function runs()
    {
        return $this->hasMany(ProcessRun::class, 'definition_id');
    }
}
