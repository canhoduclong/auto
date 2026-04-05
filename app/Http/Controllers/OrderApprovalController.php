<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Setting;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrderApprovalController extends Controller
{
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }

    private function isApproverRole(string $role): bool
    {
        return in_array(strtolower($role), ['leader_sale', 'leader', 'sale_manager', 'manager_sale', 'manager', 'director', 'admin'], true);
    }

    private function userCanApprove(?\App\Models\User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->roles->pluck('name')
            ->map(fn ($role) => strtolower((string) $role))
            ->contains(fn ($role) => $this->isApproverRole($role));
    }

    private function logOrderHistory(Order $order, string $action, ?string $before, ?string $after, ?string $note = null): void
    {
        $user = auth()->user();

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => $action,
            'user_id' => $user?->id,
            'role' => $user?->roles->pluck('name')->first(),
            'status_before' => $before,
            'status_after' => $after,
            'note' => $note,
        ]);
    }

    public function approve(Request $request, Order $order, ApprovalService $approvalService): RedirectResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        if (!$this->userCanApprove($user)) {
            return back()->with('error', __('orders.approval.no_permission'));
        }

        try {
            $statusBefore = (string) $order->status;
            $approvalService->approve($order, $request->user(), $request->input('note'));
            $order->refresh();
            $this->logOrderHistory($order, 'approve_order', $statusBefore, (string) $order->status, $request->input('note'));
            return back()->with('success', __('orders.messages.confirmed'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Order $order, ApprovalService $approvalService): RedirectResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        if (!$this->userCanApprove($user)) {
            return back()->with('error', __('orders.approval.no_permission'));
        }

        try {
            $statusBefore = (string) $order->status;
            $approvalService->reject($order, $request->user(), $request->input('note'));
            $order->refresh();
            $this->logOrderHistory($order, 'reject_order', $statusBefore, (string) $order->status, $request->input('note'));
            return back()->with('success', __('orders.statuses.rejected'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
