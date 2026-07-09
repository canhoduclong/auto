<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Order;
use App\Models\ProductCuttingBatch;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class OrderPackingController extends Controller
{
    public function __construct(private WarehouseDashboardController $warehouseOrders)
    {
    }

    public function index(Request $request)
    {
        return $this->warehouseOrders->orders($request);
    }

    public function startPacking(Request $request, Order $order)
    {
        return $this->warehouseOrders->startPacking($request, $order);
    }

    public function updateLogistics(Request $request, Order $order)
    {
        return $this->warehouseOrders->updateLogistics($request, $order);
    }

    public function completePacking(Request $request, Order $order)
    {
        return $this->warehouseOrders->completePacking($request, $order);
    }

    public function requestAdjustment(Request $request, Order $order)
    {
        return $this->warehouseOrders->requestAdjustment($request, $order);
    }

    public function createTransferRequest(Request $request, Order $order)
    {
        return $this->warehouseOrders->createTransferRequest($request, $order);
    }

    public function returnToReadyToPack(Request $request, Order $order)
    {
        return $this->warehouseOrders->returnToReadyToPack($request, $order);
    }

    public function reopenPacking(Request $request, Order $order)
    {
        return $this->warehouseOrders->reopenPacking($request, $order);
    }

    public function completeCuttingBatch(Request $request, ProductCuttingBatch $batch)
    {
        $batch->loadMissing('exportDocument.items');
        $sourceVariantIds = $batch->exportDocument?->items
            ?->pluck('product_variant_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values() ?? collect();
        $verifiedVariantIds = collect($batch->picked_material_verifications ?? [])
            ->pluck('variant_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique();

        if ($sourceVariantIds->isNotEmpty() && $sourceVariantIds->diff($verifiedVariantIds)->isNotEmpty()) {
            return back()->with('error', 'Vui lòng bấm Đã lấy cho tất cả mặt hàng kho đã xuất trước khi hoàn thiện pha lóc.');
        }

        return $this->warehouseOrders->completeCuttingBatch($request, $batch);
    }

    public function markCuttingMaterialPicked(Request $request, ProductCuttingBatch $batch, ProductVariant $variant)
    {
        $user = $request->user();
        $managedWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        if (!$managedWarehouseId && !$user?->hasRole('admin')) {
            return back()->with('error', 'Tài khoản chưa được gán kho thực hiện.');
        }
        if ($managedWarehouseId && (int) $batch->warehouse_id !== $managedWarehouseId) {
            abort(403, 'Bạn không có quyền xác nhận mặt hàng của kho khác.');
        }
        if ($batch->status !== ProductCuttingBatch::STATUS_IN_PROGRESS) {
            return back()->with('error', 'Mẻ pha lóc này không còn ở trạng thái đang thực hiện.');
        }

        $batch->loadMissing('exportDocument.items');
        $sourceItem = $batch->exportDocument?->items
            ?->first(fn ($item) => (int) $item->product_variant_id === (int) $variant->id);
        if (!$sourceItem) {
            return back()->with('error', 'Mặt hàng này không nằm trong danh sách kho đã lấy cho mẻ pha lóc.');
        }

        $verifications = collect($batch->picked_material_verifications ?? [])
            ->keyBy(fn ($row) => (int) ($row['variant_id'] ?? 0));
        $verifications->put((int) $variant->id, [
            'variant_id' => (int) $variant->id,
            'quantity' => (float) $sourceItem->quantity,
            'verified_by' => (int) $user->id,
            'verified_by_name' => (string) ($user->name ?? 'Package'),
            'verified_at' => now()->toDateTimeString(),
        ]);

        $batch->update([
            'picked_material_verifications' => $verifications->values()->all(),
        ]);

        return back()->with('success', 'Đã xác nhận đã lấy mặt hàng pha lóc.');
    }

    public function show(Order $order)
    {
        $this->authorizePackageOrder($order);

        return redirect()->route('package.orders', ['date' => $order->created_at?->toDateString()]);
    }

    private function authorizePackageOrder(Order $order): void
    {
        $user = request()->user();
        $managedWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        $orderWarehouseId = $order->warehouse_id ? (int) $order->warehouse_id : null;

        if (!$user?->hasRole('admin') && $managedWarehouseId && $orderWarehouseId && $managedWarehouseId !== $orderWarehouseId) {
            abort(403, 'Đơn hàng thuộc kho khác.');
        }
    }

}
