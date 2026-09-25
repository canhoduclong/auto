<?php

namespace App\Observers;

use App\Models\OrderAdjustment;
use App\Services\GoogleSheetsJournalSyncScheduler;

class OrderAdjustmentObserver
{
    public function __construct(private readonly GoogleSheetsJournalSyncScheduler $scheduler) {}

    public function created(OrderAdjustment $adjustment): void
    {
        $this->scheduler->scheduleOrderIds([$adjustment->order_id]);
    }

    public function updated(OrderAdjustment $adjustment): void
    {
        $this->scheduler->scheduleOrderIds([
            $adjustment->order_id,
            $adjustment->getRawOriginal('order_id'),
        ]);
    }

    public function deleted(OrderAdjustment $adjustment): void
    {
        $this->scheduler->scheduleOrderIds([$adjustment->order_id]);
    }
}
