<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderWorkflowNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderNotificationService
{
    private const MANAGEMENT_ROLES = [
        'leader',
        'leader_sale',
        'sale_manager',
        'manager',
        'manager_sale',
        'director',
    ];

    public function notifySubmitted(Order $order): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $order->loadMissing('user');
        $sale = $order->user;
        if (!$sale) {
            return;
        }

        $recipients = collect([$sale])
            ->merge($this->relatedManagementUsers($sale))
            ->unique('id')
            ->values();

        Notification::send(
            $recipients,
            new OrderWorkflowNotification($order, OrderWorkflowNotification::SUBMITTED, $sale->id),
        );
    }

    public function notifyApproved(Order $order, User $manager): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $manager->notify(new OrderWorkflowNotification(
            $order,
            OrderWorkflowNotification::APPROVED,
            $manager->id,
        ));
    }

    private function relatedManagementUsers(User $sale): Collection
    {
        if (!$sale->team_id && !$sale->department_id) {
            return collect();
        }

        return User::query()
            ->where('id', '!=', $sale->id)
            ->whereHas('roles', fn ($query) => $query->whereIn(DB::raw('LOWER(name)'), self::MANAGEMENT_ROLES))
            ->where(function ($query) use ($sale) {
                if ($sale->team_id) {
                    $query->where('team_id', $sale->team_id);
                } else {
                    $query->where('department_id', $sale->department_id);
                }
            })
            ->get();
    }
}
