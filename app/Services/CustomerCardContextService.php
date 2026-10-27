<?php

namespace App\Services;

use App\Models\{Customer, Order, User};
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CustomerCardContextService
{
    public function canViewContact(Customer $customer, ?User $user): bool
    {
        return $user && (!$customer->isFree() || $user->hasRole(['admin', 'manager', 'manager_sale', 'director']));
    }

    public function enrich(Collection $customers, ?User $user): void
    {
        if ($customers->isEmpty()) return;
        $customers->loadMissing(['lastOrder', 'previousOwnership.sale:id,name', 'latestCustomerFeedback', 'latestValidCustomerOrder.user:id,name', 'currentOwner:id,name', 'assignedTo:id,name', 'user:id,name']);
        $valid = Order::query()->whereIn('customer_id', $customers->pluck('id'))
            ->whereNull('trash_at')->whereNotIn('status', ['cancelled', 'canceled', 'rejected', 'draft']);
        $stats = (clone $valid)->select('customer_id')
            ->selectRaw('MAX(CASE WHEN accounting_sales_import_batch_id IS NOT NULL THEN COALESCE(delivery_date, DATE(created_at)) ELSE DATE(created_at) END) as last_order_date')
            ->selectRaw("MAX(CASE WHEN status IN ('delivered', 'completed') AND COALESCE(DATE(delivered_at), delivery_date, DATE(created_at)) <= ? THEN COALESCE(DATE(delivered_at), delivery_date, DATE(created_at)) END) as last_received_date", [today()->toDateString()])
            ->groupBy('customer_id')->get()->keyBy('customer_id');
        $feedback = (clone $valid)->whereNotNull('customer_feedback_status')->select('customer_id', 'customer_feedback_status')->selectRaw('COUNT(*) as total')->groupBy('customer_id', 'customer_feedback_status')->get()->groupBy('customer_id');
        foreach ($customers as $customer) {
            $stat = $stats->get($customer->id);
            $lastReceived = $stat?->last_received_date;
            $counts = ($feedback->get($customer->id) ?? collect())->pluck('total', 'customer_feedback_status');
            $latest = $customer->latestCustomerFeedback;
            $previousSale = $customer->previousOwnership?->sale?->name
                ?? $customer->latestValidCustomerOrder?->user?->name
                ?? $customer->currentOwner?->name ?? $customer->assignedTo?->name;
            $customer->setAttribute('card_context', [
                'previous_sale' => $previousSale,
                'last_order_date' => $stat?->last_order_date ? Carbon::parse($stat->last_order_date)->format('d/m/Y') : null,
                'last_received_date' => $lastReceived ? Carbon::parse($lastReceived)->format('d/m/Y') : null,
                'days_without_order' => $lastReceived ? (int) Carbon::parse($lastReceived)->startOfDay()->diffInDays(today()) : null,
                'feedback_counts' => collect(Order::customerFeedbackOptions())->map(fn ($label, $key) => $label.': '.(int) ($counts[$key] ?? 0))->implode(' · '),
                'feedback_total' => (int) $counts->sum(),
                'latest_feedback' => $latest ? trim((string) $latest->customer_feedback_note) : null,
                'latest_feedback_date' => $latest?->customer_feedback_at?->format('d/m/Y'),
            ]);
            $customer->setAttribute('card_is_free', $customer->isFree());
            $visible = $this->canViewContact($customer, $user);
            $customer->setAttribute('can_view_contact', $visible);
            if (!$visible) $customer->setAttribute('phone', null);
            $customer->makeHidden('lastOrder');
            // Relations are used only for the summary, never sent as raw orders/contact data.
            foreach (['previousOwnership', 'latestCustomerFeedback', 'latestValidCustomerOrder'] as $relation) $customer->unsetRelation($relation);
        }
    }
}
