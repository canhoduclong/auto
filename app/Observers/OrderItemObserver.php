<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\GoogleSheetsJournalSyncScheduler;
use App\Services\GoogleSheetsOrderSyncScheduler;

class OrderItemObserver
{
    public function __construct(
        private readonly GoogleSheetsJournalSyncScheduler $scheduler,
        private readonly GoogleSheetsOrderSyncScheduler $orderSheetSync,
    ) {}

    public function created(OrderItem $item): void
    {
        $this->recordSaleItemChange($item, 'Thêm hàng');

        $this->scheduler->scheduleOrderIds([$item->order_id]);
        $this->orderSheetSync->schedule([$item->order_id]);
    }

    public function updated(OrderItem $item): void
    {
        $service = app(\App\Services\WarehouseSaleChangeService::class);
        if ($item->wasChanged(['quantity', 'price', 'product_variant_id']) && $item->order && $service->shouldTrack($item->order)) {
            $notes = [];
            foreach (['quantity' => 'Số lượng', 'price' => 'Giá', 'product_variant_id' => 'Mã size'] as $field => $label) {
                if ($item->wasChanged($field)) {
                    $notes[] = $label.': '.$item->getRawOriginal($field).' → '.$item->$field;
                }
            }
            $service->record($item->order, ($item->variant?->name ?: 'Dòng hàng #'.$item->id).': '.implode('; ', $notes));
        }

        $this->scheduler->scheduleOrderIds([
            $item->order_id,
            $item->getRawOriginal('order_id'),
        ]);
        $this->orderSheetSync->schedule([$item->order_id, $item->getRawOriginal('order_id')]);
    }

    public function deleted(OrderItem $item): void
    {
        $this->recordSaleItemChange($item, 'Bỏ hàng');

        $this->scheduler->scheduleOrderIds([$item->order_id]);
        $this->orderSheetSync->schedule([$item->order_id]);
    }
    private function recordSaleItemChange(OrderItem $item, string $action): void
    {
        // This editor replaces all rows and records one before/after summary.
        if (request()->route()?->getActionMethod() === 'myOrderUpdate') {
            return;
        }
        $service = app(\App\Services\WarehouseSaleChangeService::class);
        if ($item->order && $service->shouldTrack($item->order)) {
            $service->record($item->order, $action.': '.($item->variant?->name ?: '#'.$item->product_variant_id)
                .' × '.$item->quantity.'; giá '.number_format((float) $item->price).'đ');
        }
    }

}
