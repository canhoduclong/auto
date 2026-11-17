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
            'payment_mode' => 'nullable|in:default,cycle',
            'cycle_type' => 'nullable|required_if:payment_mode,cycle|in:weekly,monthly',
            'cycle_close_weekday' => 'nullable|required_if:cycle_type,weekly|integer|min:1|max:7',
            'cycle_weekday' => 'nullable|required_if:cycle_type,weekly|integer|min:1|max:7',
            'cycle_close_monthday' => 'nullable|required_if:cycle_type,monthly|integer|min:1|max:31',
            'cycle_monthday' => 'nullable|required_if:cycle_type,monthly|integer|min:1|max:31',
            'due_days' => 'nullable|integer|min:0|max:3650', 'policy_version' => 'required|integer|min:0',
        ]);
        if (isset($data['payment_mode'])) {
            $data['allowed_orders'] = 1;
            $data['rolling_only'] = $data['payment_mode'] === 'default';
        }
        $group = ['customer_id' => $customer->id];
        $policies->save($group, $data, $request->user()->id, $request->user()->isAdmin(), (int) $data['policy_version'], $request->user());

        return response()->json(['message' => 'Đã cập nhật và khóa tiêu chí công nợ.', 'policy' => $policies->effective($group, $policies->policies())]);
    }

    public function unlock(Request $request, Customer $customer, CustomerDebtPolicyService $policies)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $group = ['customer_id' => $customer->id];
        $data = $request->validate(['policy_version' => 'required|integer|min:0']);
        $policies->unlock($group, $request->user(), (int) $data['policy_version']);

        return response()->json(['message' => 'Đã mở khóa. Nhân viên được lưu thêm một lần rồi tự khóa lại.', 'policy' => $policies->effective($group, $policies->policies())]);
    }
}
