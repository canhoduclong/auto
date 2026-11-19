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
        if ($request->has('fixed_debt')) {
            $fixedDebt = trim((string) $request->input('fixed_debt', ''));
            if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $fixedDebt)) {
                $fixedDebt = str_replace('.', '', $fixedDebt);
            }
            $request->merge(['fixed_debt' => $fixedDebt === '' ? null : $fixedDebt]);
        }
        $data = $request->validate([
            'fixed_debt' => 'nullable|integer|min:0|max:999999999999999',
            'threshold_days' => 'nullable|integer|min:0|max:3650',
            'allowed_orders' => 'nullable|integer|min:0|max:100000', 'credit_limit' => 'nullable|integer|min:0|max:999999999999999',
            'due_days' => 'nullable|integer|min:0|max:3650', 'policy_version' => 'required|integer|min:0',
        ] + CustomerDebtPolicyService::cycleRules());
        if (isset($data['payment_mode']) && ! $request->boolean('return_to_report')) {
            if (! array_key_exists('fixed_debt', $data)) {
                $data['allowed_orders'] = 1;
            }
            $data['rolling_only'] = $data['payment_mode'] === 'default';
        }
        $group = ['customer_id' => $customer->id];
        $policies->save($group, $data, $request->user()->id, $request->user()->isAdmin(), (int) $data['policy_version'], $request->user());

        $message = 'Đã cập nhật và khóa tiêu chí công nợ.';
        if ($request->boolean('return_to_report') && ! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        return response()->json(['message' => $message, 'policy' => $policies->effective($group, $policies->policies())]);
    }

    public function unlock(Request $request, Customer $customer, CustomerDebtPolicyService $policies)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $group = ['customer_id' => $customer->id];
        $data = $request->validate(['policy_version' => 'required|integer|min:0']);
        $policies->unlock($group, $request->user(), (int) $data['policy_version']);

        $message = 'Đã mở khóa. Nhân viên được lưu thêm một lần rồi tự khóa lại.';
        if ($request->boolean('return_to_report') && ! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        return response()->json(['message' => $message, 'policy' => $policies->effective($group, $policies->policies())]);
    }
}
