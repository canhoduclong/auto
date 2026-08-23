<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Services\CustomerPriorityService;

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

    public function store(Request $request, CustomerPriorityService $priorityService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'priority_level' => 'nullable|in:2,3',
            'takeover' => 'nullable|boolean',
        ]);
        $phoneDigits = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));

        $duplicateCustomer = Customer::query()
            ->where(function ($query) use ($data, $phoneDigits) {
                $query->where('name_normalized', Customer::normalizeName($data['name']));

                if (!empty($data['email'])) {
                    $query->orWhereRaw('LOWER(email) = ?', [strtolower($data['email'])]);
                }

                if (!empty($data['phone'])) {
                    $query->orWhere('phone', $data['phone']);
                    if ($phoneDigits !== '') {
                        $query->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '.', ''), '-', ''), '+', '') = ?", [$phoneDigits]);
                    }
                    $query->orWhere(function ($subQuery) use ($data) {
                        $subQuery->where('name', $data['name'])
                            ->where('phone', $data['phone']);
                    });
                }
            })
            ->first();

        if ($duplicateCustomer) {
            $userId = (int) auth()->id();
            if ($userId > 0) {
                if ($request->boolean('takeover') || $duplicateCustomer->isFree()) {
                    $priorityService->takeover($duplicateCustomer, $userId, $request->boolean('takeover') ? 'takeover' : 'free_customer');
                } else {
                    $priorityService->attachSale($duplicateCustomer, $userId, isset($data['priority_level']) ? (int) $data['priority_level'] : 3, 'duplicate_join');
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Khach hang da ton tai, da cap nhat danh sach priority.',
                'customer' => $duplicateCustomer,
            ]);
        }

        $userId = (int) auth()->id();
        $data['assigned_to'] = $userId ?: null;
        $data['assigned_at'] = $userId ? now() : null;
        $data['current_owner_sale_id'] = $userId ?: null;
        $data['customer_status'] = $userId ? 'active' : 'free';
        $data['current_cycle_no'] = 1;
        $data['free_from_date'] = $userId ? null : now();


        $customer = Customer::create($data);
        // Luôn gán priority = 1 cho user hiện tại nếu có assigned_to (khách mới hoặc khách tự do)
        $targetUserId = $customer->assigned_to ?: ($userId ?: null);
        if ($targetUserId) {
            $priorityService->attachSale($customer, (int) $targetUserId, 1, 'created');
        } else {
            $priorityService->ensureLifecycle($customer);
        }

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }
}
