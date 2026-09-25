<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Province;
use App\Models\Ward;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        // Dạng căn hộ/chung cư
        'project_name',   // tên dự án
        'zone',           // phân khu
        'block',          // block/tòa
        'floor',          // tầng
        'unit_number',    // số căn hộ

        // Dạng nhà phố
        'house_number',   // số nhà
        'temporary_number', // số nhà tạm (nếu có)
        'street',

        // Chung
        'ward',
        'district',
        'city',
        'province_id',
        'ward_id',
        'is_default',
        'note',           // ghi chú thêm
    ];

    /**
     * Quan hệ: địa chỉ thuộc về một khách hàng
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function wardModel()
    {
        return $this->belongsTo(Ward::class, 'ward_id');
    }
}
