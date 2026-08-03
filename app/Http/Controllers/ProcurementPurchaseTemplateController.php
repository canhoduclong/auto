<?php

namespace App\Http\Controllers;

use App\Models\ProcurementPurchaseTemplate;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementPurchaseTemplateController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:150'], 'supplier_id' => ['required','exists:suppliers,id'],
            'items' => ['required','array','min:1'], 'items.*.product_variant_id' => ['required','distinct','exists:product_variants,id'],
            'items.*.quantity' => ['required','numeric','gt:0'], 'items.*.weight' => ['nullable','numeric','min:0'],
        ]);
        $ids = collect($data['items'])->pluck('product_variant_id');
        $valid = ProductVariant::whereIn('id', $ids)->whereHas('product.suppliers', fn ($q) => $q->where('suppliers.id', $data['supplier_id'])->where('supplier_products.active', true))->count();
        if ($valid !== $ids->count()) throw ValidationException::withMessages(['items' => 'Có sản phẩm không thuộc nhà cung cấp đã chọn.']);

        $template = DB::transaction(function () use ($data) {
            $template = ProcurementPurchaseTemplate::create(['name'=>trim($data['name']), 'supplier_id'=>$data['supplier_id'], 'user_id'=>auth()->id()]);
            $template->items()->createMany($data['items']);
            return $template;
        });
        return response()->json(['ok'=>true, 'message'=>'Đã lưu mẫu thu mua.', 'template'=>$this->payload($template)]);
    }

    public function destroy(ProcurementPurchaseTemplate $template): JsonResponse
    {
        abort_unless(auth()->user()->hasRole('admin') || (int)$template->user_id === (int)auth()->id(), 403);
        $template->delete();
        return response()->json(['ok'=>true, 'message'=>'Đã xóa mẫu thu mua.']);
    }

    public static function payload(ProcurementPurchaseTemplate $template): array
    {
        $template->loadMissing(['supplier','items.productVariant.product']);
        return ['id'=>(int)$template->id, 'name'=>$template->name, 'supplier_id'=>(int)$template->supplier_id, 'supplier_name'=>$template->supplier?->name,
            'items'=>$template->items->map(fn($item)=>['product_variant_id'=>(int)$item->product_variant_id, 'quantity'=>(float)$item->quantity, 'weight'=>(float)$item->weight,
                'label'=>trim(($item->productVariant?->product?->name ?? 'Sản phẩm').' - '.($item->productVariant?->name ?? 'Biến thể'))])->values()->all()];
    }
}
