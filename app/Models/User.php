<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Customer;

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
        return $this->roles->contains('name', $role);
    }
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
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