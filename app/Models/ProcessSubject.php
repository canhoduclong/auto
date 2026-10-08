<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessSubject extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function run()
    {
        return $this->belongsTo(ProcessRun::class, 'run_id');
    }
}
