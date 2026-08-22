<?php

namespace App\Observers;

use App\Models\OrderAdjustment;
use App\Models\OrderAdjustmentItem;
use App\Services\GoogleSheetsJournalSyncScheduler;

class OrderAdjustmentItemObserver
{
    public function __construct(private readonly GoogleSheetsJournalSyncScheduler $scheduler) {}

    public function created(OrderAdjustmentItem $item): void
    {
        $this->schedule($item->order_adjustment_id);
    }

    public function updated(OrderAdjustmentItem $item): void
    {
        $this->schedule($item->order_adjustment_id);
        $this->schedule($item->getRawOriginal('order_adjustment_id'));
    }

    public function deleted(OrderAdjustmentItem $item): void
    {
        $this->schedule($item->order_adjustment_id);
    }

    private function schedule(int|string|null $adjustmentId): void
    {
        if (! $adjustmentId) {
            return;
        }

        $orderId = OrderAdjustment::query()->whereKey($adjustmentId)->value('order_id');
        $this->scheduler->scheduleOrderIds([$orderId]);
    }
}
