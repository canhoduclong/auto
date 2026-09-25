<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPurchaseTemplate extends Model
{
    protected $fillable = ['supplier_id', 'user_id', 'name'];
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(ProcurementPurchaseTemplateItem::class); }
}
