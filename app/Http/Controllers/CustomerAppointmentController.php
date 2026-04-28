<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerReminder;
use App\Models\Setting;
use App\Services\CustomerPriorityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) auth()->id();
        $search = trim((string) $request->input('q', ''));
        $selectedCustomer = null;

        $oldCustomerId = (int) old('customer_id', 0);
        if ($oldCustomerId > 0) {
            $selectedCustomer = Customer::query()
                ->where('id', $oldCustomerId)
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('assigned_to', $userId);
                })
                ->first(['id', 'name', 'phone']);
        }

        $appointmentsQuery = CustomerReminder::query()
            ->with(['customer:id,name,phone'])
            ->whereHas('customer', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            });

        if ($search !== '') {
            $appointmentsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('from_date')) {
            $appointmentsQuery->whereDate('remind_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $appointmentsQuery->whereDate('remind_at', '<=', $request->input('to_date'));
        }

        $appointments = $appointmentsQuery
            ->orderByDesc('remind_at')
            ->paginate(12)
            ->appends($request->query());

        return view('site.my_customer.appointments', [
            'appointments' => $appointments,
            'settings'     => Setting::all()->keyBy('key'),
            'search'       => $search,
            'selectedCustomer' => $selectedCustomer,
        ]);
    }

    /**
     * AJAX: search customers belonging to current user.
     */
    public function searchCustomers(Request $request)
    {
        $userId = (int) auth()->id();
        $q      = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 10;

        $customers = Customer::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'phone'])
            ->withQueryString();

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
            'links' => [
                'prev' => $customers->previousPageUrl(),
                'next' => $customers->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request, CustomerPriorityService $priorityService)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
            'note' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ]);

        $customer = Customer::findOrFail((int) $validated['customer_id']);
        $this->ensureManagedCustomer($customer);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('customer-appointments', 'public')
            : null;

        $reminder = CustomerReminder::create([
            'customer_id' => $customer->id,
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

        return back()->with('success', 'Đã thêm cuộc hẹn khách hàng.');
    }

    public function update(Request $request, CustomerReminder $reminder, CustomerPriorityService $priorityService)
    {
        $this->ensureManagedCustomer($reminder->customer);

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

        $reminder->update([
            'title' => $validated['title'],
            'remind_at' => $validated['remind_at'],
            'note' => $validated['note'] ?? null,
            'image_path' => $imagePath,
            'is_done' => (bool) ($validated['is_done'] ?? $reminder->is_done),
        ]);

        if (!$wasDone && $reminder->is_done && !$reminder->meeting_score_counted_at) {
            $priorityService->addCareAction(
                customer: $reminder->customer,
                saleId: (int) Auth::id(),
                actionType: 'meeting_done',
                note: 'Đã gặp khách từ lịch hẹn: ' . $reminder->title,
                score: 10,
                meta: ['reminder_id' => $reminder->id]
            );
            $reminder->update(['meeting_score_counted_at' => now()]);
        }

        return back()->with('success', 'Đã cập nhật cuộc hẹn.');
    }

    public function destroy(CustomerReminder $reminder)
    {
        $this->ensureManagedCustomer($reminder->customer);

        if (!empty($reminder->image_path)) {
            Storage::disk('public')->delete($reminder->image_path);
        }

        $reminder->delete();

        return back()->with('success', 'Đã xóa cuộc hẹn.');
    }

    private function ensureManagedCustomer(Customer $customer): void
    {
        $userId = (int) auth()->id();

        if ((int) $customer->user_id !== $userId && (int) $customer->assigned_to !== $userId) {
            abort(403, 'Bạn không có quyền thao tác với khách hàng này.');
        }
    }
}
