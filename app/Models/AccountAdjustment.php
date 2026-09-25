<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountAdjustment extends Model
{
    protected $fillable = [
        'account_id',
        'performed_by',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'note',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function typeLabel(): string
    {
        return $this->type === 'deposit' ? 'Nạp tiền' : 'Rút tiền';
    }
}
