<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        echo "<pre>";
        print_r($user->role);
        echo "</pre>";
        $roleName = $user->role->name ?? $user->role ?? 'default';
        echo  $roleName ;
        // Điều hướng view dựa trên role
        return match ($roleName) {
            'admin'   => view('dashboard.admin', compact('user')),
            'manager' => view('dashboard.manager', compact('user')),
            'staff'   => view('dashboard.staff', compact('user')),
            default   => view('dashboard.default', compact('user')),
        };
    }
}
