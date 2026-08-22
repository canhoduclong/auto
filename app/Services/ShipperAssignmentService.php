<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use App\Notifications\ShipperDeliveryScheduleUpdated;
use Carbon\Carbon;

class ShipperAssignmentService
{
    public function assignmentStatuses(): array
    {
        return [
            Order::STATUS_APPROVED,
            Order::STATUS_READY_TO_PACK,
            Order::STATUS_PACKING,
            Order::STATUS_PACKED,
            Order::STATUS_READY_TO_SHIP,
        ];
    }

    private function plannedOrderIdsForShipper(array $routePlan, int $shipperId): array
    {
        $shipperPlan = collect($routePlan)
            ->first(fn ($plan) => (int) ($plan['shipper_id'] ?? 0) === $shipperId);

        return collect($shipperPlan['routes'] ?? [])
            ->flatMap(fn ($route) => $route['orders'] ?? [])
            ->pluck('order_id')
            ->filter(fn ($orderId) => (int) $orderId > 0)
            ->map(fn ($orderId) => (int) $orderId)
            ->unique()
            ->values()
            ->all();
    }

    public function hasUnpublishedDailySchedule(int $shipperId, Carbon|string|null $date = null): bool
    {
        $dateString = $date instanceof Carbon
            ? $date->toDateString()
            : Carbon::parse($date ?: now())->toDateString();

        $orders = Order::query()
            ->where('shipper_id', $shipperId)
            ->whereIn('status', $this->assignmentStatuses())
            ->forWorkflowDate($dateString)
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('delivery_time')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return false;
        }

        $snapshot = $orders->map(fn (Order $order) => [
            'order_id' => (int) $order->id,
            'daily_sequence' => $order->daily_sequence !== null ? (int) $order->daily_sequence : null,
            'delivery_date' => optional($order->delivery_date)->toDateString(),
            'delivery_time' => $order->delivery_time,
            'updated_at' => optional($order->updated_at)->toDateTimeString(),
        ])->values()->all();
        $snapshotHash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $latestHistory = OrderHistory::query()
            ->join('orders', 'orders.id', '=', 'order_histories.order_id')
            ->where('orders.shipper_id', $shipperId)
            ->where(function ($dateQuery) use ($dateString): void {
                $dateQuery->whereDate('orders.created_at', $dateString);
                if (Carbon::parse($dateString)->isToday()) {
                    $dateQuery->orWhere('orders.skip_auto_cancel', true);
                }
            })
            ->whereIn('order_histories.action', ['schedule_created', 'schedule_confirmed', 'schedule_rejected'])
            ->orderByDesc('order_histories.created_at')
            ->orderByDesc('order_histories.id')
            ->select('order_histories.*')
            ->first();

        return !$latestHistory || $latestHistory->schedule_snapshot_hash !== $snapshotHash;
    }

    public function publishDailySchedule(
        int $shipperId,
        Carbon|string|null $date = null,
        ?int $actorId = null,
        string $actorRole = 'system',
        ?string $notes = null,
        array $routePlan = [],
    ): bool {
        $dateString = $date instanceof Carbon
            ? $date->toDateString()
            : Carbon::parse($date ?: now())->toDateString();

        $plannedOrderIds = $this->plannedOrderIdsForShipper($routePlan, $shipperId);

        $orders = Order::query()
            ->where('shipper_id', $shipperId)
            ->whereIn('status', $this->assignmentStatuses())
            ->when(
                $plannedOrderIds !== [],
                fn ($query) => $query->whereIn('id', $plannedOrderIds)
                    ->where(function ($dateQuery) use ($dateString, $plannedOrderIds): void {
                        $dateQuery->forWorkflowDate($dateString)
                            ->orWhere(function ($exceptionQuery) use ($plannedOrderIds): void {
                                $exceptionQuery->where('skip_auto_cancel', true)
                                    ->whereIn('id', $plannedOrderIds);
                            });
                    }),
                fn ($query) => $query->forWorkflowDate($dateString)
            )
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('delivery_time')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return false;
        }

        if ($plannedOrderIds !== [] && $orders->count() !== count($plannedOrderIds)) {
            return false;
        }

        $shipperRoutePlan = collect($routePlan)
            ->first(fn ($shipperPlan) => (int) ($shipperPlan['shipper_id'] ?? 0) === $shipperId);

        $snapshot = $orders->map(fn (Order $order) => [
            'order_id' => (int) $order->id,
            'daily_sequence' => $order->daily_sequence !== null ? (int) $order->daily_sequence : null,
            'delivery_date' => optional($order->delivery_date)->toDateString(),
            'delivery_time' => $order->delivery_time,
            'updated_at' => optional($order->updated_at)->toDateTimeString(),
        ])->values()->all();
        if ($shipperRoutePlan) {
            $snapshot[] = [
                'route_plan' => $shipperRoutePlan,
            ];
        }
        $snapshotJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $snapshotHash = hash('sha256', $snapshotJson);

        $latestHistory = OrderHistory::query()
            ->join('orders', 'orders.id', '=', 'order_histories.order_id')
            ->where('orders.shipper_id', $shipperId)
            ->where(function ($dateQuery) use ($dateString): void {
                $dateQuery->whereDate('orders.created_at', $dateString);
                if (Carbon::parse($dateString)->isToday()) {
                    $dateQuery->orWhere('orders.skip_auto_cancel', true);
                }
            })
            ->whereIn('order_histories.action', ['schedule_created', 'schedule_confirmed', 'schedule_rejected'])
            ->orderByDesc('order_histories.created_at')
            ->orderByDesc('order_histories.id')
            ->select('order_histories.*')
            ->first();

        if (in_array($latestHistory?->action, ['schedule_created', 'schedule_confirmed'], true)
            && $latestHistory?->schedule_snapshot_hash === $snapshotHash
        ) {
            return false;
        }

        $routeChanged = $latestHistory !== null
            && $latestHistory->schedule_snapshot_hash !== $snapshotHash;
        $note = $routeChanged
            ? 'Manager đã cập nhật lộ trình giao hàng. Vui lòng kiểm tra và xác nhận.'
            : 'Manager đã gửi lộ trình giao hàng. Vui lòng kiểm tra và xác nhận.';
        if (filled($notes)) {
            $note .= ' Ghi chú: ' . trim($notes);
        }

        foreach ($orders as $order) {
            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'schedule_created',
                'user_id' => $actorId,
                'role' => $actorRole,
                'status_before' => $order->status,
                'status_after' => $order->status,
                'note' => $note,
                'schedule_snapshot_hash' => $snapshotHash,
                'schedule_snapshot' => $snapshotJson,
            ]);
        }

        $shipper = User::query()->find($shipperId);
        $shipper?->notify(new ShipperDeliveryScheduleUpdated($dateString, $orders->count(), $routeChanged));

        return true;
    }
}
