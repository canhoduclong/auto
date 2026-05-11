<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionCategory extends Model
{
    protected $fillable = ['code', 'name', 'flow_direction', 'sort_order', 'is_active', 'created_by'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'transaction_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function flowDirectionLabel(): string
    {
        return $this->flow_direction === 'in' ? 'Thu vào tài khoản' : 'Chi từ tài khoản';
    }
}
