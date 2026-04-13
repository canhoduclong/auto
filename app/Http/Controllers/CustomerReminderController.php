<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class CustomerReminderController extends Controller
{

    public function update(Customer $customer, CustomerReminder $reminder, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
            'note' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'remove_image' => 'nullable|boolean',
        ]);

        $imagePath = $reminder->image_path;
        if ($request->boolean('remove_image') && !empty($imagePath)) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('image')) {
            if (!empty($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('customer-appointments', 'public');
        }

        $validated['image_path'] = $imagePath;
        $reminder->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã cập nhật cuộc hẹn thành công!']);
        }

        return redirect()->route('my_customer.show', $customer)->with('success', 'Đã cập nhật nhắc nhở thành công!');
    }

    public function destroy(Customer $customer, CustomerReminder $reminder)
    {
        if (!empty($reminder->image_path)) {
            Storage::disk('public')->delete($reminder->image_path);
        }
        $reminder->delete();
        return redirect()->route('my_customer.show', $customer)->with('success', 'Đã xóa nhắc nhở thành công!');
    }
    public function store(Customer $customer, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
            'note' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('customer-appointments', 'public')
            : null;

        $reminder = $customer->reminders()->create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'remind_at' => $validated['remind_at'],
            'note' => $validated['note'] ?? null,
            'image_path' => $imagePath,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã thêm nhắc nhở thành công!']);
        }

        return redirect()->route('my_customer.show', $customer)->with('success', 'Đã thêm nhắc nhở thành công!');
    }
}
