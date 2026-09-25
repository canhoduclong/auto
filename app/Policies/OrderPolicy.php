<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function viewAny(User $user) {  
        return $user->hasPermission('orders.index');
    }

    public function show(User $user, Order $order) {
        return $user->hasPermission('orders.show');
    }

    public function create(User $user) {
        return $user->hasPermission('orders.create');
    }

    public function update(User $user, Order $order) {
        // Không cho sửa nếu đã thanh toán đủ hoặc đã hoàn thành
        if ($order->isPaid() || $order->status === Order::STATUS_COMPLETED) {
            return false;
        }
        return $user->hasPermission('orders.update');
    }
    public function edit(User $user, Order $order) {
        return $user->hasPermission('orders.edit');
    }
    public function delete(User $user, Order $order) {
        return false;
    }
}





