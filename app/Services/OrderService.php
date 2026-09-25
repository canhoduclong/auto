<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\ProductVariant;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

            $order = new Order();
            $order->customer_id = $customer->id;
            $order->user_id = $orderData['user_id'] ?? auth()->id();
            $order->code = $this->generateOrderCode();
            $order->status = $this->normalizeEnumValue(
                $orderData['status'] ?? OrderStatus::Pending->value,
                OrderStatus::Pending->value
            );
            $order->payment_status = $this->normalizeEnumValue(
                $orderData['payment_status'] ?? PaymentStatus::Unpaid->value,
                PaymentStatus::Unpaid->value
            );
            $order->delivery_status = $this->normalizeEnumValue(
                $orderData['delivery_status'] ?? DeliveryStatus::NotShipped->value,
                DeliveryStatus::NotShipped->value
            );
            $order->total = 0;
            $order->note = $orderData['note'] ?? null;
            $order->warehouse_id = null; // Đảm bảo luôn NULL khi tạo mới/copy
            $order->save();

            $total = 0;

            foreach ($variants as $variantData) {
                $variant = ProductVariant::with('product')
                    ->where('id', $variantData['id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $price = $this->resolvePrice($variant, $variantData);
                $lineTotal = $price * (int) $variantData['quantity'];

                $total += $lineTotal;

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id'=> $variant->id,
                    'quantity' => (int) $variantData['quantity'],
                    'price' => $price,
                    'total' => $lineTotal,
                ]);
            }

            $order->update([
                'total' => $total,
                'amount_due' => 0,
            ]);

            $this->approvalService->initOrderApproval($order);

            return $order;
        });
    }

    private function resolvePrice(ProductVariant $variant, array $variantData): float
    {
        if (array_key_exists('price', $variantData) && $variantData['price'] !== null) {
            return (float) $variantData['price'];
        }

        return (float) ($variant->latestPriceRule?->price ?? 0);
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'ORD-' . strtoupper(Str::random(8));
        } while (Order::where('code', $code)->exists());

        return $code;
    }

    private function normalizeEnumValue(mixed $value, string $fallback): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $fallback;
    }
}
