<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'title',
        'note',
        'image_path',
        'remind_at',
        'is_done',
        'appointment_score_counted_at',
        'meeting_score_counted_at',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'is_done' => 'boolean',
        'appointment_score_counted_at' => 'datetime',
        'meeting_score_counted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
