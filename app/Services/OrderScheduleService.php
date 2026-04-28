<?php

namespace App\Services;

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\OrderSchedule;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class OrderScheduleService
{
    public function evaluateSchedule(OrderSchedule $schedule): array
    {
        $schedule->loadMissing('items.variant.product');

        $priceIssue = false;
        $stockIssue = false;

        DB::transaction(function () use ($schedule, &$priceIssue, &$stockIssue) {
            foreach ($schedule->items as $item) {
                $variant = ProductVariant::query()
                    ->withAvailableStock()
                    ->with(['latestPriceRule', 'product'])
                    ->find($item->product_variant_id);

                if (!$variant) {
                    $item->current_price = 0;
                    $item->stock_available = 0;
                    $item->price_diff = true;
                    $item->stock_diff = true;
                    $item->save();
                    $priceIssue = true;
                    $stockIssue = true;
                    continue;
                }

                $currentPrice = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
                $availableStock = max(0, (int) ($variant->available_stock ?? 0));

                $item->current_price = $currentPrice;
                $item->stock_available = $availableStock;
                $item->price_diff = round($currentPrice, 2) !== round((float) $item->scheduled_price, 2);
                $item->stock_diff = $availableStock < (int) $item->quantity;
                $item->save();

                if ($item->price_diff) {
                    $priceIssue = true;
                }
                if ($item->stock_diff) {
                    $stockIssue = true;
                }
            }

            $schedule->price_status = $priceIssue ? 'changed' : 'ok';
            $schedule->stock_status = $stockIssue ? 'insufficient' : 'ok';
            $schedule->status = ($priceIssue || $stockIssue) ? 'need_review' : 'approved';
            $schedule->save();
        });

        return [
            'price_issue' => $priceIssue,
            'stock_issue' => $stockIssue,
            'status' => $schedule->status,
        ];
    }

    public function generateOrder(OrderSchedule $schedule, array $decisions = []): ?Order
    {
        $schedule->loadMissing('items.variant.product', 'customer');

        if ($schedule->generated_order_id) {
            return Order::find($schedule->generated_order_id);
        }

        $items = [];

        foreach ($schedule->items as $item) {
            $rowDecision = $decisions[$item->id] ?? [];
            $action = (string) ($rowDecision['action'] ?? 'keep');

            if ($action === 'remove') {
                continue;
            }

            $currentStock = max(0, (int) $item->stock_available);
            $targetQty = (int) $item->quantity;

            if ($action === 'adjust') {
                $requestedQty = isset($rowDecision['approved_quantity']) ? (int) $rowDecision['approved_quantity'] : $targetQty;
                $targetQty = max(0, min($requestedQty, $currentStock));
            }

            if ($targetQty <= 0) {
                continue;
            }

            $usePrice = (float) ($item->current_price ?? $item->scheduled_price);

            $items[] = [
                'variant_id' => (int) $item->product_variant_id,
                'quantity' => $targetQty,
                'base_price' => $usePrice,
                'price' => $usePrice,
                'unit_discount' => 0,
                'unit_discount_type' => 'decrease',
            ];
        }

        if (empty($items)) {
            return null;
        }

        /** @var \App\Http\Controllers\OrderController $orderController */
        $orderController = app(OrderController::class);
        /** @var \App\Services\ApprovalService $approvalService */
        $approvalService = app(ApprovalService::class);

        $order = $orderController->createOrderFromSchedule(
            $items,
            [
                'customer_id' => (int) $schedule->customer_id,
                'user_id' => (int) $schedule->created_by,
                'delivery_time' => optional($schedule->customer)->delivery_time,
            ],
            $approvalService
        );

        $schedule->status = 'generated';
        $schedule->generated_order_id = $order->id;
        $schedule->review_meta = [
            'generated_at' => now()->toDateTimeString(),
            'decisions' => $decisions,
        ];
        $schedule->save();

        return $order;
    }
}
