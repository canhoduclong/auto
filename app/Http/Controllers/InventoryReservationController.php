<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reservations = InventoryReservation::with([
            'orderItem.order:id,customer_id,user_id,shipper_id,code,status',
            'orderItem.order.customer:id,name,phone',
            'orderItem.order.user:id,name,short_name',
            'orderItem.order.shipper:id,name,short_name,phone',
            'orderItem.variant.product',
            'inventory.warehouse',
        ])->withSum('recoveryDocuments as recovered_quantity', 'reservation_recovery_quantity')
            ->paginate(10);
        $statusOptions = Order::statusOptions();

        return view('inventory-reservations.index', compact('reservations', 'statusOptions'));
    }

    public function storeRecoveryReceipt(Request $request, InventoryReservation $inventoryReservation)
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Chỉ Admin được lập phiếu nhập cứu hộ reservation.');

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $document = DB::transaction(function () use ($inventoryReservation, $validated, $request): InventoryDocument {
            $reservation = InventoryReservation::query()
                ->with(['orderItem.order', 'orderItem.variant.product'])
                ->lockForUpdate()
                ->findOrFail($inventoryReservation->id);
            $inventory = Inventory::query()->lockForUpdate()->findOrFail($reservation->inventory_id);
            $quantity = round((float) $validated['quantity'], 3);
            $previouslyRecovered = (float) InventoryDocument::query()
                ->where('inventory_reservation_id', $reservation->id)
                ->sum('reservation_recovery_quantity');
            $remainingLimit = round((float) $reservation->quantity - $previouslyRecovered, 3);

            if ($quantity > $remainingLimit + 0.0001) {
                throw ValidationException::withMessages([
                    'quantity' => 'Tổng số nhập cứu hộ không được vượt quá số lượng reservation còn lại ('.number_format(max(0, $remainingLimit), 3, ',', '.').').',
                ]);
            }

            $orderCode = (string) ($reservation->orderItem?->order?->code ?? 'N/A');
            $document = InventoryDocument::query()->create([
                'type' => 'import',
                'warehouse_id' => $inventory->warehouse_id,
                'inventory_reservation_id' => $reservation->id,
                'reservation_recovery_quantity' => $quantity,
                'document_date' => now()->toDateString(),
                'shipping_fee' => 0,
                'user_id' => $request->user()->id,
                'notes' => 'Admin nhập cứu hộ reservation #'.$reservation->id
                    .' của đơn #'.$orderCode.'. Lý do: '.trim($validated['reason']),
            ]);
            $document->items()->create([
                'product_variant_id' => $inventory->product_variant_id,
                'quantity' => $quantity,
                'unit_cost' => 0,
                'note' => 'Bổ sung tồn bảo đảm hàng đã giữ chỗ; không thay đổi reservation.',
            ]);
            InventoryMovement::query()->create([
                'inventory_id' => $inventory->id,
                'quantity' => $quantity,
                'type' => 'reservation_recovery_import',
                'reference_id' => $document->id,
                'reference_type' => InventoryDocument::class,
                'user_id' => $request->user()->id,
            ]);

            $inventory->increment('quantity', $quantity);
            ProductVariant::query()->whereKey($inventory->product_variant_id)->update([
                'stock' => Inventory::query()->where('product_variant_id', $inventory->product_variant_id)->sum('quantity'),
            ]);

            return $document;
        });

        return redirect()->route('inventory-documents.show', $document)
            ->with('success', 'Đã lập phiếu nhập cứu hộ '.$document->document_number.'. Reservation vẫn được giữ nguyên.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
