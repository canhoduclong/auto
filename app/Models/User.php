<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Customer;
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
        'password',
        'avatar',
        'warehouse_id',
        'team_id',
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
            'password' => 'hashed',
        ];
    }

    // có nhiều vai trò
    public function roles()
    {
        return $this->belongsToMany(Role::class);
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

}