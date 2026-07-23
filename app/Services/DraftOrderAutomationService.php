<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\OrderController;
use App\Models\OrderSchedule;
use App\Models\ProductVariant;
use App\Models\TextOrderDraft;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DraftOrderAutomationService
{
    public function generate(TextOrderDraft $draft, string $scheduledDate): OrderSchedule
    {
        return DB::transaction(function () use ($draft, $scheduledDate) {
            $draft = TextOrderDraft::query()->lockForUpdate()->findOrFail($draft->id);

            $existing = OrderSchedule::query()
                ->where('text_order_draft_id', $draft->id)
                ->whereDate('schedule_date', $scheduledDate)
                ->first();

            if ($existing) {
                return $existing;
            }

            if (!$draft->customer_id) {
                throw new \RuntimeException('Đơn mẫu chưa chọn khách hàng.');
            }

            $templateItems = collect($draft->parsed_items ?: [[
                'product_variant_id' => $draft->product_variant_id,
                'quantity' => $draft->quantity,
                'size_kg' => $draft->size_kg,
            ]]);

            if ($templateItems->isEmpty() || $templateItems->contains(
                fn (array $item) => empty($item['product_variant_id']) || (int) ($item['quantity'] ?? 0) < 1
            )) {
                throw new \RuntimeException('Đơn mẫu có sản phẩm chưa hợp lệ.');
            }

            $variants = ProductVariant::query()
                ->with(['latestPriceRule', 'product'])
                ->whereIn('id', $templateItems->pluck('product_variant_id')->map(fn ($id) => (int) $id)->all())
                ->get()
                ->keyBy('id');

            if ($variants->count() !== $templateItems->pluck('product_variant_id')->map(fn ($id) => (int) $id)->unique()->count()) {
                throw new \RuntimeException('Một hoặc nhiều sản phẩm trong đơn mẫu không còn tồn tại.');
            }

            $schedule = OrderSchedule::query()->create([
                'customer_id' => $draft->customer_id,
                'text_order_draft_id' => $draft->id,
                'schedule_date' => $scheduledDate,
                'status' => 'pending',
                'price_status' => 'ok',
                'stock_status' => 'ok',
                'created_by' => $draft->sale_id ?: $draft->created_by,
                'is_active' => true,
                'review_meta' => [
                    'source' => 'text_order_draft',
                    'automation_mode' => $draft->automation_mode,
                    'scheduled_for' => $scheduledDate,
                    'copied_at' => Carbon::now('Asia/Bangkok')->toDateTimeString(),
                ],
            ]);

            $orderItems = [];
            foreach ($templateItems as $item) {
                $variant = $variants->get((int) $item['product_variant_id']);
                $currentPrice = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);
                $quantity = (int) $item['quantity'];

                $schedule->items()->create([
                    'product_id' => (int) $variant->product_id,
                    'product_variant_id' => (int) $variant->id,
                    'quantity' => $quantity,
                    'scheduled_price' => $currentPrice,
                    'current_price' => $currentPrice,
                    'price_diff' => false,
                    'stock_available' => 0,
                    'stock_diff' => false,
                ]);

                $orderItems[] = [
                    'variant_id' => (int) $variant->id,
                    'quantity' => $quantity,
                    'base_price' => $currentPrice,
                    'unit_discount' => 0,
                    'unit_discount_type' => 'decrease',
                    'unit_weight' => isset($item['size_kg']) ? (float) $item['size_kg'] : null,
                ];
            }

            $customer = $draft->customer()->firstOrFail();
            $truckStation = $draft->use_truck_station ? $draft->truckStation()->first() : null;
            $order = app(OrderController::class)->createOrderFromSchedule(
                $orderItems,
                [
                    'customer_id' => $customer->id,
                    'user_id' => $draft->sale_id ?: $draft->created_by,
                    'actor_user_id' => $draft->sale_id ?: $draft->created_by,
                    'recipient_name' => $draft->customer_name ?: $customer->name,
                    'recipient_phone' => $draft->phone ?: $customer->phone,
                    'recipient_address' => $draft->address ?: $customer->address,
                    'note' => $draft->note,
                    'delivery_date' => Carbon::today('Asia/Bangkok')->toDateString(),
                    'delivery_time' => $draft->delivery_time,
                    'use_truck_station' => (bool) $draft->use_truck_station,
                    'truck_station_id' => $truckStation?->id,
                    'truck_station_name' => $draft->truck_station_name ?: $truckStation?->name,
                    'truck_station_address' => $draft->truck_station_address ?: $truckStation?->address,
                    'truck_station_phone' => $draft->truck_station_phone ?: $truckStation?->phone,
                    'truck_receive_time' => $draft->truck_receive_time,
                    'status' => OrderStatus::Pending->value,
                    'payment_status' => PaymentStatus::Unpaid->value,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                    'allow_backorder' => true,
                ],
                app(ApprovalService::class)
            );

            $meta = (array) $schedule->review_meta;
            $meta['generated_at'] = Carbon::now('Asia/Bangkok')->toDateTimeString();
            $schedule->update([
                'status' => 'generated',
                'generated_order_id' => $order->id,
                'review_meta' => $meta,
            ]);

            $draft->update([
                'automation_last_run_at' => Carbon::now('Asia/Bangkok'),
                'automation_last_error' => null,
            ]);

            return $schedule->refresh();
        });
    }
}
