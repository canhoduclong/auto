<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'layout_web_name', 'layout_web_slug', 'layout_mobile_name', 'layout_mobile_slug'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    
    //public function permissions()
    //{
    //    return $this->belongsToMany(Permission::class);
    //} 
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    public function taskPermissions()
    {
        return $this->hasMany(TaskPermission::class);
    }
}
