<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskPermission extends Model
{
    protected $fillable = [
        'role_id',
        'permission_slug',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // ── Permission Slugs ─────────────────────────────────────────────

    public const ASSIGN_TASK = 'assign_task';
    public const COMPLETE_TASK = 'complete_task';
    public const VERIFY_COMPLETION = 'verify_completion';
    public const VIEW_TASK_HISTORY = 'view_task_history';
    public const TRACK_PROGRESS = 'track_progress';

    public const PERMISSION_LABELS = [
        self::ASSIGN_TASK => 'Giao việc',
        self::COMPLETE_TASK => 'Hoàn thành việc',
        self::VERIFY_COMPLETION => 'Xác nhận hoàn thành',
        self::VIEW_TASK_HISTORY => 'Xem lịch sử công việc',
        self::TRACK_PROGRESS => 'Theo dõi tiến độ',
    ];

    public static function getPermissionLabel(string $slug): string
    {
        return self::PERMISSION_LABELS[$slug] ?? $slug;
    }

    public static function userHasPermission(User $user, string $permissionSlug): bool
    {
        return $user->roles()
            ->whereHas('taskPermissions', fn($q) => $q->where('permission_slug', $permissionSlug))
            ->exists();
    }

    public static function assignPermissionToRole(Role $role, string $permissionSlug): void
    {
        self::firstOrCreate([
            'role_id' => $role->id,
            'permission_slug' => $permissionSlug,
        ]);
    }

    public static function removePermissionFromRole(Role $role, string $permissionSlug): void
    {
        self::where('role_id', $role->id)
            ->where('permission_slug', $permissionSlug)
            ->delete();
    }
}
