<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\ProcurementPurchase;
use App\Models\ProcurementPurchaseItem;
use App\Services\SupplierDebtService;
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
            ->when(!$user->hasRole('admin'), fn ($q) => $q->where('warehouse_id', $user->warehouse_id ?? 0))
            ->latest('sent_to_warehouse_at')->paginate(30);
        return view('warehouse.procurement-receipts', compact('purchases'));
    }

    public function receive(Request $request, ProcurementPurchase $purchase)
    {
        abort_unless($purchase->status === ProcurementPurchase::STATUS_SENT, 422, 'Phiếu không ở trạng thái chờ tiếp nhận.');
        $data = $request->validate(['warehouse_rating' => ['required', 'integer', 'between:1,5'], 'warehouse_condition' => ['required', 'string', 'max:255'], 'warehouse_comment' => ['nullable', 'string', 'max:1000'], 'items' => ['nullable', 'array'], 'items.*.item_type' => ['required', 'in:processed_duck,feathers,offall,offal,reject'], 'items.*.size' => ['nullable', 'numeric', 'min:0'], 'items.*.quantity' => ['required', 'integer', 'min:0'], 'items.*.weight' => ['nullable', 'numeric', 'min:0'], 'items.*.condition' => ['nullable', 'string', 'max:255'], 'product_items'=>['nullable','array'], 'product_items.*.id'=>['required','integer','distinct'], 'product_items.*.received_quantity'=>['required','numeric','min:0'], 'product_items.*.received_weight'=>['required','numeric','min:0'], 'product_items.*.condition'=>['nullable','string','max:255']]);
        DB::transaction(function () use ($purchase, $data, $request): void {
            $purchase = ProcurementPurchase::lockForUpdate()->findOrFail($purchase->id);
            abort_unless($request->user()->hasRole('admin') || ($request->user()->warehouse_id && (int)$request->user()->warehouse_id === (int)$purchase->warehouse_id), 403, 'Bạn không thuộc kho nhận hàng.');
            abort_unless($purchase->status === ProcurementPurchase::STATUS_SENT, 422, 'Phiếu đã được tiếp nhận.');
            if ($purchase->entry_mode === 'product_lines') {
                abort_if($purchase->inventory_document_id, 422, 'Phiếu thu mua đã tạo phiếu nhập kho.');
                abort_if(!$purchase->warehouse_id, 422, 'Phiếu chưa chọn kho nhận.');
                abort_if($purchase->payment_transaction_id, 422, 'Cần xử lý phiếu thanh toán đã liên kết trước khi chốt lại giá trị thực nhận.');
                $submitted = collect($data['product_items'] ?? [])->keyBy('id');
                $productItems = $purchase->productItems()->with('productVariant')->get();
                abort_if($submitted->isEmpty() || $submitted->count() !== $productItems->count()
                    || $productItems->pluck('id')->diff($submitted->keys())->isNotEmpty(), 422, 'Danh sách sản phẩm thực nhận không hợp lệ.');
                $subtotal = 0;
                foreach ($productItems as $productItem) {
                    $received = $submitted[$productItem->id];
                    $qty = (float)$received['received_quantity'];
                    $weight = (float)$received['received_weight'];
                    abort_if(($qty > 0 && $weight <= 0) || ($qty <= 0 && $weight > 0), 422, 'Số lượng và kg thực nhận phải cùng lớn hơn 0, hoặc cùng bằng 0 nếu không giao.');
                    $lineTotal = round($weight * (float)$productItem->unit_cost, 2);
                    $subtotal += $lineTotal;
                    $productItem->update(['received_quantity'=>$qty, 'received_weight'=>$weight, 'line_total'=>$lineTotal, 'condition'=>$received['condition'] ?? null]);
                }
                $vat = round($subtotal * (float)$purchase->vat_rate / 100, 2);
                $purchase->fill(['subtotal'=>$subtotal, 'vat_amount'=>$vat,
                    'total_amount'=>$subtotal + $vat + (float)$purchase->other_fee + (float)$purchase->transportation_fee
                        + (float)$purchase->broker_fee + (float)$purchase->processing_fee + (float)$purchase->procurement_fee]);
                $document = InventoryDocument::create(['type'=>'import', 'document_date'=>now()->toDateString(),
                    'warehouse_id'=>$purchase->warehouse_id, 'supplier_id'=>$purchase->supplier_id,
                    'shipping_fee'=>(float)$purchase->other_fee + (float)$purchase->transportation_fee + (float)$purchase->broker_fee + (float)$purchase->processing_fee + (float)$purchase->procurement_fee,
                    'vat_amount'=>$vat, 'notes'=>'Nhập từ phiếu thu mua '.$purchase->code, 'user_id'=>auth()->id()]);
                foreach ($productItems as $productItem) {
                    $qty = (float)$productItem->received_quantity;
                    $weight = (float)$productItem->received_weight;
                    if ($qty <= 0) continue;
                    $document->items()->create(['product_variant_id'=>$productItem->product_variant_id, 'quantity'=>$qty,
                        'weight_kg'=>$weight, 'unit_cost'=>$productItem->unit_cost, 'unit_cost_basis'=>'kg',
                        'source_price_id'=>$productItem->source_price_id, 'note'=>$productItem->condition ?? $productItem->note]);
                    $inventory = Inventory::firstOrCreate(['product_variant_id'=>$productItem->product_variant_id, 'warehouse_id'=>$purchase->warehouse_id], ['quantity'=>0, 'weight_kg'=>0]);
                    DB::table('inventories')->where('id', $inventory->id)->update([
                        'quantity'=>DB::raw('quantity + '.$qty), 'weight_kg'=>DB::raw('COALESCE(weight_kg, 0) + '.$weight), 'updated_at'=>now(),
                    ]);
                    InventoryMovement::create(['inventory_id'=>$inventory->id, 'quantity'=>$qty, 'weight_kg'=>$weight,
                        'type'=>'import', 'reference_id'=>$document->id, 'reference_type'=>InventoryDocument::class, 'user_id'=>auth()->id()]);
                }
                $purchase->inventory_document_id = $document->id;
                // Preserve all recorded payments; only the remaining debt changes with actual delivery.
                app(SupplierDebtService::class)->recalculate($purchase);
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
