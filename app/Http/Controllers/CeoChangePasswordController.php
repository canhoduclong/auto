<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CeoChangePasswordController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        return view('ceo.change-password', ['user' => $user]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }
        $user->password = Hash::make($request->input('password'));
        $user->save();
        return redirect()->route('ceo.profile')->with('success', 'Đổi mật khẩu thành công!');
    }
}
