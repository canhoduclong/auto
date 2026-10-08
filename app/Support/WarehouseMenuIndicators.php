<?php

namespace App\Support;

use App\Models\ProcessRun;
use App\Models\TaskAssignee;
use App\Models\User;

class WarehouseMenuIndicators
{
    public static function counts(User $user): array
    {
        $roles = $user->roles->pluck('name')->map(fn ($r) => strtolower($r))->all();
        $assigned = ProcessRun::where('status', 'running')->where(function ($q) use ($user, $roles) {
            $q->where(fn ($q) => $q->where('assignee_mode', 'user')->where('assignee_user_id', $user->id))->orWhere(fn ($q) => $q->where('assignee_mode', 'user_role')->where('assignee_user_id', $user->id)->whereIn(\DB::raw('LOWER(assignee_role)'), $roles))->orWhere(fn ($q) => $q->where('assignee_mode', 'role')->whereIn(\DB::raw('LOWER(assignee_role)'), $roles));
        });
        $pending = (clone $assigned)->where(fn ($q) => $q->whereJsonContains('configuration->positions', 'inbox')->orWhereNull('configuration->positions'))->count();
        $pending += ProcessRun::where('status', 'revision')->where('initiator_id', $user->id)->where(fn ($q) => $q->whereJsonContains('configuration->positions', 'inbox')->orWhereNull('configuration->positions'))->count();
        $receipt = TaskAssignee::where('user_id', $user->id)->where('status', 'pending')->whereNull('accepted_at')->whereHas('task', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected', 'completed']))->count();

        $unread = [];
        $routes = ['warehouse.orders', 'warehouse.returns', 'warehouse.inventory-transfers.incoming', 'warehouse.finance-requests.index'];
        foreach ($user->unreadNotifications()->get(['data']) as $notification) {
            $path = parse_url($notification->data['url'] ?? '', PHP_URL_PATH);
            if (! $path) {
                continue;
            }foreach ($routes as $route) {
                $base = parse_url(route($route), PHP_URL_PATH);
                if ($path === $base || str_starts_with($path, $base.'/')) {
                    $unread[$route] = ($unread[$route] ?? 0) + 1;
                    break;
                }
            }
        }

        return ['unread' => $unread, 'Cần xử lý' => $pending, 'Nhận việc / Chưa tiếp nhận' => $receipt, 'coordination' => (clone $assigned)->where('activity', 'shipping_expense')->whereRaw('LOWER(assignee_role) = ?', ['manager_shipper'])->count()];
    }
}
