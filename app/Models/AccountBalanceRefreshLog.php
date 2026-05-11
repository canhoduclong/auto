<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBalanceRefreshLog extends Model
{
    protected $fillable = [
        'refreshed_by',
        'filter_account_id',
        'from_date',
        'to_date',
        'accounts_reconciled',
        'accounts_updated',
        'total_amount_adjusted',
        'results_json',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'accounts_reconciled' => 'integer',
        'accounts_updated' => 'integer',
        'total_amount_adjusted' => 'decimal:2',
        'results_json' => 'array',
    ];

    public function performer()
    {
        return $this->belongsTo(User::class, 'refreshed_by');
    }

    public function filterAccount()
    {
        return $this->belongsTo(Account::class, 'filter_account_id');
    }
}
