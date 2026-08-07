<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CustomerClassificationService
{
    public const SETTING_KEY = 'customer_classification_config';

    public static function defaults(): array
    {
        return [
            'window_months' => 3,
            'overall' => ['a_min' => 85, 'b_min' => 65, 'c_min' => 40],
            'volume' => ['label' => 'Sản lượng mua', 'weight' => 25, 'a_min' => 50, 'b_min' => 20, 'c_min' => 5],
            'frequency' => ['label' => 'Tần suất mua hàng', 'weight' => 20, 'a_min' => 10, 'b_min' => 5, 'c_min' => 1],
            'trend' => ['label' => 'Xu hướng sản lượng', 'weight' => 15, 'a_min' => 10, 'b_min' => -10, 'c_min' => -30],
            'payment' => ['label' => 'Thanh toán', 'weight' => 20, 'a_min' => 95, 'b_min' => 90, 'c_min' => 70],
            'debt' => ['label' => 'Công nợ', 'weight' => 10, 'a_max' => 0, 'b_max' => 15, 'c_max' => 30],
            'history' => ['label' => 'Lịch sử mua hàng', 'weight' => 5, 'a_min' => 12, 'b_min' => 6, 'c_min' => 1],
            'relationship' => ['label' => 'Quan hệ mua hàng', 'weight' => 5, 'a_min' => 80, 'b_min' => 50, 'c_min' => 20],
            'inactivity_risk_months' => 2,
        ];
    }

    public function config(): array
    {
        $stored = json_decode((string) Setting::get(self::SETTING_KEY, '{}'), true);

        return array_replace_recursive(self::defaults(), is_array($stored) ? $stored : []);
    }

    /**
     * Calculate classifications in batches so the customer list does not issue
     * one query per customer.
     */
    public function classifyMany(Collection $customers): Collection
    {
        $ids = $customers->pluck('id')->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $config = $this->config();
        $windowMonths = max(1, (int) $config['window_months']);
        $now = now();
        $currentStart = $now->copy()->startOfDay()->subMonths($windowMonths);
        $previousStart = $currentStart->copy()->subMonths($windowMonths);

        $orderQuery = Order::query()
            ->whereIn('customer_id', $ids)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REJECTED]);
        if (Schema::hasColumn('orders', 'is_return_order')) {
            $orderQuery->where(function ($query) {
                $query->whereNull('is_return_order')->orWhere('is_return_order', false);
            });
        }

        $orders = $orderQuery->get([
            'id', 'customer_id', 'created_at', 'status', 'payment_status', 'amount_due', 'total',
        ]);
        $quantityOrderIds = $orders
            ->filter(fn ($order) => $order->created_at->gte($previousStart))
            ->pluck('id');
        $quantities = $quantityOrderIds->isEmpty()
            ? collect()
            : \App\Models\OrderItem::query()
                ->whereIn('order_id', $quantityOrderIds)
                ->selectRaw('order_id, SUM(quantity) as purchased_quantity')
                ->groupBy('order_id')
                ->pluck('purchased_quantity', 'order_id');

        $byCustomer = $orders->groupBy('customer_id');
        $results = collect();

        foreach ($ids as $customerId) {
            $customerOrders = $byCustomer->get($customerId, collect())->sortBy('created_at')->values();
            $recentOrders = $customerOrders->filter(fn ($order) => $order->created_at->gte($currentStart));
            $previousOrders = $customerOrders->filter(fn ($order) => $order->created_at->gte($previousStart) && $order->created_at->lt($currentStart));
            $recentQuantity = (float) $recentOrders->sum(fn ($order) => (float) ($quantities[$order->id] ?? 0));
            $previousQuantity = (float) $previousOrders->sum(fn ($order) => (float) ($quantities[$order->id] ?? 0));
            $trend = $previousQuantity > 0
                ? (($recentQuantity - $previousQuantity) / $previousQuantity) * 100
                : ($recentQuantity > 0 ? 100.0 : 0.0);

            $paymentBase = $customerOrders->filter(fn ($order) => (float) ($order->total ?? 0) > 0);
            $paidOrders = $paymentBase->filter(fn ($order) => (string) $order->payment_status === 'paid');
            $paymentRate = $paymentBase->isNotEmpty() ? ($paidOrders->count() / $paymentBase->count()) * 100 : 0;

            $oldestDebt = $customerOrders
                ->filter(fn ($order) => (float) ($order->amount_due ?? 0) > 0)
                ->min('created_at');
            $overdueDays = $oldestDebt ? Carbon::parse($oldestDebt)->startOfDay()->diffInDays($now->copy()->startOfDay()) : 0;
            $firstOrderAt = $customerOrders->min('created_at');
            $lastOrderAt = $customerOrders->max('created_at');
            $historyMonths = $firstOrderAt ? max(1, Carbon::parse($firstOrderAt)->diffInMonths($now) + 1) : 0;
            $inactiveMonths = $lastOrderAt ? Carbon::parse($lastOrderAt)->diffInMonths($now) : null;

            $metrics = [
                'volume' => round($recentQuantity / $windowMonths, 2),
                'frequency' => round($recentOrders->count() / $windowMonths, 2),
                'trend' => round($trend, 2),
                'payment' => round($paymentRate, 2),
                'debt' => $overdueDays,
                'history' => $historyMonths,
                // Every valid order in this application is a Hoàng Long order.
                'relationship' => $customerOrders->isNotEmpty() ? 100 : 0,
            ];

            $criteria = [];
            $score = 0.0;
            foreach (['volume', 'frequency', 'trend', 'payment', 'debt', 'history', 'relationship'] as $key) {
                $grade = $this->criterionGrade($key, (float) $metrics[$key], $config[$key]);
                $points = ['A' => 100, 'B' => 75, 'C' => 50, 'D' => 0][$grade];
                $score += $points * ((float) $config[$key]['weight'] / 100);
                $criteria[$key] = $grade;
            }

            $riskOverride = $overdueDays > (float) $config['debt']['c_max']
                || ($inactiveMonths !== null && $inactiveMonths >= (int) $config['inactivity_risk_months']);
            $grade = $riskOverride ? 'D' : $this->overallGrade($score, $config['overall']);

            $results->put($customerId, [
                'grade' => $grade,
                'score' => round($score, 1),
                'metrics' => $metrics,
                'criteria' => $criteria,
                'risk_override' => $riskOverride,
                'last_order_at' => $lastOrderAt ? Carbon::parse($lastOrderAt) : null,
            ]);
        }

        return $results;
    }

    private function criterionGrade(string $key, float $value, array $rule): string
    {
        if ($key === 'debt') {
            if ($value <= (float) $rule['a_max']) {
                return 'A';
            }
            if ($value <= (float) $rule['b_max']) {
                return 'B';
            }
            if ($value <= (float) $rule['c_max']) {
                return 'C';
            }

            return 'D';
        }

        if ($value >= (float) $rule['a_min']) {
            return 'A';
        }
        if ($value >= (float) $rule['b_min']) {
            return 'B';
        }
        if ($value >= (float) $rule['c_min']) {
            return 'C';
        }

        return 'D';
    }

    private function overallGrade(float $score, array $overall): string
    {
        if ($score >= (float) $overall['a_min']) {
            return 'A';
        }
        if ($score >= (float) $overall['b_min']) {
            return 'B';
        }
        if ($score >= (float) $overall['c_min']) {
            return 'C';
        }

        return 'D';
    }
}
