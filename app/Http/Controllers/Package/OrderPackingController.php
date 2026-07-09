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
        return $this->setCuttingMaterialPicked($request, $batch, $variant, true);
    }

    public function unmarkCuttingMaterialPicked(Request $request, ProductCuttingBatch $batch, ProductVariant $variant)
    {
        return $this->setCuttingMaterialPicked($request, $batch, $variant, false);
    }

    private function setCuttingMaterialPicked(Request $request, ProductCuttingBatch $batch, ProductVariant $variant, bool $picked)
    {
        $user = $request->user();
        $managedWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        if (!$managedWarehouseId && !$user?->hasRole('admin')) {
            return $this->pickedMaterialResponse($request, false, 'Tài khoản chưa được gán kho thực hiện.', [], 422);
        }
        if ($managedWarehouseId && (int) $batch->warehouse_id !== $managedWarehouseId) {
            abort(403, 'Bạn không có quyền xác nhận mặt hàng của kho khác.');
        }
        if ($batch->status !== ProductCuttingBatch::STATUS_IN_PROGRESS) {
            return $this->pickedMaterialResponse($request, false, 'Mẻ pha lóc này không còn ở trạng thái đang thực hiện.', [], 422);
        }

        $batch->loadMissing('exportDocument.items');
        $sourceItem = $batch->exportDocument?->items
            ?->first(fn ($item) => (int) $item->product_variant_id === (int) $variant->id);
        if (!$sourceItem) {
            return $this->pickedMaterialResponse($request, false, 'Mặt hàng này không nằm trong danh sách kho đã lấy cho mẻ pha lóc.', [], 422);
        }

        $verifications = collect($batch->picked_material_verifications ?? [])
            ->keyBy(fn ($row) => (int) ($row['variant_id'] ?? 0));

        if ($picked) {
            $verifications->put((int) $variant->id, [
                'variant_id' => (int) $variant->id,
                'quantity' => (float) $sourceItem->quantity,
                'verified_by' => (int) $user->id,
                'verified_by_name' => (string) ($user->name ?? 'Package'),
                'verified_at' => now()->toDateTimeString(),
            ]);
        } else {
            $verifications->forget((int) $variant->id);
        }

        $batch->update([
            'picked_material_verifications' => $verifications->values()->all(),
        ]);

        $sourceVariantIds = $batch->exportDocument?->items
            ?->pluck('product_variant_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values() ?? collect();
        $verifiedVariantIds = $verifications
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique();
        $allMaterialsPicked = $sourceVariantIds->isEmpty() || $sourceVariantIds->diff($verifiedVariantIds)->isEmpty();

        return $this->pickedMaterialResponse($request, true, $picked ? 'Đã xác nhận đã lấy mặt hàng pha lóc.' : 'Đã quay lại trạng thái chưa lấy.', [
            'batch_id' => (int) $batch->id,
            'variant_id' => (int) $variant->id,
            'picked' => $picked,
            'verified_by_name' => (string) ($user->name ?? 'Package'),
            'all_materials_picked' => $allMaterialsPicked,
        ]);
    }

    private function pickedMaterialResponse(Request $request, bool $ok, string $message, array $payload = [], int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge([
                'ok' => $ok,
                'message' => $message,
            ], $payload), $status);
        }

        return back()->with($ok ? 'success' : 'error', $message);
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
