<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttributeValue extends Model
{
    use HasFactory;
    protected $table = 'product_attribute_values';

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

}