<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SystemBackup extends Model {
    protected $guarded = ['id'];
    protected $casts = ['manifest'=>'array','started_at'=>'datetime','completed_at'=>'datetime'];
}
