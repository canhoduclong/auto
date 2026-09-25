<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Validation\Rule;

class CeoPageController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $customer = Customer::where('user_id', $user->id)->first();
        if (!$customer) {
            $customer = Customer::create([
                'user_id' => $user->id,
                'assigned_to' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }
        return view('ceo.profile', [
            'user' => $user,
            'customer' => $customer
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        $customer = $user->customer;
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'note' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
        ]);
        $payload = $request->only(['name', 'email', 'phone', 'dob', 'gender', 'note']);
        $customer?->update($payload);
        $user->name = $request->input('name');
        if ($request->filled('email')) {
            $user->email = $request->input('email');
        }
        $user->save();
        if ($request->hasFile('avatar')) {
            $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('avatars'), $avatarName);
            $user->update(['avatar' => 'avatars/' . $avatarName]);
        }
        return redirect()->route('ceo.profile')->with('success', 'Profile updated successfully.');
    }
}
