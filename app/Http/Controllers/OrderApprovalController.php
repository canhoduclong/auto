<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderApprovalController extends Controller
{
    public function approve(Request $request, Order $order, ApprovalService $approvalService): RedirectResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $approvalService->approve($order, $request->user(), $request->input('note'));
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

        try {
            $approvalService->reject($order, $request->user(), $request->input('note'));
            return back()->with('success', 'Đơn hàng đã bị từ chối.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
