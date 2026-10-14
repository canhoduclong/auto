<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductTaxRate extends Model {
 public const ENTITY_TYPES=['household'=>'Hộ kinh doanh','company'=>'Công ty','other'=>'Khác'];
 public const ENTITY_COLUMNS=['household'=>'household_tax_percent','company'=>'company_tax_percent','other'=>'tax_percent'];
 protected $guarded=['id'];
 protected $casts=['tax_percent'=>'decimal:2','effective_from'=>'date','approved_at'=>'datetime'];
 public function creator(){return $this->belongsTo(User::class,'created_by');}
 public function approver(){return $this->belongsTo(User::class,'approved_by');}
}
