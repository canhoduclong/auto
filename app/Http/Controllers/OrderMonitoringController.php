<?php

namespace App\Http\Controllers;

use App\Models\ApprovalOrder;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrderMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $this->resolveSelectedDate($request->input('date'));
        $steps = $this->resolveSteps();
        $payload = $this->buildPayload($steps, $selectedDate);
        $recentOrderDates = $this->resolveRecentOrderDates($selectedDate);

        return view('orders.monitoring.index', [
            'steps' => $steps,
            'orders' => $payload['orders'],
            'stats' => $payload['stats'],
            'generatedAt' => now(),
            'selectedDate' => $selectedDate->toDateString(),
            'isAutoRefresh' => $selectedDate->isToday(),
            'recentOrderDates' => $recentOrderDates,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $selectedDate = $this->resolveSelectedDate($request->input('date'));
        $steps = $this->resolveSteps();
        $payload = $this->buildPayload($steps, $selectedDate);

        return response()->json([
            'statsHtml' => view('orders.monitoring._stats', [
                'stats' => $payload['stats'],
            ])->render(),
            'tableHtml' => view('orders.monitoring._table', [
                'steps' => $steps,
                'orders' => $payload['orders'],
            ])->render(),
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'isAutoRefresh' => $selectedDate->isToday(),
        ]);
    }

    private function resolveSteps(): Collection
    {
        $workflow = ApprovalWorkflow::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereJsonContains('applies_to', ApprovalWorkflow::ACTIVITY_ORDER_CREATE)
                    ->orWhereNull('applies_to');
            })
            ->with('steps')
            ->orderByDesc('id')
            ->first();

        if ($workflow && $workflow->steps->isNotEmpty()) {
            return $workflow->steps->sortBy('step_order')->values();
        }

        return ApprovalStep::query()
            ->orderBy('step_order')
            ->get();
    }

    private function buildPayload(Collection $steps, Carbon $selectedDate): array
    {
        $orders = Order::query()
            ->with([
                'customer',
                'user',
                'approvals.step',
                'approvals.approver',
                'histories.user',
            ])
            ->whereDate('created_at', $selectedDate)
            ->latest()
            ->get();

        $dayOrders = Order::query()->whereDate('created_at', $selectedDate);

        $stats = [
            'today_total_orders' => (clone $dayOrders)->count(),
            'today_pending_orders' => (clone $dayOrders)->where('status', 'like', 'pending%')->count(),
            'today_approved_orders' => (clone $dayOrders)->where('status', 'approved')->count(),
            'today_rejected_orders' => (clone $dayOrders)->where('status', 'rejected')->count(),
            'today_processed_approvals' => ApprovalOrder::query()
                ->whereDate('approved_at', $selectedDate)
                ->whereIn('status', ['approved', 'rejected'])
                ->count(),
        ];

        $orders = $orders->map(function (Order $order) use ($steps) {
            $approvalsByStep = $order->approvals->keyBy('approval_step_id');

            $cells = $steps->map(function (ApprovalStep $step) use ($approvalsByStep, $order) {
                $roleSlug = strtolower((string) $step->role_slug);

                if ($roleSlug === 'warehouse') {
                    return $this->resolveWarehouseCell($order, $step, $approvalsByStep->get($step->id));
                }

                if ($roleSlug === 'shipper') {
                    return $this->resolveShipperCell($order, $step, $approvalsByStep->get($step->id));
                }

                $approval = $approvalsByStep->get($step->id);

                return [
                    'step_id' => $step->id,
                    'role_slug' => $step->role_slug,
                    'step_order' => $step->step_order,
                    'status' => $approval?->status ?? 'not_started',
                    'approver_name' => $approval?->approver?->name,
                    'approved_at' => optional($approval?->approved_at)->format('d/m H:i'),
                    'note' => $approval?->note,
                ];
            })->values();

            return [
                'id' => $order->id,
                'code' => $order->code ?: ('#' . $order->id),
                'customer_name' => $order->customer?->name ?? '-',
                'delivery_time' => $order->delivery_time ?: ($order->customer?->delivery_time ?? null),
                'staff_name' => $order->user?->name ?? '-',
                'status' => (string) $order->status,
                'total' => (float) $order->total,
                'created_at' => optional($order->created_at)->format('d/m/Y H:i'),
                'cells' => $cells,
            ];
        });

        return [
            'orders' => $orders,
            'stats' => $stats,
        ];
    }

    private function resolveWarehouseCell(Order $order, ApprovalStep $step, $approval): array
    {
        $latestWarehouseHistory = $order->histories
            ->filter(fn ($history) => strtolower((string) $history->role) === 'warehouse')
            ->sortByDesc('created_at')
            ->first();

        $completedStatuses = [
            'packed',
            'packed_waiting_pickup',
            'pending_shipper_approval',
            'shipping',
            'delivering',
            'delivered',
            'completed',
            'returning',
            'returned',
            'returned_completed',
        ];
        $pendingStatuses = ['approved', 'ready_to_pack', 'pending_warehouse_approval'];

        $status = $approval?->status ?? 'not_started';
        $approverName = $approval?->approver?->name;
        $approvedAt = optional($approval?->approved_at)->format('d/m H:i');
        $note = $approval?->note;

        if ((string) $order->status === 'packing') {
            $status = 'processing';
            $approverName = $latestWarehouseHistory?->user?->name ?? $approverName;
            $approvedAt = optional($latestWarehouseHistory?->created_at)->format('d/m H:i') ?: $approvedAt;
            $note = $latestWarehouseHistory?->note ?? $note;
        } elseif (in_array((string) $order->status, $completedStatuses, true)) {
            $status = 'approved';
            $approverName = $latestWarehouseHistory?->user?->name ?? $approverName;
            $approvedAt = optional($latestWarehouseHistory?->created_at)->format('d/m H:i') ?: $approvedAt;
            $note = $latestWarehouseHistory?->note ?? $note;
        } elseif (in_array((string) $order->status, $pendingStatuses, true) && $status === 'not_started') {
            $status = 'pending';
        }

        return [
            'step_id' => $step->id,
            'role_slug' => $step->role_slug,
            'step_order' => $step->step_order,
            'status' => $status,
            'approver_name' => $approverName,
            'approved_at' => $approvedAt,
            'note' => $note,
        ];
    }

    private function resolveShipperCell(Order $order, ApprovalStep $step, $approval): array
    {
        $latestShipperHistory = $order->histories
            ->filter(fn ($history) => strtolower((string) $history->role) === 'shipper')
            ->sortByDesc('created_at')
            ->first();

        $pendingStatuses = ['packed', 'packed_waiting_pickup', 'pending_shipper_approval'];
        $processingStatuses = ['delivering', 'shipping'];
        $completedStatuses = ['delivered', 'completed'];
        $returnedStatuses = ['returning', 'returned', 'returned_completed'];

        $status = $approval?->status ?? 'not_started';
        $approverName = $approval?->approver?->name;
        $approvedAt = optional($approval?->approved_at)->format('d/m H:i');
        $note = $approval?->note;

        if (in_array((string) $order->status, $processingStatuses, true)) {
            $status = 'processing';
            $approverName = $latestShipperHistory?->user?->name ?? $approverName;
            $approvedAt = optional($latestShipperHistory?->created_at)->format('d/m H:i') ?: $approvedAt;
            $note = $latestShipperHistory?->note ?? $note;
        } elseif (in_array((string) $order->status, $completedStatuses, true)) {
            $status = 'approved';
            $approverName = $latestShipperHistory?->user?->name ?? $approverName;
            $approvedAt = optional($latestShipperHistory?->created_at)->format('d/m H:i') ?: $approvedAt;
            $note = $latestShipperHistory?->note ?? $note;
        } elseif (in_array((string) $order->status, $returnedStatuses, true)) {
            $status = 'returned';
            $approverName = $latestShipperHistory?->user?->name ?? $approverName;
            $approvedAt = optional($latestShipperHistory?->created_at)->format('d/m H:i') ?: $approvedAt;
            $note = $latestShipperHistory?->note ?? $note;
        } elseif (in_array((string) $order->status, $pendingStatuses, true) && $status === 'not_started') {
            $status = 'pending';
        }

        return [
            'step_id' => $step->id,
            'role_slug' => $step->role_slug,
            'step_order' => $step->step_order,
            'status' => $status,
            'approver_name' => $approverName,
            'approved_at' => $approvedAt,
            'note' => $note,
        ];
    }

    private function resolveSelectedDate(?string $dateInput): Carbon
    {
        if (!empty($dateInput)) {
            try {
                return Carbon::parse($dateInput)->startOfDay();
            } catch (\Throwable $e) {
                // Fallback to today when date input is invalid.
            }
        }

        return Carbon::today();
    }

    private function resolveRecentOrderDates(Carbon $selectedDate): Collection
    {
        return Order::query()
            ->selectRaw('DATE(created_at) as order_date, COUNT(*) as total_orders')
            ->groupBy('order_date')
            ->orderByDesc('order_date')
            ->limit(7)
            ->get()
            ->map(function ($row) use ($selectedDate) {
                $date = Carbon::parse($row->order_date);

                return [
                    'value' => $date->toDateString(),
                    'label' => $date->format('d/m'),
                    'day_name' => $date->isoFormat('ddd'),
                    'total_orders' => (int) $row->total_orders,
                    'is_selected' => $date->isSameDay($selectedDate),
                    'is_today' => $date->isToday(),
                ];
            })
            ->values();
    }
}
