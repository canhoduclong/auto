<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessDocument extends Model
{
    protected $guarded = ['id'];

    public function event()
    {
        return $this->belongsTo(ProcessEvent::class, 'event_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
