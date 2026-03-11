<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;

use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function createNewOrderForm(Request $request)
    {
        
        $variantId = $request->input('variant_id');
        
        if (!$variantId) {
            return redirect()->route('orders.index')->with('error', 'No variant ID provided.');
        }

        
        $variant = ProductVariant::with(['product', 'media'])->find($variantId);

        if (!$variant) {
            return redirect()->route('orders.index')->with('error', 'Variant not found.');
        }
        $customers = Customer::paginate(10);

        // IMPORTANT: Set the base path for the pagination links to our AJAX endpoint.
        $customers->setPath(route('orders.ajax_customer_search'));

        return view('orders.create_new', compact('variant', 'customers'));
    }

    public function ajaxCustomerSearch(Request $request)
    {
        $query = Customer::query();

        if ($request->has('search') && $request->input('search') != '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        $customers = $query->paginate(10);

        return response()->json([
            'html' => view('orders._customer_list', compact('customers'))->render()
        ]);
    }

    public function ajaxVariantSearch(Request $request)
    {
        $perPage = $request->input('per_page', 5);
        // Safeguard against excessively large values
        if ($perPage > 50) {
            $perPage = 50;
        }

        $query = ProductVariant::with('product');

        if ($request->has('search') && $request->input('search') != '') {
            $searchTerm = $request->input('search');
            $query->where('sku', 'like', "%{$searchTerm}%")
                  ->orWhereHas('product', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  });
        }

        // Exclude variants that are already in the cart
        if ($request->has('exclude_ids') && is_array($request->input('exclude_ids'))) {
            $query->whereNotIn('id', $request->input('exclude_ids'));
        }

        $variants = $query->paginate($perPage);

        return response()->json([
            'html' => view('orders._variant_search_results', compact('variants'))->render()
        ]);
    }

    public function storeANewOrder(Request $request, ApprovalService $approvalService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $items = collect($request->input('items'))->map(function ($item) {
            return [
                'variant_id' => (int) $item['variant_id'],
                'quantity' => (int) $item['quantity'],
            ];
        })->values()->all();

        try {
            $order = $this->createOrderWithUnifiedStockFlow(
                items: $items,
                orderData: [
                    'customer_id' => (int) $request->input('customer_id'),
                    'user_id' => auth()->id(),
                    'status' => OrderStatus::Pending->value,
                    'payment_status' => PaymentStatus::Unpaid->value,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                ],
                approvalService: $approvalService
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('site.orders.show', $order)->with('success', 'Order created successfully.');
    }
    public function storeNewOrder(Request $request, ApprovalService $approvalService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $items = collect($request->input('items'))->map(function ($item) {
            return [
                'variant_id' => (int) $item['variant_id'],
                'quantity' => (int) $item['quantity'],
            ];
        })->values()->all();

        try {
            $order = $this->createOrderWithUnifiedStockFlow(
                items: $items,
                orderData: [
                    'customer_id' => (int) $request->input('customer_id'),
                    'user_id' => auth()->id(),
                    'status' => OrderStatus::Pending->value,
                    'payment_status' => PaymentStatus::Unpaid->value,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                ],
                approvalService: $approvalService
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
    }

    public function storeFromCart(Request $request, ApprovalService $approvalService)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để tạo đơn hàng.');
        }
        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:50',
            'recipient_address' => 'required|string|max:1000',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.show')->with('error', 'Giỏ hàng trống');
        }

        if (auth()->user()->hasRole('warehouse') && !auth()->user()->warehouse_id) {
            return redirect()->route('cart.show')->with('error', 'Tai khoan warehouse chua duoc assign kho.');
        }

        $items = [];
        foreach ($cart as $variantId => $details) {
            $items[] = [
                'variant_id' => (int) $variantId,
                'quantity' => (int) ($details['quantity'] ?? 0),
                'price' => $details['price'] ?? null,
            ];
        }

        try {
            $order = $this->createOrderWithUnifiedStockFlow(
                items: $items,
                orderData: [
                    'customer_id' => auth()->id() ?? null,
                    'user_id' => auth()->id() ?? null,
                    'recipient_name' => $request->recipient_name,
                    'recipient_phone' => $request->recipient_phone,
                    'recipient_address' => $request->recipient_address,
                    'note' => $request->note,
                    'status' => OrderStatus::Pending->value,
                    'payment_status' => PaymentStatus::Unpaid->value,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                ],
                approvalService: $approvalService
            );
        } catch (\Throwable $e) {
            return redirect()->route('cart.show')->with('error', $e->getMessage());
        }

        session()->forget('cart');

        return redirect()->route('site.orders.show', $order->id)->with('success', 'Đơn hàng đã tạo thành công');
        
    }
    public function test(Request $request)
    {
        echo "oks";
    }
    public function index(Request $request)
    {
        $query = Order::with('customer', 'user', 'transactions', 'approvals.step');

        if ($request->boolean('my_pending_approval') && auth()->check()) {
            $roleNames = auth()->user()->roles()->pluck('name')->map(fn ($role) => strtolower((string) $role))->values();

            if ($roleNames->isNotEmpty()) {
                $query->whereExists(function ($sub) use ($roleNames) {
                    $sub->select(DB::raw(1))
                        ->from('approval_orders as ao')
                        ->join('approval_steps as aps', 'aps.id', '=', 'ao.approval_step_id')
                        ->whereColumn('ao.order_id', 'orders.id')
                        ->where('ao.status', 'pending')
                        ->whereIn(DB::raw('LOWER(aps.role_slug)'), $roleNames->toArray())
                        ->whereNotExists(function ($prev) {
                            $prev->select(DB::raw(1))
                                ->from('approval_orders as ao_prev')
                                ->join('approval_steps as aps_prev', 'aps_prev.id', '=', 'ao_prev.approval_step_id')
                                ->whereColumn('ao_prev.order_id', 'ao.order_id')
                                ->where('ao_prev.status', 'pending')
                                ->whereColumn('aps_prev.step_order', '<', 'aps.step_order');
                        });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filtering
        if ($request->filled('customer_name')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('customer_name') . '%');
            });
        }

        if ($request->filled('phone_number')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->input('phone_number') . '%');
            });
        }
        
        // Statistics
        $statsQuery = clone $query;
        $totalInvoiceAmount = $statsQuery->sum('total');
        $totalPaidAmount = $statsQuery->sum('amount_paid');
        $totalOutstandingAmount = $totalInvoiceAmount - $totalPaidAmount;
        $fullyPaidOrders = (clone $statsQuery)->where('payment_status', 'paid')->count();
        $unpaidOrders = (clone $statsQuery)->where('payment_status', 'unpaid')->count();
        $partiallyPaidOrders = (clone $statsQuery)->where('payment_status', 'partially_paid')->count();

        $orders = $query->latest()->paginate(15);

        $currentStepByOrder = [];
        $canApproveByOrder = [];
        $authUser = auth()->user();
        $authUser?->loadMissing('roles');

        foreach ($orders as $order) {
            $currentStep = $order->approvals
                ->where('status', 'pending')
                ->sortBy(fn ($approval) => $approval->step->step_order ?? PHP_INT_MAX)
                ->first();

            $currentStepByOrder[$order->id] = $currentStep;

            $canApprove = false;
            if ($authUser && $currentStep?->step) {
                $requiredRole = strtolower((string) $currentStep->step->role_slug);
                $canApprove = $authUser->roles->contains(
                    fn ($role) => strtolower((string) $role->name) === $requiredRole
                );
            }

            $canApproveByOrder[$order->id] = $canApprove;
        }

        $users = \App\Models\User::all();
        $statusOptions = collect(OrderStatus::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->name];
        });

        return view('orders.index', compact(
            'orders',
            'users',
            'statusOptions',
            'totalInvoiceAmount',
            'totalPaidAmount',
            'totalOutstandingAmount',
            'fullyPaidOrders',
            'unpaidOrders',
            'partiallyPaidOrders',
            'currentStepByOrder',
            'canApproveByOrder'
        ));
    }

    public function show(Order $order)
    {
        $order->load('items.variant.product', 'customer', 'approvals.step', 'approvals.approver');

        $approvalService = app(ApprovalService::class);
        $currentPendingApproval = $approvalService->getCurrentPendingStep($order);
        $canApproveCurrentStep = auth()->check()
            ? $approvalService->canApproveCurrentStep($order, auth()->user())
            : false;

        return view('orders.show', compact('order', 'currentPendingApproval', 'canApproveCurrentStep'));
    }

    public function confirm(Order $order)
    {
        $this->assertValidTransition($order, ['pending'], 'confirmed');
        $order->update(['status' => 'confirmed']);

        return back()->with('success', 'Don hang da duoc xac nhan.');
    }

    public function picking(Order $order)
    {
        $this->assertValidTransition($order, ['confirmed'], 'picking');
        $order->update(['status' => 'picking']);

        return back()->with('success', 'Don hang da chuyen sang trang thai picking.');
    }

    public function pickup(Order $order)
    {
        $this->assertValidTransition($order, ['picking', 'confirmed'], 'picked_up');

        DB::transaction(function () use ($order) {
            $this->deductReservedStockForOrder($order);
            $order->update(['status' => 'picked_up']);
        });

        return back()->with('success', 'Da tru ton kho thuc te khi shipper lay hang.');
    }

    public function ship(Order $order)
    {
        $this->assertValidTransition($order, ['picked_up'], 'shipping');
        $order->update(['status' => 'shipping']);

        return back()->with('success', 'Don hang dang duoc giao.');
    }

    public function complete(Order $order)
    {
        $this->assertValidTransition($order, ['shipping'], 'completed');
        $order->update(['status' => 'completed']);

        return back()->with('success', 'Don hang da hoan tat.');
    }

    public function cancel(Order $order)
    {
        $this->assertValidTransition($order, ['pending', 'confirmed', 'picking'], 'cancelled');

        DB::transaction(function () use ($order) {
            $this->releaseReservedStockForOrder($order);
            $order->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Don hang da bi huy va da giai phong hang booking.');
    }

    public function toggleStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'nullable|string',
        ]);

        $targetStatus = $request->input('status');
        if (!$targetStatus) {
            $targetStatus = match ($order->status) {
                'pending' => 'confirmed',
                'confirmed' => 'picking',
                'picking' => 'picked_up',
                'picked_up' => 'shipping',
                'shipping' => 'completed',
                default => $order->status,
            };
        }

        return match ($targetStatus) {
            'confirmed' => $this->confirm($order),
            'picking' => $this->picking($order),
            'picked_up' => $this->pickup($order),
            'shipping' => $this->ship($order),
            'completed' => $this->complete($order),
            'cancelled' => $this->cancel($order),
            default => back()->with('error', 'Khong the chuyen trang thai don hang.'),
        };
    }

    private function getManagedWarehouseId(): ?int
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('warehouse')) {
            return null;
        }

        return $user->warehouse_id ? (int) $user->warehouse_id : null;
    }

    private function getAvailableStock(int $variantId, ?int $warehouseId): int
    {
        if ($warehouseId) {
            $inventory = Inventory::where('product_variant_id', $variantId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (!$inventory) {
                return 0;
            }

            return max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
        }

        $availableSum = (int) Inventory::where('product_variant_id', $variantId)
            ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0) as available_sum')
            ->value('available_sum');

        if ($availableSum > 0) {
            return $availableSum;
        }

        return (int) ProductVariant::where('id', $variantId)->value('stock');
    }

    private function syncVariantStockFromInventories(int $variantId): void
    {
        $totalStock = (int) Inventory::where('product_variant_id', $variantId)->sum('quantity');
        ProductVariant::where('id', $variantId)->update(['stock' => $totalStock]);
    }

    private function createOrderWithUnifiedStockFlow(array $items, array $orderData, ApprovalService $approvalService): Order
    {
        if (auth()->check() && auth()->user()->hasRole('warehouse') && !auth()->user()->warehouse_id) {
            throw new \RuntimeException('Tai khoan warehouse chua duoc assign kho.');
        }

        return DB::transaction(function () use ($items, $orderData, $approvalService) {
            $items = collect($items)
                ->filter(fn ($item) => (int) ($item['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw new \RuntimeException('Khong co san pham hop le trong don.');
            }

            $managedWarehouseId = $this->getManagedWarehouseId();
            $variantsData = [];
            $total = 0;

            foreach ($items as $item) {
                $variantId = (int) $item['variant_id'];
                $quantity = (int) $item['quantity'];

                $variant = ProductVariant::with('product')->lockForUpdate()->find($variantId);
                if (!$variant) {
                    throw new \RuntimeException('San pham khong ton tai.');
                }

                $availableQty = $this->getAvailableStock($variantId, $managedWarehouseId);
                if ($availableQty < $quantity) {
                    throw new \RuntimeException('San pham ' . $variant->sku . ' khong du ton kho. Con lai: ' . $availableQty);
                }

                $price = isset($item['price']) && $item['price'] !== null
                    ? (float) $item['price']
                    : (float) ($variant->latestPriceRule?->price ?? 0);

                $variantsData[] = [
                    'variant' => $variant,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'price' => $price,
                ];

                $total += $price * $quantity;
            }

            $order = new Order();
            $order->customer_id = $orderData['customer_id'] ?? null;
            $order->user_id = $orderData['user_id'] ?? auth()->id();
            $order->recipient_name = $orderData['recipient_name'] ?? null;
            $order->recipient_phone = $orderData['recipient_phone'] ?? null;
            $order->recipient_address = $orderData['recipient_address'] ?? null;
            $order->note = $orderData['note'] ?? null;
            $order->status = $orderData['status'] ?? OrderStatus::Pending->value;
            $order->payment_status = $orderData['payment_status'] ?? PaymentStatus::Unpaid->value;
            $order->delivery_status = $orderData['delivery_status'] ?? DeliveryStatus::NotShipped->value;
            $order->total = $total;
            $order->save();

            foreach ($variantsData as $info) {
                $order->items()->create([
                    'product_id' => $info['variant']->product_id,
                    'product_variant_id' => $info['variant_id'],
                    'quantity' => $info['quantity'],
                    'price' => $info['price'],
                    'total' => $info['quantity'] * $info['price'],
                ]);
            }

            // OMS flow: create order => reserve stock only, not deduct on-hand yet.
            $this->reserveStockForOrder($order, $managedWarehouseId);

            $approvalService->initOrderApproval($order);

            return $order;
        });
    }

    private function reserveStockForOrder(Order $order, ?int $managedWarehouseId): void
    {
        $order->loadMissing('items.variant');

        foreach ($order->items as $item) {
            $variantId = (int) $item->product_variant_id;
            $reserveQty = (int) $item->quantity;

            if ($managedWarehouseId) {
                $inventory = Inventory::lockForUpdate()
                    ->where('product_variant_id', $variantId)
                    ->where('warehouse_id', $managedWarehouseId)
                    ->first();

                if (!$inventory) {
                    throw new \RuntimeException('Khong tim thay ton kho de booking cho SKU: ' . ($item->variant->sku ?? $variantId));
                }

                $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                if ($available < $reserveQty) {
                    throw new \RuntimeException('Khong du available stock de booking cho SKU: ' . ($item->variant->sku ?? $variantId));
                }

                $inventory->reserved_quantity += $reserveQty;
                $inventory->save();

                InventoryReservation::create([
                    'order_item_id' => $item->id,
                    'inventory_id' => $inventory->id,
                    'quantity' => $reserveQty,
                ]);
            } else {
                $inventories = Inventory::lockForUpdate()
                    ->where('product_variant_id', $variantId)
                    ->orderByDesc('quantity')
                    ->get();

                if ($inventories->isEmpty()) {
                    throw new \RuntimeException('Chua cau hinh ton kho theo kho cho SKU: ' . ($item->variant->sku ?? $variantId));
                }

                $remaining = $reserveQty;
                foreach ($inventories as $inventory) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                    if ($available <= 0) {
                        continue;
                    }

                    $reservedNow = min($available, $remaining);
                    $inventory->reserved_quantity += $reservedNow;
                    $inventory->save();

                    InventoryReservation::create([
                        'order_item_id' => $item->id,
                        'inventory_id' => $inventory->id,
                        'quantity' => $reservedNow,
                    ]);

                    $remaining -= $reservedNow;
                }

                if ($remaining > 0) {
                    throw new \RuntimeException('Khong du available stock de booking cho SKU: ' . ($item->variant->sku ?? $variantId));
                }
            }
        }
    }

    private function deductReservedStockForOrder(Order $order): void
    {
        $order->loadMissing('items.variant');

        foreach ($order->items as $item) {
            $reservations = InventoryReservation::where('order_item_id', $item->id)->lockForUpdate()->get();

            foreach ($reservations as $reservation) {
                $inventory = Inventory::lockForUpdate()->find($reservation->inventory_id);
                if (!$inventory) {
                    continue;
                }

                if ($inventory->quantity < $reservation->quantity || $inventory->reserved_quantity < $reservation->quantity) {
                    throw new \RuntimeException('Du lieu ton kho booking khong hop le de tru kho.');
                }

                $inventory->quantity -= $reservation->quantity;
                $inventory->reserved_quantity -= $reservation->quantity;
                $inventory->save();

                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => -$reservation->quantity,
                    'type' => 'export',
                    'reference_id' => $order->id,
                    'reference_type' => Order::class,
                    'user_id' => auth()->id(),
                ]);

                $this->syncVariantStockFromInventories((int) $inventory->product_variant_id);
            }

            InventoryReservation::where('order_item_id', $item->id)->delete();
        }
    }

    private function releaseReservedStockForOrder(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $reservations = InventoryReservation::where('order_item_id', $item->id)->lockForUpdate()->get();
            foreach ($reservations as $reservation) {
                $inventory = Inventory::lockForUpdate()->find($reservation->inventory_id);
                if ($inventory) {
                    $inventory->reserved_quantity = max(0, (int) $inventory->reserved_quantity - (int) $reservation->quantity);
                    $inventory->save();
                }
            }

            InventoryReservation::where('order_item_id', $item->id)->delete();
        }
    }

    private function assertValidTransition(Order $order, array $allowedCurrentStatuses, string $targetStatus): void
    {
        if (!in_array((string) $order->status, $allowedCurrentStatuses, true)) {
            abort(422, 'Khong the chuyen trang thai tu ' . $order->status . ' sang ' . $targetStatus . '.');
        }
    }
}