<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerDebtPolicyService;
use Illuminate\Http\Request;

class CustomerDebtPolicyController extends Controller
{
    public function update(Request $request, Customer $customer, CustomerDebtPolicyService $policies)
    {
        abort_unless($policies->canEditCustomer($request->user(), $customer), 403);
        $limit = trim((string) $request->input('credit_limit', ''));
        if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $limit)) {
            $limit = str_replace('.', '', $limit);
        }
        $request->merge(['credit_limit' => $limit === '' ? null : $limit]);
        $data = $request->validate([
            'allowed_orders' => 'nullable|integer|min:0|max:100000', 'credit_limit' => 'nullable|integer|min:0|max:999999999999999',
            'due_days' => 'nullable|integer|min:0|max:3650', 'policy_version' => 'required|integer|min:0',
        ]);
        $group = ['customer_id' => $customer->id];
        $policies->save($group, $data, $request->user()->id, $request->user()->isAdmin(), (int) $data['policy_version'], $request->user());

        return response()->json(['message' => 'Đã cập nhật và khóa tiêu chí công nợ.', 'policy' => $policies->effective($group, $policies->policies())]);
    }

    public function unlock(Request $request, Customer $customer, CustomerDebtPolicyService $policies)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $group = ['customer_id' => $customer->id];
        $policies->unlock($group, $request->user());

        return response()->json(['message' => 'Đã mở khóa. Nhân viên được lưu thêm một lần rồi tự khóa lại.', 'policy' => $policies->effective($group, $policies->policies())]);
    }
}
