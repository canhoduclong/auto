<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Services\CustomerPriorityService;
use Illuminate\Support\Facades\Auth;

class MyCustomerController extends Controller
{
    public function refreshPriority(Request $request, CustomerPriorityService $priorityService)
    {
        $userId = Auth::id();
        $customers = Customer::query()
            ->where('assigned_to', $userId)
            ->where('customer_status', 'active')
            ->get();

        foreach ($customers as $customer) {
            $priorityService->attachSale($customer, $userId, 1, 'refresh');
        }

        return response()->json(['success' => true, 'message' => 'Đã làm mới priority cho khách đang chăm sóc.']);
    }
}
