<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserPolicyAcceptance;

class SalesTaxPolicy
{
    public const ENABLED_SETTING = 'sales_tax_policy_enabled';
    public const TITLE_SETTING = 'sales_tax_policy_title';
    public const VERSION_SETTING = 'sales_tax_policy_version';
    public const CONTENT_SETTING = 'sales_tax_policy_content';
    public const CONTENT_ARCHIVE_PREFIX = 'sales_tax_policy_content_version_';
    public const TITLE_ARCHIVE_PREFIX = 'sales_tax_policy_title_version_';

    public const DEFAULT_TITLE = 'QUY ĐỊNH SỬ DỤNG HỆ THỐNG VÀ CAM KẾT TRÁCH NHIỆM.';

    public static function enabled(): bool
    {
        return Setting::enabled(self::ENABLED_SETTING, true);
    }

    public static function version(): string
    {
        return (string) Setting::get(self::VERSION_SETTING, config('sales_tax_policy.version'));
    }

    public static function title(): string
    {
        return (string) Setting::get(self::TITLE_SETTING, self::DEFAULT_TITLE);
    }

    public static function content(): string
    {
        $content = Setting::get(self::CONTENT_SETTING);
        if (is_string($content) && trim($content) !== '') {
            return (string) TaskDescription::sanitize($content);
        }

        $path = resource_path('content/sales-tax-policy.html');
        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    public static function appliesTo(?User $user): bool
    {
        if (! self::enabled() || ! $user) {
            return false;
        }

        $roles = $user->roles->pluck('name')->map(fn ($name) => strtolower((string) $name));

        // Admin manages the system and is exempt even when the account also has a sales role.
        if ($roles->contains('admin')) {
            return false;
        }

        return $roles->intersect(config('sales_tax_policy.roles', []))->isNotEmpty();
    }

    public static function isAccepted(User $user): bool
    {
        return UserPolicyAcceptance::query()->where('user_id', $user->id)
            ->where('policy_key', config('sales_tax_policy.key'))
            ->where('policy_version', self::version())
            ->whereNull('revoked_at')->exists();
    }
}
