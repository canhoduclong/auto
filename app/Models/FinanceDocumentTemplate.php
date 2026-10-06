<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinanceDocumentTemplate extends Model {
    protected $fillable = ['name','form_type','flow_direction','is_active'];
    protected $casts = ['is_active'=>'boolean'];
}
