<?php

namespace App\Services;

use App\Models\AdminEvent;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class OrderActivityPresenter
{
    public function recentForUser(User $user, int $limit = 50): Collection
    {
        $events = Schema::hasTable('admin_events') ? AdminEvent::where('actor_id', $user->id)->latest('created_at')->limit($limit)->get() : collect();
        $recentHistory = Schema::hasTable('order_histories') ? OrderHistory::with('order:id,code,customer_id', 'order.customer:id,name')
            ->where('user_id', $user->id)->latest('created_at')->limit($limit)->get() : collect();
        $history = $recentHistory;
        if ($events->isNotEmpty() && Schema::hasTable('order_histories')) {
            $history = $history->concat(OrderHistory::where('user_id', $user->id)
                ->whereBetween('created_at', [$events->min('created_at')->copy()->subSeconds(5), $events->max('created_at')->copy()->addSeconds(5)])
                ->whereIn('order_id', $events->where('event_type', 'order')->map(fn ($e) => data_get($e->metadata, 'order_id') ?? $e->subject_id)->filter()->unique())->get())->unique('id');
        }
        $byOrder = $history->groupBy('order_id');
        $matchedIds = [];
        foreach ($events as $event) {
            $orderId = data_get($event->metadata, 'order_id') ?? $event->subject_id;
            $status = data_get($event->metadata, 'changes.status');
            if (is_array($status)) {
                $status = $status['after'] ?? $status['new'] ?? null;
            }
            $match = $event->event_type === 'order' ? ($byOrder[$orderId] ?? collect())
                ->filter(fn ($h) => abs($h->created_at->timestamp - $event->created_at->timestamp) <= 5
                    && (($status !== null && $h->status_after === $status)
                        || ($event->action === 'created' && $h->action === 'create_order')
                        || ($status === null && in_array($event->action, ['updated', 'status_changed'], true) && $h->action === 'update_order')))
                ->sortBy(fn ($h) => abs($h->created_at->timestamp - $event->created_at->timestamp))->first() : null;
            if ($match) {
                $matchedIds[$match->id] = true;
            }
            $event->setAttribute('activity_detail', $this->present($event, $match));
        }
        // Refusals and route decisions may exist only in the order's business history.
        foreach ($recentHistory as $h) {
            if (isset($matchedIds[$h->id])) {
                continue;
            }
            $message = 'Đơn '.($h->order?->code ?: '#'.$h->order_id);
            if ($h->status_before && $h->status_after && $h->status_before !== $h->status_after) {
                $statuses = Order::statusOptions();
                $message .= ' · Trạng thái: '.($statuses[$h->status_before] ?? $h->status_before).' → '.($statuses[$h->status_after] ?? $h->status_after);
            }
            $event = new AdminEvent(['actor_id' => $user->id, 'event_type' => 'order', 'action' => $h->action,
                'message' => $message, 'url' => route('orders.show', $h->order_id),
                'metadata' => ['order_id' => $h->order_id, 'order_code' => $h->order?->code, 'customer_name' => $h->order?->customer?->name]]);
            $event->created_at = $h->created_at;
            $event->setAttribute('activity_detail', $this->present($event, $h));
            $events->push($event);
        }

        return $events->sortByDesc('created_at')->take($limit)->values();
    }

    public function present(AdminEvent $event, ?OrderHistory $history = null): array
    {
        if ($event->event_type !== 'order') {
            return ['type' => $event->event_type, 'action' => $event->action, 'title' => $event->title,
                'message' => $event->message, 'changes' => [], 'note' => null];
        }
        $metadata = $event->metadata ?? [];
        $status = data_get($metadata, 'changes.status');
        if (is_array($status)) {
            $status = $status['after'] ?? $status['new'] ?? null;
        }
        $action = $history?->action ?? $event->action;
        [, $label] = app(UserActivitySummaryService::class)->describe($action, $history?->status_after ?? $status);
        if (! $history && $status === null && in_array($action, ['updated', 'status_changed', 'update'], true)) {
            $label = 'Chỉnh sửa đơn hàng';
        }
        $customer = $metadata['customer_name'] ?? null;
        $code = $metadata['order_code'] ?? $metadata['code'] ?? null;
        $subject = $customer ?: $code;

        return ['type' => 'Đơn hàng', 'action' => $label,
            'title' => $label.($subject ? ' · '.$subject : ''),
            'message' => $event->message, 'changes' => $metadata['change_details'] ?? [],
            'note' => $history?->note];
    }
}
