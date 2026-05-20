<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Customer;
use App\Models\MobileApiToken;
use App\Models\Team;
use App\Models\Warehouse;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'google_id',
        'google_avatar',
        'email_verified_at',
        'warehouse_id',
        'team_id',
        'block_id',
        'department_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // có nhiều vai trò
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function mobileApiTokens()
    {
        return $this->hasMany(MobileApiToken::class);
    }
    // có một vai trò
    public function hasRole($role)
    {
        return $this->roles->contains(function ($assignedRole) use ($role) {
            return strcasecmp((string) $assignedRole->name, (string) $role) === 0;
        });
    }
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function block()
    {
        return $this->belongsTo(\App\Models\Block::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

     //die( $permissionName);
    //$roles = $this->roles()->with('permissions')->get();
    //dd($roles->toArray()); // xem role nào đang có quyền gì
    // Kiểm tra xem người dùng có vai trò nào được gán quyền đó không
    
    public function hasPermission($permissionName)
    {
        return $this->roles()->whereHas('permissions', function($query) use ($permissionName) {
            $query->where('name', $permissionName);
        })->exists();
        
    } 
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isSalesFlowRole(): bool
    {
        return $this->hasRole('sale')
            || $this->hasRole('leader')
            || $this->hasRole('leader_sale')
            || $this->hasRole('sale_manager')
            || $this->hasRole('manager')
            || $this->hasRole('manager_sale');
    }

    public function canAccessSalesDailyFeatures(): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        $dynamicPermissions = [
            'work-reports.index',
            'pages.my_orders.daily_prices',
            'pages.my_orders.daily_inventories',
        ];

        foreach ($dynamicPermissions as $permissionName) {
            if ($this->hasPermission($permissionName)) {
                return true;
            }
        }

        // Backward compatibility for legacy role-only assignments.
        return $this->isSalesFlowRole();
    }

}