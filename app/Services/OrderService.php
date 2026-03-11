<?php

namespace App\Services;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Models\ProductVariant;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Facades\DB; 

class OrderService
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {
    }

    public function createOrder(array $orderData, array $variants): Order
    {
        return DB::transaction(function () use ($orderData, $variants) {

            $variants = collect($variants)
                ->filter(fn ($v) => isset($v['quantity']) && $v['quantity'] > 0);

            if ($variants->isEmpty()) {
                throw new \Exception('No products selected.');
            }

            $customer = Customer::findOrFail($orderData['customer_id']);

            $order = Order::create([
                'customer_id'     => $customer->id,
                'sale_id'         => auth()->id(),
                'status'          => OrderStatus::PendingManagerApproval,
                'discount_rate'   => $customer->discount_rate,
                'commission_rate' => $customer->commission_rate,
                'code'            => $this->generateOrderCode(),
                'note'            => $orderData['note'] ?? null,
            ]);

            $subtotal = 0;

            foreach ($variants as $variantData) {

                $variant = ProductVariant::with('product')
                    ->where('id', $variantData['id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $price = $this->resolvePrice($variant, $customer);
                $total = $price * $variantData['quantity'];

                $subtotal += $total;

                $order->items()->create([
                    'product_id'         => $variant->product_id,
                    'product_variant_id'=> $variant->id,
                    'quantity'           => $variantData['quantity'],
                    'price'              => $price,
                    'total'              => $total,
                ]);
            }

            $discount = $subtotal * ($customer->discount_rate / 100);

            $order->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total'    => $subtotal - $discount,
            ]);

            $this->approvalService->initOrderApproval($order);

            return $order;
        });
    }

    
}
