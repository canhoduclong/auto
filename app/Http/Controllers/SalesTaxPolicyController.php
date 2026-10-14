<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Setting;
use App\Models\UserPolicyAcceptance;
use App\Support\SalesTaxPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SalesTaxPolicyController extends Controller
{
    public function show(Request $request): View
    {
        $settings = Cache::remember('settings', 60, fn () => Setting::all()->keyBy('key'));
        $logoId = $settings->get('logo')?->value;

        return view('site.sales-tax-policy', [
            'settings' => $settings,
            'logoMedia' => $logoId ? Media::find($logoId) : null,
            'accepted' => SalesTaxPolicy::isAccepted($request->user()),
            'policyEnabled' => SalesTaxPolicy::enabled(),
            'policyTitle' => SalesTaxPolicy::title(),
            'policyVersion' => SalesTaxPolicy::version(),
            'policyContent' => SalesTaxPolicy::content(),
        ]);
    }

    public function accept(Request $request): RedirectResponse
    {
        abort_unless(SalesTaxPolicy::appliesTo($request->user()), 403);
        $request->validate([
            'commitments' => ['required', 'array:read,understand,integrity,audit,feedback,comply'],
            'commitments.read' => ['accepted'],
            'commitments.understand' => ['accepted'],
            'commitments.integrity' => ['accepted'],
            'commitments.audit' => ['accepted'],
            'commitments.feedback' => ['accepted'],
            'commitments.comply' => ['accepted'],
        ], [
            'commitments.required' => 'Bạn cần xác nhận đầy đủ từng nội dung cam kết.',
            'commitments.*.accepted' => 'Bạn cần xác nhận đầy đủ từng nội dung cam kết.',
        ]);
        UserPolicyAcceptance::updateOrCreate([
            'user_id' => $request->user()->id,
            'policy_key' => config('sales_tax_policy.key'),
            'policy_version' => SalesTaxPolicy::version(),
        ], [
            'accepted_at' => now(),
            'revoked_at' => null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ]);
        return redirect()->intended(route('pages.my_orders.monitoring'))->with('success', 'Bạn đã xác nhận quy định nghiệp vụ nội bộ.');
    }

    public function revoke(Request $request): RedirectResponse
    {
        $acceptance = UserPolicyAcceptance::query()
            ->where('user_id', $request->user()->id)
            ->where('policy_key', config('sales_tax_policy.key'))
            ->where('policy_version', SalesTaxPolicy::version())
            ->whereNull('revoked_at')
            ->firstOrFail();

        $acceptance->update(['revoked_at' => now()]);

        return redirect()->route('sales-tax-policy.show')
            ->with('success', 'Bạn đã hủy xác nhận. Tài khoản hiện ở trạng thái chưa xác nhận.');
    }
}
