<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class WarehouseAdjustmentAccess
{
    public function scope(Builder $query, User $user): Builder
    {
        if ($user->hasRole(['admin', 'manager', 'manager_sale'])) {
            return $query;
        }

        if ($user->hasRole(['leader', 'leader_sale', 'sale_manager']) && $user->team_id) {
            return $query->whereIn('user_id', User::query()->select('id')->where('team_id', $user->team_id));
        }

        if ($user->hasRole(['sale', 'leader', 'leader_sale', 'sale_manager'])) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function allows(User $user, Order $order): bool
    {
        return $this->scope(Order::query(), $user)->whereKey($order->id)->exists();
    }
}
