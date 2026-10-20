<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OrderAdjustmentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleReturnController extends Controller
{
    public static function eligibleShippers()
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'ship']))
            ->orderBy('name')->get()->filter(fn ($user) => Setting::enabled('shipper_auto_complete_'.$user->id));
    }

    private function owner(Order $order): void
    {
        abort_unless(auth()->user()->hasRole('admin') || (auth()->user()->hasRole('sale') && (int) $order->user_id === (int) auth()->id()), 403);
        abort_unless(in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true), 422, 'Chỉ hoàn trả đơn đã giao hoặc hoàn thành.');
    }

    public function index()
    {
        $user = auth()->user();
        abort_unless($user->hasRole(['admin', 'sale', 'shipper', 'ship', 'manager_shipper', 'warehouse', 'account', 'accountant', 'accounting']), 403);
        $query = OrderReturn::with(['order.customer', 'assignedShipper', 'warehouse', 'returnItems.productVariant.product'])
            ->whereNotNull('assigned_shipper_id');
        if (! $user->hasRole(['admin', 'account', 'accountant', 'accounting', 'manager_shipper'])) {
            $query->where(function ($q) use ($user) {
                $q->whereRaw('1=0');
                if ($user->hasRole('sale')) {
                    $q->orWhere('created_by', $user->id);
                }
                if ($user->hasRole(['shipper', 'ship'])) {
                    $q->orWhere('assigned_shipper_id', $user->id);
                }
                if ($user->hasRole('warehouse') && $user->warehouse_id) {
                    $q->orWhere('warehouse_id', $user->warehouse_id);
                }
            });
        }

        return view('sale-returns.index', ['returns' => $query->latest()->paginate(20)]);
    }

    public function create(Order $order)
    {
        $this->owner($order);
        $order->load('items.variant.product', 'items.product', 'customer');

        return view('sale-returns.create', ['order' => $order, 'shippers' => self::eligibleShippers(), 'warehouses' => Warehouse::orderBy('name')->get()]);
    }

    public function store(Request $request, Order $order)
    {
        $this->owner($order);
        $data = $request->validate([
            'assigned_shipper_id' => 'required|integer', 'warehouse_id' => 'required|exists:warehouses,id',
            'reason' => 'required|string|max:2000', 'evidence_image' => 'required|image|max:5120',
            'items' => 'required|array|min:1', 'items.*.quantity' => 'required|integer|min:0',
        ]);
        abort_unless(self::eligibleShippers()->contains('id', (int) $data['assigned_shipper_id']), 422, 'Shipper chưa được bật quyền điều phối hoàn thành thay.');
        $image = $request->file('evidence_image')->store('orders/returns', 'public');
        try {
            DB::transaction(function () use ($order, $data, $image) {
                $locked = Order::lockForUpdate()->findOrFail($order->id);
                $this->owner($locked);
                $locked->load('items');
                // Reserve quantities across all pending and completed return workflows.
                $returned = ReturnItem::whereHas('orderReturn', fn ($q) => $q->where('order_id', $locked->id)
                    ->whereNotIn('status', ['rejected', 'cancelled']))->get()->groupBy('product_variant_id')->map->sum('quantity');
                $remaining = $locked->items->groupBy('product_variant_id')->map->sum('quantity');
                $lines = [];
                foreach ($data['items'] as $id => $line) {
                    $item = $locked->items->firstWhere('id', (int) $id);
                    if (! $item) {
                        throw ValidationException::withMessages(['items' => 'Sản phẩm không thuộc đơn hàng.']);
                    }
                    $qty = (int) $line['quantity'];
                    if ($qty === 0) {
                        continue;
                    }
                    $variant = $item->product_variant_id;
                    $available = (int) ($remaining[$variant] ?? 0) - (int) ($returned[$variant] ?? 0);
                    if ($qty > (int) $item->quantity || $qty > $available) {
                        throw ValidationException::withMessages(['items' => 'Số lượng trả vượt quá số lượng còn được hoàn trả.']);
                    }
                    $returned[$variant] = (int) ($returned[$variant] ?? 0) + $qty;
                    $lines[] = ['product_variant_id' => $variant, 'quantity' => $qty,
                        'original_weight' => $item->effective_priced_by_kg ? $item->reconciliation_pricing_quantity * $qty / max(1, (float) $item->quantity) : null];
                }
                if (! $lines) {
                    throw ValidationException::withMessages(['items' => 'Chọn ít nhất một sản phẩm hoàn trả.']);
                }
                $return = OrderReturn::create(['order_id' => $locked->id, 'customer_id' => $locked->customer_id,
                    'warehouse_id' => $data['warehouse_id'], 'assigned_shipper_id' => $data['assigned_shipper_id'],
                    'created_by' => auth()->id(), 'reason' => $data['reason'], 'evidence_image_path' => $image,
                    'status' => 'sale_requested', 'refund_amount' => 0]);
                foreach (collect($lines)->groupBy('product_variant_id') as $variantId => $group) {
                    $return->returnItems()->create([
                        'product_variant_id' => $variantId,
                        'quantity' => $group->sum('quantity'),
                        'original_weight' => $group->first()['original_weight'] === null ? null : $group->sum('original_weight'),
                    ]);
                }
                $this->history($return, 'sale_return_requested', 'Sale tạo yêu cầu hoàn trả, chờ shipper hoặc điều phối xác nhận.');
            });
        } catch (\Throwable $error) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($image);
            throw $error;
        }

        return redirect()->route('sale-returns.index')->with('success', 'Đã gửi yêu cầu hoàn trả.');
    }

    public function ship(OrderReturn $orderReturn)
    {
        DB::transaction(function () use ($orderReturn) {
            $return = OrderReturn::lockForUpdate()->findOrFail($orderReturn->id);
            abort_unless($return->assigned_shipper_id && $return->status === 'sale_requested', 422);
            $user = auth()->user();
            abort_unless($user->hasRole('admin') || (int) $return->assigned_shipper_id === (int) $user->id
                || ($user->hasRole('manager_shipper') && Setting::enabled('shipper_auto_complete_'.$return->assigned_shipper_id)), 403);
            $return->update(['status' => 'sale_in_transit', 'ship_confirmed_by' => $user->id, 'ship_confirmed_at' => now()]);
            $this->history($return, 'sale_return_ship_confirmed', 'Đã xác nhận vận chuyển hàng hoàn trả về kho.');
        });

        return back()->with('success', 'Đã xác nhận trả hàng về kho.');
    }

    public function receive(Request $request, OrderReturn $orderReturn)
    {
        $data = $request->validate(['weights' => 'nullable|array', 'weights.*' => 'nullable|numeric|min:0']);
        DB::transaction(function () use ($orderReturn, $data) {
            $return = OrderReturn::lockForUpdate()->findOrFail($orderReturn->id);
            $user = auth()->user();
            abort_unless($user->hasRole('admin') || ($user->hasRole('warehouse') && $user->warehouse_id && (int) $user->warehouse_id === (int) $return->warehouse_id), 403);
            abort_unless($return->assigned_shipper_id && $return->status === 'sale_in_transit', 422, 'Phiếu chưa được shipper xác nhận hoặc kho đã nhận.');
            $order = Order::lockForUpdate()->findOrFail($return->order_id);
            $order->load('items');
            $document = InventoryDocument::create(['type' => 'import', 'warehouse_id' => $return->warehouse_id,
                'document_date' => now()->toDateString(), 'notes' => 'Kho nhận hoàn trả từ sale [order_return:#'.$return->id.']',
                'shipping_fee' => 0, 'user_id' => $user->id]);
            $amount = 0;
            foreach ($return->returnItems as $item) {
                $orderItems = $order->items->where('product_variant_id', $item->product_variant_id);
                $original = $orderItems->first();
                abort_unless($original, 422);
                $weight = $data['weights'][$item->id] ?? null;
                if ($original->effective_priced_by_kg && $weight === null) {
                    throw ValidationException::withMessages(['weights' => 'Nhập khối lượng thực nhận cho hàng tính theo kg.']);
                }
                $basis = $original->effective_priced_by_kg ? (float) $weight : (float) $item->quantity;
                $orderedBasis = $orderItems->sum(fn ($line) => $line->reconciliation_pricing_quantity);
                $lineTotal = $orderItems->sum(fn ($line) => $line->reconciliation_pricing_quantity * (float) $line->price);
                $amount += $orderedBasis > 0 ? $basis * $lineTotal / $orderedBasis : 0;
                $item->update(['received_weight' => $weight, 'weight_confirmed_at' => now()]);
                $inventory = Inventory::firstOrCreate(['warehouse_id' => $return->warehouse_id, 'product_variant_id' => $item->product_variant_id], ['quantity' => 0, 'reserved_quantity' => 0]);
                $inventory->increment('quantity', $item->quantity);
                if ($weight !== null) {
                    $inventory->increment('weight_kg', (float) $weight);
                }
                \App\Models\ProductVariant::whereKey($item->product_variant_id)->update(['stock' => Inventory::where('product_variant_id', $item->product_variant_id)->sum('quantity')]);
                $document->items()->create(['product_variant_id' => $item->product_variant_id, 'quantity' => $item->quantity, 'unit_cost' => $item->quantity > 0 ? $basis * $lineTotal / max(.001, $orderedBasis) / $item->quantity : 0]);
                InventoryMovement::create(['inventory_id' => $inventory->id, 'quantity' => $item->quantity, 'type' => 'import', 'reference_type' => OrderReturn::class, 'reference_id' => $return->id, 'user_id' => $user->id]);
            }
            $return->update(['status' => 'sale_accounting_pending', 'refund_amount' => round($amount, 2), 'warehouse_confirmed_by' => $user->id, 'warehouse_confirmed_at' => now()]);
            $this->history($return, 'sale_return_received', 'Kho đã nhận hàng hoàn trả, chờ kế toán duyệt.');
        });

        return back()->with('success', 'Kho đã nhận hàng. Phiếu đã chuyển kế toán duyệt.');
    }

    public function approve(OrderReturn $orderReturn)
    {
        abort_unless(auth()->user()->hasRole(['admin', 'account', 'accountant', 'accounting']), 403);
        DB::transaction(function () use ($orderReturn) {
            $return = OrderReturn::lockForUpdate()->findOrFail($orderReturn->id);
            abort_unless($return->assigned_shipper_id && $return->status === 'sale_accounting_pending' && $return->warehouse_confirmed_at, 422);
            $order = Order::lockForUpdate()->findOrFail($return->order_id);
            $returnedQty = ReturnItem::whereHas('orderReturn', fn ($q) => $q->where('order_id', $order->id)->where('status', 'warehouse_received'))->sum('quantity');
            $scope = $returnedQty + $return->returnItems()->sum('quantity') >= $order->items()->sum('quantity') ? 'full' : 'partial';
            $return->update(['status' => 'warehouse_received', 'return_scope' => $scope, 'accounting_confirmed_by' => auth()->id(), 'accounting_confirmed_at' => now()]);
            app(OrderAdjustmentSettlementService::class)->sync($order);
            $this->history($return, 'sale_return_accounting_approved', 'Kế toán duyệt hàng hoàn trả: '.number_format((float) $return->refund_amount, 0, ',', '.').'đ.');
        });

        return back()->with('success', 'Kế toán đã duyệt và cập nhật đối soát/công nợ.');
    }

    private function history(OrderReturn $return, string $action, string $note): void
    {
        OrderHistory::create(['order_id' => $return->order_id, 'user_id' => auth()->id(), 'role' => auth()->user()->roles->first()?->name,
            'action' => $action, 'status_before' => $return->order->status, 'status_after' => $return->order->status, 'note' => $note.' Phiếu #'.$return->id]);
    }
}
