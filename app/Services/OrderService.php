<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderApproval;
use App\Workflows\OrderWorkflow;
use App\Enums\OrderStatus;
use App\Models\User;
use App\Models\ProductVariant;

class OrderService
{
    public function createOrder(array $orderData, array $variants): Order
    {
        $variants = array_filter($variants, function ($variant) {
            return isset($variant['quantity']) && $variant['quantity'] > 0;
        });

        if (empty($variants)) {
            throw new \Exception("No products selected.");
        }

        $totalAmount = 0;
        $orderItems = [];

        foreach ($variants as $variantData) {
            $variant = ProductVariant::with('latestPriceRule', 'product')->find($variantData['id']);
            if ($variant) {
                $price = $variant->latestPriceRule->price ?? $variant->price;
                $total = $price * $variantData['quantity'];
                $totalAmount += $total; 
                $orderItems[] = [
                    'product_id' => $variant->product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $variantData['quantity'],
                    'price' => $price,
                    'total' => $total,
                ];
            }
        }

        $orderData['total_amount'] = $totalAmount ?? 0;
        $orderData['code'] = 'ORD-' . strtoupper(uniqid());
        $order = Order::create($orderData);

        if ($order) {
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }
        }

        return $order;
    }

    public function updateStatus(Order $order, string $newStatus, User $user): Order
    {
        $workflow = new OrderWorkflow();

        // Kiểm tra transition hợp lệ
        if (! $workflow->canTransition($order->status, $newStatus)) {
            throw new \Exception("Không thể chuyển trạng thái từ {$order->status} sang {$newStatus}");
        }

        // Kiểm tra role
        if ($newStatus === OrderStatus::LeaderConfirmed->value && ! $user->hasRole('leader')) {
            throw new \Exception("Chỉ Leader mới được quyền xác nhận");
        }

        if ($newStatus === OrderStatus::ManagerConfirmed->value && ! $user->hasRole('manager')) {
            throw new \Exception("Chỉ Manager mới được quyền xác nhận");
        }

        // Cập nhật trạng thái
        $order->status = $newStatus;
        $order->save();

        // Lưu log vào bảng approvals
        OrderApproval::create([
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'role' => $user->roles()->first()->name ?? 'no-role',
            'status'   => $newStatus,
            'note'     => null,
        ]);

        return $order;
    }
}
