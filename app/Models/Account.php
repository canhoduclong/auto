<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'name', 'type', 'owner_type', 'owner_name', 'account_number', 'bank_name',
        'balance', 'opening_balance', 'warning_threshold', 'is_active', 'note',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'warning_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function isLowBalance(): bool
    {
        return (float) $this->balance < (float) $this->warning_threshold;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function ownerTypeLabel(): string
    {
        return match ($this->owner_type) {
            'company' => 'Công ty',
            'business_household' => 'Hộ kinh doanh',
            'other' => 'Khác',
            default => 'Cá nhân',
        };
    }
}
