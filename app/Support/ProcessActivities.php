<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class ProcessActivities
{
    public static function all(): array
    {
        return ['shipping_expense' => ['label' => 'Xác nhận chi phí ship', 'entity' => 'shipping_expense', 'positions' => ['inbox', 'order_list', 'order_detail', 'shipping_list']], 'order_review' => ['label' => 'Xét duyệt hồ sơ đơn hàng', 'entity' => 'order', 'positions' => ['inbox', 'order_list', 'order_detail']], 'product_review' => ['label' => 'Xét duyệt hồ sơ sản phẩm', 'entity' => 'product', 'positions' => ['inbox', 'product_list', 'product_detail']]];
    }

    public static function positions(): array
    {
        return ['inbox' => 'Trang Cần xử lý', 'order_list' => 'Danh sách đơn hàng', 'order_detail' => 'Chi tiết đơn hàng', 'shipping_list' => 'Quản lý phí ship', 'product_list' => 'Danh sách sản phẩm', 'product_detail' => 'Chi tiết sản phẩm'];
    }

    public static function canSubmit(string $activity, object $subject, User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }if ($activity === 'order_review') {
            return $subject instanceof Order && ((int) $subject->user_id === (int) $user->id || $user->can('show', $subject));
        }if ($activity === 'product_review') {
            return $subject instanceof Product && ($user->can('show', $subject) || $user->hasRole(['warehouse', 'manager', 'ceo', 'director']));
        }

        return false;
    }
}
