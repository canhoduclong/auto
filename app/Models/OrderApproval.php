<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderApproval extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'role',
        'status',
        'note',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
