<?php

namespace App\Http\Controllers;

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
        $purchases = ProcurementPurchase::with(['farm', 'supplier', 'creator', 'items'])
            ->whereIn('status', [ProcurementPurchase::STATUS_SENT, ProcurementPurchase::STATUS_RECEIVED])
            ->when(!$user->hasRole('admin') && $user->warehouse_id, fn ($q) => $q->where('warehouse_id', $user->warehouse_id))
            ->latest('sent_to_warehouse_at')->paginate(30);
        return view('warehouse.procurement-receipts', compact('purchases'));
    }

    public function receive(Request $request, ProcurementPurchase $purchase)
    {
        abort_unless($purchase->status === ProcurementPurchase::STATUS_SENT, 422, 'Phiếu không ở trạng thái chờ tiếp nhận.');
        $data = $request->validate(['warehouse_rating' => ['required', 'integer', 'between:1,5'], 'warehouse_condition' => ['required', 'string', 'max:255'], 'warehouse_comment' => ['nullable', 'string', 'max:1000'], 'items' => ['nullable', 'array'], 'items.*.item_type' => ['required', 'in:processed_duck,feathers,offall,offal,reject'], 'items.*.size' => ['nullable', 'numeric', 'min:0'], 'items.*.quantity' => ['required', 'integer', 'min:0'], 'items.*.weight' => ['nullable', 'numeric', 'min:0'], 'items.*.condition' => ['nullable', 'string', 'max:255']]);
        DB::transaction(function () use ($purchase, $data): void {
            $purchase->items()->where('stage', 'received')->delete();
            foreach (($data['items'] ?? []) as $item) {
                if ((int) $item['quantity'] <= 0) continue;
                ProcurementPurchaseItem::create([...$item, 'item_type' => $item['item_type'] === 'offall' ? 'offal' : $item['item_type'], 'procurement_purchase_id' => $purchase->id, 'stage' => 'received']);
            }
            $purchase->update(['status' => ProcurementPurchase::STATUS_RECEIVED, 'received_by' => auth()->id(), 'received_at' => now(), 'warehouse_rating' => $data['warehouse_rating'], 'warehouse_condition' => $data['warehouse_condition'], 'warehouse_comment' => $data['warehouse_comment'] ?? null]);
        });
        return back()->with('success', 'Đã tiếp nhận và đánh giá phiếu thu mua ' . $purchase->code . '.');
    }
}
