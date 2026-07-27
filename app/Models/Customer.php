<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// ...existing code...
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            $customer->name = trim((string) $customer->name);
            $customer->name_normalized = static::normalizeName($customer->name);
        });
    }

    public static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        if (class_exists(\Normalizer::class)) {
            $name = \Normalizer::normalize($name, \Normalizer::FORM_C) ?: $name;
        }

        return mb_strtolower($name, 'UTF-8');
    }

    /**
     * Quan hệ: Customer thuộc một tuyến vận chuyển
     */
    public function truckRoute()
    {
        return $this->belongsTo(TruckRoute::class, 'truck_route_id');
    }

    /**
     * Get a truck route that passes through this customer's truck station (fallback accessor)
     */
    public function getTruckRouteByStationAttribute()
    {
        if (!$this->truck_station_id) {
            return null;
        }

        // Try to find an active route that has a stop at this station
        return TruckRoute::query()
            ->where('is_active', true)
            ->whereHas('stops', function ($q) {
                $q->where('truck_station_id', $this->truck_station_id);
            })
            ->with(['brand', 'stops.station'])
            ->first();
    }

    public function reminders()
    {
        return $this->hasMany(CustomerReminder::class);
    }

    public function shippingFeeHistories()
    {
        return $this->hasMany(CustomerShippingFeeHistory::class);
    }

    public function latestShippingFeeHistory()
    {
        return $this->hasOne(CustomerShippingFeeHistory::class)->latestOfMany();
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
        'legacy_code',
        'foam_box_required',
        'foam_box_price',
        'use_truck_station',
        'truck_station_id',
        'truck_route_id',
        'truck_station_address',
        'truck_receive_time',
        'truck_return_time',
        'truck_return_address',
        'truck_invoice_image',
        'truck_delivery_image',
        'truck_station_phone',
        'truck_fee',
        'shipping_fee',
        'assigned_to',
        'current_owner_sale_id',
        'default_shipper_id',
        'assigned_at',
        'status',
        'customer_status',
        'debt_type',
        'free_from_date',
        'current_cycle_no',
        'commission_percent',
        'is_employee',
        'customer_card_codes',
        'is_pinned',
        'sort_order',
        'deleted_by',
    ];

    protected $dates = ['dob'];

    protected $casts = [
        'dob' => 'date',
        'next_appointment' => 'datetime',
        'assigned_at' => 'datetime',
        'free_from_date' => 'datetime',
        'commission_percent' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'is_employee' => 'boolean',
        'customer_card_codes' => 'array',
        'is_pinned' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function freeCustomerDays(): int
    {
        return max((int) Setting::get('free_customer_days', Setting::get('customer_free_days', 0)), 0);
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

        // A fresh assignment/takeover starts a new care window even when the
        // customer's latest order is already older than the configured limit.
        // Customers with no orders keep the existing no-expiry behavior.
        $lastOrderAt = $this->_lastOrderDate();
        if (!$lastOrderAt) {
            return null;
        }

        $activityAt = $lastOrderAt;
        if ($this->assigned_at && $this->assigned_at->gt($activityAt)) {
            $activityAt = $this->assigned_at;
        }

        return $activityAt->copy()->addDays($days);
    }

    public function isFree(): bool
    {
        if ($this->is_employee) {
            return false;
        }

        if ((string) $this->customer_status === 'free') {
            return true;
        }

        if (!$this->assigned_to) {
            return true;
        }

        $expiresAt = $this->assignmentExpiresAt();

        return $expiresAt ? $expiresAt->lte(now()) : false;
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
                $builder->where('customer_status', 'free')
                    ->orWhereNull('assigned_to');

                if ($days > 0) {
                    $threshold = now()->subDays($days);
                    // Free only when both the latest order and latest assignment
                    // are older than the configured care window.
                    $builder->orWhereRaw(
                        '(SELECT MAX(created_at) FROM orders WHERE orders.customer_id = customers.id) <= ?
                        AND (customers.assigned_at IS NULL OR customers.assigned_at <= ?)',
                        [$threshold, $threshold]
                    );
                }
            });
    }

    public function scopeManaged(Builder $query): Builder
    {
        $days = static::freeCustomerDays();

        $query->where('is_employee', false)
            ->where('customer_status', '!=', 'free')
            ->whereNotNull('assigned_to');

        if ($days > 0) {
            $threshold = now()->subDays($days);
            // Managed if there are no orders, or either the latest order or
            // latest assignment is still inside the configured care window.
            $query->where(function (Builder $q) use ($threshold) {
                $q->whereRaw(
                    '(SELECT MAX(created_at) FROM orders WHERE orders.customer_id = customers.id) IS NULL'
                )->orWhereRaw(
                    '(SELECT MAX(created_at) FROM orders WHERE orders.customer_id = customers.id) > ?',
                    [$threshold]
                )->orWhere('assigned_at', '>', $threshold);
            });
        }

        return $query;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user) {
            $builder->where('assigned_to', $user->id)
                ->orWhere('current_owner_sale_id', $user->id);
        })->managed();
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

    public function currentOwner()
    {
        return $this->belongsTo(User::class, 'current_owner_sale_id');
    }

    public function defaultShipper()
    {
        return $this->belongsTo(User::class, 'default_shipper_id');
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

    public function priorities()
    {
        return $this->hasMany(CustomerPriority::class);
    }

    public function ownershipHistories()
    {
        return $this->hasMany(CustomerOwnershipHistory::class);
    }

    public function truckStation()
    {
        return $this->belongsTo(TruckStation::class);
    }
}
