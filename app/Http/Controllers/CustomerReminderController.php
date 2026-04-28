<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerReminder;
use App\Services\CustomerPriorityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class CustomerReminderController extends Controller
{

    public function update(Customer $customer, CustomerReminder $reminder, Request $request, CustomerPriorityService $priorityService)
    {
        $wasDone = (bool) $reminder->is_done;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
            'note' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'remove_image' => 'nullable|boolean',
            'is_done' => 'nullable|boolean',
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
        $validated['is_done'] = (bool) ($validated['is_done'] ?? $reminder->is_done);
        $reminder->update($validated);

        if (!$wasDone && $reminder->is_done && !$reminder->meeting_score_counted_at) {
            $priorityService->addCareAction(
                customer: $customer,
                saleId: (int) Auth::id(),
                actionType: 'meeting_done',
                note: 'Đã gặp khách từ lịch hẹn: ' . $reminder->title,
                score: 10,
                meta: ['reminder_id' => $reminder->id]
            );
            $reminder->update(['meeting_score_counted_at' => now()]);
        }

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
    public function store(Customer $customer, Request $request, CustomerPriorityService $priorityService)
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

        if (!$reminder->appointment_score_counted_at) {
            $priorityService->addCareAction(
                customer: $customer,
                saleId: (int) Auth::id(),
                actionType: 'appointment_set',
                note: 'Đặt lịch hẹn: ' . $validated['title'],
                score: 10,
                meta: ['reminder_id' => $reminder->id]
            );
            $reminder->update(['appointment_score_counted_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã thêm nhắc nhở thành công!']);
        }

        return redirect()->route('my_customer.show', $customer)->with('success', 'Đã thêm nhắc nhở thành công!');
    }
}
