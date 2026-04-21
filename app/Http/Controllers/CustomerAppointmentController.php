<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerReminder;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) auth()->id();
        $search = trim((string) $request->input('q', ''));

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
        ]);
    }

    /**
     * AJAX: search customers belonging to current user.
     */
    public function searchCustomers(Request $request)
    {
        $userId = (int) auth()->id();
        $q      = trim((string) $request->input('q', ''));

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
            ->limit(30)
            ->get(['id', 'name', 'phone']);

        return response()->json($customers);
    }

    public function store(Request $request)
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

        CustomerReminder::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'remind_at' => $validated['remind_at'],
            'note' => $validated['note'] ?? null,
            'image_path' => $imagePath,
        ]);

        return back()->with('success', 'Đã thêm cuộc hẹn khách hàng.');
    }

    public function update(Request $request, CustomerReminder $reminder)
    {
        $this->ensureManagedCustomer($reminder->customer);

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

        $reminder->update([
            'title' => $validated['title'],
            'remind_at' => $validated['remind_at'],
            'note' => $validated['note'] ?? null,
            'image_path' => $imagePath,
        ]);

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
