<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\ProcurementPurchase;
use App\Models\ProcurementPurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseProcurementController extends Controller
{
    public function __construct() { $this->middleware(['auth', 'role:warehouse,admin']); }

    public function index(Request $request)
    {
        $user = $request->user();
        $purchases = ProcurementPurchase::with(['farm', 'supplier', 'creator', 'items', 'productItems.productVariant.product', 'inventoryDocument'])
            ->whereIn('status', [ProcurementPurchase::STATUS_SENT, ProcurementPurchase::STATUS_RECEIVED])
            ->when(!$user->hasRole('admin') && $user->warehouse_id, fn ($q) => $q->where('warehouse_id', $user->warehouse_id))
            ->latest('sent_to_warehouse_at')->paginate(30);
        return view('warehouse.procurement-receipts', compact('purchases'));
    }

    public function receive(Request $request, ProcurementPurchase $purchase)
    {
        abort_unless($purchase->status === ProcurementPurchase::STATUS_SENT, 422, 'Phiếu không ở trạng thái chờ tiếp nhận.');
        $data = $request->validate(['warehouse_rating' => ['required', 'integer', 'between:1,5'], 'warehouse_condition' => ['required', 'string', 'max:255'], 'warehouse_comment' => ['nullable', 'string', 'max:1000'], 'items' => ['nullable', 'array'], 'items.*.item_type' => ['required', 'in:processed_duck,feathers,offall,offal,reject'], 'items.*.size' => ['nullable', 'numeric', 'min:0'], 'items.*.quantity' => ['required', 'integer', 'min:0'], 'items.*.weight' => ['nullable', 'numeric', 'min:0'], 'items.*.condition' => ['nullable', 'string', 'max:255'], 'product_items'=>['nullable','array'], 'product_items.*.id'=>['required','integer'], 'product_items.*.received_quantity'=>['required','numeric','min:0'], 'product_items.*.received_weight'=>['nullable','numeric','min:0'], 'product_items.*.condition'=>['nullable','string','max:255']]);
        DB::transaction(function () use ($purchase, $data): void {
            if ($purchase->entry_mode === 'product_lines') {
                abort_if($purchase->inventory_document_id, 422, 'Phiếu thu mua đã tạo phiếu nhập kho.');
                $submitted = collect($data['product_items'] ?? [])->keyBy('id');
                abort_if($submitted->isEmpty() || $purchase->productItems()->whereIn('id', $submitted->keys())->count() !== $purchase->productItems()->count(), 422, 'Danh sách sản phẩm thực nhận không hợp lệ.');
                $document = InventoryDocument::create(['type'=>'import', 'document_date'=>now()->toDateString(), 'warehouse_id'=>$purchase->warehouse_id, 'supplier_id'=>$purchase->supplier_id, 'shipping_fee'=>$purchase->transportation_fee, 'notes'=>'Nhập từ phiếu thu mua '.$purchase->code, 'user_id'=>auth()->id()]);
                foreach ($purchase->productItems()->with('productVariant')->get() as $productItem) {
                    $received = $submitted[$productItem->id]; $qty=(float)$received['received_quantity'];
                    $productItem->update(['received_quantity'=>$qty, 'received_weight'=>(float)($received['received_weight'] ?? 0), 'condition'=>$received['condition'] ?? null]);
                    if ($qty <= 0) continue;
                    $documentItem = $document->items()->create(['product_variant_id'=>$productItem->product_variant_id, 'quantity'=>$qty, 'unit_cost'=>$productItem->unit_cost, 'source_price_id'=>$productItem->source_price_id, 'note'=>$received['condition'] ?? $productItem->note]);
                    $inventory = Inventory::firstOrCreate(['product_variant_id'=>$productItem->product_variant_id, 'warehouse_id'=>$purchase->warehouse_id], ['quantity'=>0]);
                    $inventory->increment('quantity', $qty);
                    InventoryMovement::create(['inventory_id'=>$inventory->id, 'quantity'=>$qty, 'type'=>'import', 'reference_id'=>$document->id, 'reference_type'=>InventoryDocument::class, 'user_id'=>auth()->id()]);
                }
                $purchase->inventory_document_id = $document->id;
            } else {
                $purchase->items()->where('stage', 'received')->delete();
                foreach (($data['items'] ?? []) as $item) {
                    if ((int) $item['quantity'] <= 0) continue;
                    ProcurementPurchaseItem::create([...$item, 'item_type' => $item['item_type'] === 'offall' ? 'offal' : $item['item_type'], 'procurement_purchase_id' => $purchase->id, 'stage' => 'received']);
                }
            }
            $purchase->forceFill(['status' => ProcurementPurchase::STATUS_RECEIVED, 'received_by' => auth()->id(), 'received_at' => now(), 'warehouse_rating' => $data['warehouse_rating'], 'warehouse_condition' => $data['warehouse_condition'], 'warehouse_comment' => $data['warehouse_comment'] ?? null])->save();
        });
        return back()->with('success', 'Đã tiếp nhận và đánh giá phiếu thu mua ' . $purchase->code . '.');
    }
}
