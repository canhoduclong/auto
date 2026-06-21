<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuckFarmReview extends Model
{
    protected $fillable = ['duck_farm_id', 'user_id', 'rating', 'comment'];
    public function farm() { return $this->belongsTo(DuckFarm::class, 'duck_farm_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
