<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'website',
        'gender',
        'dob',
        'customer_type_id',
        'note',
        'delivery_time',
        'foam_box_required',
        'foam_box_price',
        'use_truck_station',
        'truck_station_address',
        'truck_receive_time',
        'truck_return_time',
        'truck_return_address',
        'truck_invoice_image',
        'truck_delivery_image',
        'truck_station_phone',
        'truck_fee',
        'assigned_to',
    ];

    protected $dates = ['dob'];

    protected $casts = [
        'dob' => 'date',
    ];

    public function transactions() {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Quan hệ: Customer thuộc một loại khách hàng
     */
    public function type()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    /**
     * Quan hệ: Customer có nhiều địa chỉ
     */
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Quan hệ: Customer được assign cho một user
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}