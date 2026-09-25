<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class GoogleSheetsOrderSyncScheduler
{
    /** @var array<int, true> */
    private static array $orderIds = [];

    private static bool $terminationCallbackRegistered = false;

    /** @param array<int, int|string|null> $orderIds */
    public function schedule(array $orderIds): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $ids = collect($orderIds)->filter()->map(fn ($id) => (int) $id)->filter()->unique()->all();
        if ($ids === []) {
            return;
        }

        DB::afterCommit(function () use ($ids): void {
            foreach ($ids as $id) {
                self::$orderIds[$id] = true;
            }
            if (self::$terminationCallbackRegistered) {
                return;
            }

            self::$terminationCallbackRegistered = true;
            app()->terminating(function (): void {
                $orderIds = array_keys(self::$orderIds);
                self::$orderIds = [];
                self::$terminationCallbackRegistered = false;

                try {
                    $service = app(GoogleSheetsOrderService::class);
                    if (! $service->isConfigured()) {
                        return;
                    }
                    foreach ($orderIds as $orderId) {
                        try {
                            $order = Order::query()->find($orderId);
                            if ($order && $this->shouldSync($order)) {
                                $service->sync($order);
                            }
                        } catch (\Throwable $exception) {
                            report($exception);
                        }
                    }
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        });
    }

    private function shouldSync(Order $order): bool
    {
        if ($order->status === Order::STATUS_CANCELLED) {
            return true;
        }

        return $order->approvals()
            ->where('status', 'approved')
            ->whereHas('step', fn ($query) => $query->whereIn(DB::raw('LOWER(role_slug)'), [
                'manager_sale', 'manager', 'director',
            ]))
            ->exists();
    }
}
