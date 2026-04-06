<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CustomerReminderController extends Controller
{

    public function update(Customer $customer, CustomerReminder $reminder, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
            'note' => 'nullable|string',
        ]);
        $reminder->update($validated);
        return redirect()->route('my_customer.show', $customer)->with('success', 'Đã cập nhật nhắc nhở thành công!');
    }

    public function destroy(Customer $customer, CustomerReminder $reminder)
    {
        $reminder->delete();
        return redirect()->route('my_customer.show', $customer)->with('success', 'Đã xóa nhắc nhở thành công!');
    }
    public function store(Customer $customer, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $reminder = $customer->reminders()->create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'remind_at' => $validated['remind_at'],
            'note' => $validated['note'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã thêm nhắc nhở thành công!']);
        }

        return redirect()->route('my_customer.show', $customer)->with('success', 'Đã thêm nhắc nhở thành công!');
    }
}
