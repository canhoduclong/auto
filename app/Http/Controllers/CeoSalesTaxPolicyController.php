<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserPolicyAcceptance;
use App\Support\SalesTaxPolicy;
use App\Support\TaskDescription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CeoSalesTaxPolicyController extends Controller
{
    public function edit(): View
    {
        $roles = config('sales_tax_policy.roles', []);
        $version = SalesTaxPolicy::version();
        $acceptances = UserPolicyAcceptance::query()
            ->with(['user.roles'])
            ->where('policy_key', config('sales_tax_policy.key'))
            ->where('policy_version', $version)
            ->whereNull('revoked_at')
            ->latest('accepted_at')
            ->paginate(20);
        $eligibleUsers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->count();
        $acceptedUsers = UserPolicyAcceptance::query()
            ->where('policy_key', config('sales_tax_policy.key'))
            ->where('policy_version', $version)
            ->whereNull('revoked_at')
            ->distinct()->count('user_id');

        return view('ceo.sales-tax-policy.edit', compact(
            'acceptances', 'eligibleUsers', 'acceptedUsers', 'version'
        ) + [
            'policyEnabled' => SalesTaxPolicy::enabled(),
            'policyTitle' => SalesTaxPolicy::title(),
            'policyContent' => SalesTaxPolicy::content(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:15000'],
            'enabled' => ['nullable', 'boolean'],
        ]);
        $content = (string) TaskDescription::sanitize($validated['content']);

        if (trim(strip_tags($content)) === '') {
            return back()->withErrors(['content' => 'Nội dung quy định không được để trống.'])->withInput();
        }

        $currentContent = SalesTaxPolicy::content();
        $currentTitle = SalesTaxPolicy::title();
        $currentVersion = SalesTaxPolicy::version();
        $normalizedCurrentContent = (string) TaskDescription::sanitize($currentContent);
        $title = trim($validated['title']);
        $policyChanged = trim($content) !== trim($normalizedCurrentContent) || $title !== trim($currentTitle);
        DB::transaction(function () use ($request, $content, $title, $policyChanged, $currentContent, $currentTitle, $currentVersion): void {
            Setting::set(SalesTaxPolicy::ENABLED_SETTING, $request->boolean('enabled') ? '1' : '0');
            Setting::set(SalesTaxPolicy::TITLE_SETTING, $title);
            Setting::set(SalesTaxPolicy::CONTENT_SETTING, $content);
            if ($policyChanged) {
                $newVersion = 'v'.now()->format('Ymd-His-v');
                Setting::set(SalesTaxPolicy::CONTENT_ARCHIVE_PREFIX.$currentVersion, $currentContent);
                Setting::set(SalesTaxPolicy::TITLE_ARCHIVE_PREFIX.$currentVersion, $currentTitle);
                Setting::set(SalesTaxPolicy::CONTENT_ARCHIVE_PREFIX.$newVersion, $content);
                Setting::set(SalesTaxPolicy::TITLE_ARCHIVE_PREFIX.$newVersion, $title);
                Setting::set(SalesTaxPolicy::VERSION_SETTING, $newVersion);
            }
        });

        return redirect()->route('ceo.sales-tax-policy.edit')->with(
            'success',
            $policyChanged
                ? 'Đã cập nhật quy định và tạo phiên bản mới. Các tài khoản áp dụng cần xác nhận lại.'
                : 'Đã cập nhật trạng thái yêu cầu xác nhận.'
        );
    }
}
