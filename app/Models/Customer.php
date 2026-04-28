<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public function reminders()
    {
        return $this->hasMany(CustomerReminder::class);
    }

    // Placeholder for care logs relationship (to be implemented if care log model/table exists)
    public function careLogs()
    {
        // Temporary: if CustomerCareLog does not exist, create a stub model or use a fallback
        return $this->hasMany(\App\Models\CustomerCareLog::class);
    }

    public function latestCareLog()
    {
        return $this->hasOne(\App\Models\CustomerCareLog::class)->latestOfMany();
    }
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'address',
        'website',
        'gender',
        'dob',
        'customer_type_id',
        'note',
        'next_appointment',
        'delivery_time',
        'size',
        'production',
        'brand',
        'company_name',
        'tax_code',
        'company_address',
        'company_email',
        'customer_code',
        'foam_box_required',
        'foam_box_price',
        'use_truck_station',
        'truck_station_id',
        'truck_station_address',
        'truck_receive_time',
        'truck_return_time',
        'truck_return_address',
        'truck_invoice_image',
        'truck_delivery_image',
        'truck_station_phone',
        'truck_fee',
        'assigned_to',
        'assigned_at',
        'status',
        'is_employee',
        'deleted_by',
    ];

    protected $dates = ['dob'];

    protected $casts = [
        'dob' => 'date',
        'next_appointment' => 'datetime',
        'assigned_at' => 'datetime',
        'is_employee' => 'boolean',
    ];

    public static function freeCustomerDays(): int
    {
        return max((int) Setting::get('customer_free_days', 0), 0);
    }

    public function assignmentExpiresAt()
    {
        if ($this->is_employee) {
            return null;
        }

        $days = static::freeCustomerDays();
        if ($days <= 0) {
            return null;
        }

        // Expiry is based on last order date; no orders = no expiry
        $lastOrderAt = $this->_lastOrderDate();
        if (!$lastOrderAt) {
            return null;
        }

        return $lastOrderAt->copy()->addDays($days);
    }

    public function isFree(): bool
    {
        if ($this->is_employee) {
            return false;
        }

        if (!$this->assigned_to) {
            return true;
        }

        $days = static::freeCustomerDays();
        if ($days <= 0) {
            return false;
        }

        // Customers with no orders are never considered free by time alone
        $lastOrderAt = $this->_lastOrderDate();
        if (!$lastOrderAt) {
            return false;
        }

        return $lastOrderAt->lte(now()->subDays($days));
    }

    /**
     * Get last order created_at from eager-loaded relation or DB query.
     */
    private function _lastOrderDate(): ?\Carbon\Carbon
    {
        if ($this->relationLoaded('lastOrder')) {
            return $this->lastOrder?->created_at;
        }
        $raw = $this->orders()->max('created_at');
        return $raw ? \Carbon\Carbon::parse($raw) : null;
    }

    public function isManagedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->is_employee) {
            return false;
        }

        return (int) $this->assigned_to === (int) $user->id && !$this->isFree();
    }

    public function scopeFree(Builder $query): Builder
    {
        $days = static::freeCustomerDays();

        return $query->where('is_employee', false)
            ->where(function (Builder $builder) use ($days) {
                $builder->whereNull('assigned_to');

                if ($days > 0) {
                    $threshold = now()->subDays($days);
                    // Free if has orders AND last order is older than threshold
                    $builder->orWhereRaw(
                        '(SELECT MAX(created_at) FROM orders WHERE orders.customer_id = customers.id) <= ?',
                        [$threshold]
                    );
                }
            });
    }

    public function scopeManaged(Builder $query): Builder
    {
        $days = static::freeCustomerDays();

        $query->where('is_employee', false)
            ->whereNotNull('assigned_to');

        if ($days > 0) {
            $threshold = now()->subDays($days);
            // Managed if: no orders yet, OR last order is within threshold
            $query->where(function (Builder $q) use ($threshold) {
                $q->whereRaw(
                    '(SELECT MAX(created_at) FROM orders WHERE orders.customer_id = customers.id) IS NULL'
                )->orWhereRaw(
                    '(SELECT MAX(created_at) FROM orders WHERE orders.customer_id = customers.id) > ?',
                    [$threshold]
                );
            });
        }

        return $query;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('assigned_to', $user->id)->managed();
    }

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

    public function lastOrder()
    {
        return $this->hasOne(Order::class)->latestOfMany('created_at');
    }

    public function truckStation()
    {
        return $this->belongsTo(TruckStation::class);
    }
}