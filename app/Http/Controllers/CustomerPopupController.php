<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerPopupController extends Controller
{
    public function search(Request $request)
    {
        $query = Customer::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%'.$request->phone.'%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }
        $customers = $query->orderByDesc('id')->paginate(10);
        return response()->json([
            'html' => view('customers.popup_list', compact('customers'))->render()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
        ]);
        $customer = Customer::create($data);
        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }
}
