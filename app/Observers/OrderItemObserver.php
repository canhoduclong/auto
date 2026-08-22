<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\GoogleSheetsJournalSyncScheduler;

class OrderItemObserver
{
    public function __construct(private readonly GoogleSheetsJournalSyncScheduler $scheduler) {}

    public function created(OrderItem $item): void
    {
        $this->scheduler->scheduleOrderIds([$item->order_id]);
    }

    public function updated(OrderItem $item): void
    {
        $this->scheduler->scheduleOrderIds([
            $item->order_id,
            $item->getRawOriginal('order_id'),
        ]);
    }

    public function deleted(OrderItem $item): void
    {
        $this->scheduler->scheduleOrderIds([$item->order_id]);
    }
}
