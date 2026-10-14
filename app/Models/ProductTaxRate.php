<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductTaxRate extends Model {
 protected $guarded=['id'];
 protected $casts=['tax_percent'=>'decimal:2','effective_from'=>'date','approved_at'=>'datetime'];
 public function creator(){return $this->belongsTo(User::class,'created_by');}
 public function approver(){return $this->belongsTo(User::class,'approved_by');}
}
