<?php

namespace App\Http\Controllers;

use App\Models\InventoryDocumentTemplate;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryDocumentTemplateController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $warehouseId = (int) (Auth::user()?->warehouse_id ?? 0);
        if ($warehouseId <= 0) {
            abort(403, 'Bạn chưa được gán kho quản lý.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|distinct|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $variantIds = collect($validated['items'])
            ->pluck('product_variant_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validVariantCount = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->whereHas('product.suppliers', function ($query) use ($validated) {
                $query->where('suppliers.id', $validated['supplier_id'])
                    ->where('supplier_products.active', true);
            })
            ->count();

        if ($validVariantCount !== count($variantIds)) {
            throw ValidationException::withMessages([
                'items' => 'Mẫu có sản phẩm không thuộc nhà cung cấp đã chọn.',
            ]);
        }

        $template = DB::transaction(function () use ($validated, $warehouseId) {
            $template = InventoryDocumentTemplate::create([
                'warehouse_id' => $warehouseId,
                'supplier_id' => $validated['supplier_id'],
                'user_id' => Auth::id(),
                'name' => trim($validated['name']),
            ]);

            $template->items()->createMany($validated['items']);

            return $template;
        });

        return response()->json([
            'ok' => true,
            'message' => 'Đã lưu mẫu phiếu nhập kho.',
            'template' => $this->payload($template),
        ]);
    }

    public function destroy(InventoryDocumentTemplate $template): JsonResponse
    {
        $this->assertManagedTemplate($template);
        $template->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Đã xóa mẫu phiếu nhập kho.',
        ]);
    }

    private function assertManagedTemplate(InventoryDocumentTemplate $template): void
    {
        if ((int) $template->warehouse_id !== (int) (Auth::user()?->warehouse_id ?? 0)) {
            abort(403);
        }
    }

    private function payload(InventoryDocumentTemplate $template): array
    {
        $template->loadMissing(['supplier', 'items.productVariant.product']);

        return [
            'id' => (int) $template->id,
            'name' => (string) $template->name,
            'supplier_id' => (int) $template->supplier_id,
            'supplier_name' => (string) ($template->supplier?->name ?? ''),
            'items' => $template->items->map(fn ($item) => [
                'product_variant_id' => (int) $item->product_variant_id,
                'quantity' => (int) $item->quantity,
                'label' => trim(
                    ($item->productVariant?->product?->name ?? 'Sản phẩm')
                    . ' - '
                    . ($item->productVariant?->name ?? 'Biến thể')
                ),
            ])->values()->all(),
        ];
    }
}
