<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCareLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'note',
        'action_type',
        'score_earned',
        'cycle_no',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
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
