<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCancelOverdueOrders extends Command
{
    protected $signature   = 'orders:auto-cancel-overdue';
    protected $description = 'Đánh dấu Giao trễ các đơn đã quá giờ giao 6 tiếng';

    private const BUSINESS_TIMEZONE = 'Asia/Bangkok';
    private const CANCELLATION_GRACE_HOURS = 6;

    /**
    * Statuses eligible for overdue marking when the delivery deadline has passed.
     *
     * Group 1 – pre-packing (never reached the warehouse packing stage):
     *   pending, pending_leader_approval, pending_manager_approval,
     *   pending_warehouse_approval, approved, ready_to_pack, packing
     *
     * Group 2 – packed but waiting for pickup/delivery and past today:
     *   packed (= STATUS_PACKED), packed_waiting_pickup (= STATUS_READY_TO_SHIP)
     */
    private const CANCELLABLE_STATUSES = [
        'pending',
        'pending_leader_approval',
        'pending_manager_approval',
        'pending_warehouse_approval',
        'approved',
        'ready_to_pack',
        'packing',
        'packed',
        'packed_waiting_pickup',
    ];

    public function handle(): int
    {
        $now       = now(self::BUSINESS_TIMEZONE);
        $overdue = 0;

        $orders = Order::with(['items', 'customer:id,delivery_time'])
            ->whereIn('status', self::CANCELLABLE_STATUSES)
            ->where('skip_auto_cancel', false)
            // Yêu cầu điều chỉnh đã hoàn tất xác nhận đơn vẫn còn hiệu lực và
            // cần tiếp tục qua Kho, kể cả khi ngày nghiệp vụ đã qua.
            ->whereDoesntHave('adjustments', fn ($adjustments) => $adjustments
                ->where('status', \App\Models\OrderAdjustment::STATUS_COMPLETED))
            ->where(function ($query) use ($now): void {
                $query->whereDate('delivery_date', '<=', $now->toDateString())
                    ->orWhereNull('delivery_date');
            })
            ->get();

        foreach ($orders as $order) {
            if (!$this->isPastCancellationDeadline($order, $now)) {
                continue;
            }

            try {
                DB::transaction(function () use ($order) {
                    $statusBefore = (string) $order->status;

                    $order->status = Order::STATUS_OVERDUE_DELIVERY;
                    $order->save();

                    OrderHistory::create([
                        'order_id'      => $order->id,
                        'action'        => 'mark_overdue_delivery',
                        'user_id'       => null,
                        'role'          => 'system',
                        'status_before' => $statusBefore,
                        'status_after'  => Order::STATUS_OVERDUE_DELIVERY,
                        'note'          => 'Hệ thống đánh dấu Giao trễ do quá giờ giao 6 tiếng (hạn giao được tính sớm nhất vào ngày sau ngày lên đơn).',
                    ]);
                });

                $overdue++;
                $this->line("  Đã đánh dấu Giao trễ đơn #{$order->code} (trạng thái trước: {$order->getOriginal('status')})");
            } catch (\Throwable $e) {
                $this->error("  Lỗi khi đánh dấu Giao trễ đơn #{$order->code}: " . $e->getMessage());
            }
        }

        $this->info("Hoàn tất: đã đánh dấu Giao trễ {$overdue} đơn quá hạn.");

        return self::SUCCESS;
    }

    private function isPastCancellationDeadline(Order $order, Carbon $now): bool
    {
        // Some import/automation flows store the business date as delivery_date.
        // Never expire an order before the next day's delivery window, even
        // when it can be fulfilled on the day it was placed.
        $earliestDeliveryDate = $order->created_at->copy()
            ->setTimezone(self::BUSINESS_TIMEZONE)->addDay()->toDateString();
        $deliveryDate = max($earliestDeliveryDate, $order->delivery_date?->toDateString() ?? $earliestDeliveryDate);
        $deliveryTime = trim((string) ($order->delivery_time ?: $order->customer?->delivery_time));
        $time = $this->extractDeliveryTime($deliveryTime);

        $deliveryAt = Carbon::parse($deliveryDate, self::BUSINESS_TIMEZONE);
        if ($time === null) {
            // Free-form or missing delivery times must never make an order overdue early.
            $deliveryAt->endOfDay();
        } else {
            $deliveryAt->setTime($time['hour'], $time['minute']);
        }

        return $now->gt($deliveryAt->addHours(self::CANCELLATION_GRACE_HOURS));
    }

    /**
     * Read the last time from a free-form delivery window (for example 8h-10h).
     * The last time is the safe end of the delivery window.
     *
     * @return array{hour: int, minute: int}|null
     */
    private function extractDeliveryTime(string $value): ?array
    {
        if ($value === '') {
            return null;
        }

        $normalizedValue = mb_strtolower($value);

        preg_match_all(
            '/(?<!\d)([01]?\d|2[0-3])\s*(?::|h|g(?:iờ)?)(?:\s*([0-5]\d))?/iu',
            $normalizedValue,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        if ($matches === []) {
            return null;
        }

        $match = $matches[array_key_last($matches)];
        $hour = (int) $match[1][0];
        $minute = isset($match[2][0]) && $match[2][0] !== '' ? (int) $match[2][0] : 0;
        $contextStart = max(0, (int) $match[0][1] - 12);
        // PREG_OFFSET_CAPTURE returns a byte offset, so use substr here as well.
        $context = substr($normalizedValue, $contextStart, 48);

        if ($hour < 12 && preg_match('/\b(chiều|toi|tối|pm)\b/u', $context)) {
            $hour += 12;
        } elseif ($hour === 12 && preg_match('/\b(đêm|khuya|am)\b/u', $context)) {
            $hour = 0;
        }

        return ['hour' => $hour, 'minute' => $minute];
    }

}
