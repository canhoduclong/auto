<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderHistory;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderApprovalController extends Controller
{
    private function isApproverRole(string $role): bool
    {
        return in_array(strtolower($role), ['leader_sale', 'leader', 'sale_manager', 'manager_sale', 'manager', 'director', 'warehouse', 'shipper', 'admin'], true);
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
        $primaryRole = strtolower((string) $user?->roles->pluck('name')->first());
        if (!$this->isApproverRole($primaryRole)) {
            return back()->with('error', 'Ban khong co quyen duyet don hang.');
        }

        try {
            $statusBefore = (string) $order->status;
            $approvalService->approve($order, $request->user(), $request->input('note'));
            $order->refresh();
            $this->logOrderHistory($order, 'approve_order', $statusBefore, (string) $order->status, $request->input('note'));
            return back()->with('success', 'Đã duyệt bước hiện tại thành công.');
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
        $primaryRole = strtolower((string) $user?->roles->pluck('name')->first());
        if (!$this->isApproverRole($primaryRole)) {
            return back()->with('error', 'Ban khong co quyen tu choi don hang.');
        }

        try {
            $statusBefore = (string) $order->status;
            $approvalService->reject($order, $request->user(), $request->input('note'));
            $order->refresh();
            $this->logOrderHistory($order, 'reject_order', $statusBefore, (string) $order->status, $request->input('note'));
            return back()->with('success', 'Đơn hàng đã bị từ chối.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
