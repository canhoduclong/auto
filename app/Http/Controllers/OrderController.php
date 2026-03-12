<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\ProductVariant;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private function hasAnyRole(User $user, array $roles): bool
    {
        $roleNames = $user->roles->pluck('name')->map(fn ($name) => strtolower((string) $name))->all();
        foreach ($roles as $role) {
            if (in_array(strtolower($role), $roleNames, true)) {
                return true;
            }
        }

        return false;
    }

    private function currentRoleLabel(?User $user = null): ?string
    {
        $actor = $user ?: auth()->user();
        if (!$actor) {
            return null;
        }

        return $actor->roles->pluck('name')->first();
    }

    private function logOrderHistory(Order $order, string $action, ?string $statusBefore, ?string $statusAfter, ?string $note = null, ?User $user = null): void
    {
        $actor = $user ?: auth()->user();

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => $action,
            'user_id' => $actor?->id,
            'role' => $this->currentRoleLabel($actor),
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'note' => $note,
        ]);
    }

    private function applyRoleScope(Builder $query, User $user): void
    {
        if ($this->hasAnyRole($user, ['admin'])) {
            return;
        }

        if ($this->hasAnyRole($user, ['sale'])) {
            $query->where('user_id', $user->id);
            return;
        }

        if ($this->hasAnyRole($user, ['leader_sale', 'leader', 'sale_manager'])) {
            $teamId = $user->team_id;
            if (!$teamId) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->where(function (Builder $sub) use ($user) {
                $sub->where('status', 'pending_leader_approval')
                    ->orWhereHas('approvals', function (Builder $q) use ($user) {
                        $q->where('approved_by', $user->id);
                    })
                    ->orWhereHas('user.roles', function (Builder $q) {
                        $q->where('name', 'sale');
                    });
            });

            $query->whereHas('user', function (Builder $q) use ($teamId) {
                $q->where('team_id', $teamId);
            });
            return;
        }

        if ($this->hasAnyRole($user, ['manager_sale', 'manager', 'director'])) {
            $teamId = $user->team_id;
            if (!$teamId) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->where(function (Builder $sub) use ($user) {
                $sub->whereIn('status', ['pending_manager_approval', 'approved', 'packing', 'packed', 'shipping', 'delivered', 'completed'])
                    ->orWhereHas('approvals', function (Builder $q) use ($user) {
                        $q->where('approved_by', $user->id);
                    });
            });

            $query->whereHas('user', function (Builder $q) use ($teamId) {
                $q->where('team_id', $teamId);
            });
            return;
        }

        if ($this->hasAnyRole($user, ['warehouse'])) {
            $query->whereIn('status', ['pending_warehouse_approval', 'approved', 'packing', 'packed']);
            return;
        }

        if ($this->hasAnyRole($user, ['shipper'])) {
            $query->whereIn('status', ['pending_shipper_approval', 'packed', 'shipping', 'delivered', 'completed', 'returned']);
            return;
        }

        $query->where('user_id', $user->id);
    }

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

        $authUser = auth()->user();
        $customerQuery = Customer::query()->where('user_id', $authUser->id);
        if (!empty($authUser->email)) {
            $customerQuery->orWhere('email', $authUser->email);
        }

        $customer = $customerQuery->first();

        if (!$customer) {
            $customer = Customer::create([
                'user_id' => $authUser->id,
                'name' => $authUser->name,
                'email' => $authUser->email,
                'phone' => $request->recipient_phone,
                'address' => $request->recipient_address,
            ]);
        } else {
            $customer->fill([
                'user_id' => $customer->user_id ?: $authUser->id,
                'name' => $customer->name ?: $authUser->name,
                'phone' => $customer->phone ?: $request->recipient_phone,
                'address' => $customer->address ?: $request->recipient_address,
            ]);

            if (empty($customer->email) && !empty($authUser->email)) {
                $customer->email = $authUser->email;
            }

            $customer->save();
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
                    'customer_id' => $customer->id,
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

        if (auth()->check()) {
            $this->applyRoleScope($query, auth()->user());
        }

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

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('team_id')) {
            $teamId = (int) $request->input('team_id');
            $query->whereHas('user', function (Builder $sub) use ($teamId) {
                $sub->where('team_id', $teamId);
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
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

        $users = User::orderBy('name')->get();
        $teams = Team::orderBy('name')->get(['id', 'name']);
        $statusOptions = collect(OrderStatus::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->name];
        });

        return view('orders.index', compact(
            'orders',
            'users',
            'teams',
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
        $order->load('items.variant.product', 'customer', 'approvals.step', 'approvals.approver', 'transactions', 'histories.user');

        $approvalService = app(ApprovalService::class);
        $currentPendingApproval = $approvalService->getCurrentPendingStep($order);
        $canApproveCurrentStep = auth()->check()
            ? $approvalService->canApproveCurrentStep($order, auth()->user())
            : false;

        $authUser = auth()->user();
        $canWarehouse = $authUser ? $this->hasAnyRole($authUser, ['warehouse', 'admin']) : false;
        $canShipper = $authUser ? $this->hasAnyRole($authUser, ['shipper', 'admin']) : false;

        $statusLabels = [
            'pending_leader_approval' => 'Pending Leader Approval',
            'pending_manager_approval' => 'Pending Manager Approval',
            'approved' => 'Approved',
            'packing' => 'Warehouse Dang Dong Hang',
            'packed' => 'Packed',
            'shipping' => 'Dang Giao Hang',
            'delivered' => 'Da Giao Hang',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'returned' => 'Returned',
            'cancelled' => 'Cancelled',
        ];

        return view('orders.show', compact('order', 'currentPendingApproval', 'canApproveCurrentStep', 'canWarehouse', 'canShipper', 'statusLabels'));
    }

    public function confirm(Order $order)
    {
        $this->assertValidTransition($order, ['pending'], 'confirmed');
        $order->update(['status' => 'confirmed']);

        return back()->with('success', 'Don hang da duoc xac nhan.');
    }

    public function picking(Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['warehouse', 'admin'])) {
            abort(403, 'Chi co kho duoc phep xac nhan dong hang.');
        }

        $this->assertValidTransition($order, ['approved'], 'packing');
        $statusBefore = (string) $order->status;
        $order->update(['status' => 'packing']);
        $this->logOrderHistory($order, 'warehouse_confirm_pack', $statusBefore, 'packing', 'Kho xac nhan bat dau dong hang');

        return back()->with('success', 'Kho da xac nhan bat dau dong hang.');
    }

    public function completePacking(Request $request, Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['warehouse', 'admin'])) {
            abort(403, 'Chi co kho duoc phep hoan tat dong hang.');
        }

        $request->validate([
            'packed_image' => 'required|image|max:5120',
            'note' => 'nullable|string|max:1000',
        ]);

        $this->assertValidTransition($order, ['packing'], 'packed');
        $statusBefore = (string) $order->status;
        $packedImagePath = $request->file('packed_image')->store('orders/packed', 'public');

        $order->update([
            'status' => 'packed',
            'packed_image_path' => $packedImagePath,
        ]);

        $this->logOrderHistory(
            $order,
            'warehouse_complete_packing',
            $statusBefore,
            'packed',
            $request->input('note')
        );

        return back()->with('success', 'Da hoan thien dong hang. Don san sang cho shipper lay.');
    }

    public function pickup(Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, 'Chi shipper duoc phep lay hang.');
        }

        $this->assertValidTransition($order, ['packed'], 'shipping');
        $statusBefore = (string) $order->status;

        DB::transaction(function () use ($order) {
            $this->deductReservedStockForOrder($order);
            $order->update([
                'status' => 'shipping',
                'delivery_status' => DeliveryStatus::Shipping->value,
            ]);
        });

        $this->logOrderHistory($order, 'shipper_pickup', $statusBefore, 'shipping', 'Shipper da lay hang');

        return back()->with('success', 'Shipper da lay hang va don chuyen sang dang giao hang.');
    }

    public function ship(Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, 'Chi shipper duoc phep cap nhat giao hang.');
        }

        $this->assertValidTransition($order, ['packed'], 'shipping');
        $statusBefore = (string) $order->status;
        $order->update([
            'status' => 'shipping',
            'delivery_status' => DeliveryStatus::Shipping->value,
        ]);
        $this->logOrderHistory($order, 'shipper_start_shipping', $statusBefore, 'shipping', 'Shipper bat dau giao hang');

        return back()->with('success', 'Don hang dang duoc giao.');
    }

    public function markDelivered(Request $request, Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, 'Chi shipper duoc phep xac nhan da giao hang.');
        }

        $request->validate([
            'delivered_image' => 'required|image|max:5120',
            'note' => 'nullable|string|max:1000',
        ]);

        $this->assertValidTransition($order, ['shipping'], 'delivered');
        $statusBefore = (string) $order->status;
        $deliveredImagePath = $request->file('delivered_image')->store('orders/delivered', 'public');

        $order->update([
            'status' => 'delivered',
            'delivery_status' => DeliveryStatus::Delivered->value,
            'delivered_image_path' => $deliveredImagePath,
        ]);

        $this->logOrderHistory($order, 'shipper_delivered', $statusBefore, 'delivered', $request->input('note'));

        return back()->with('success', 'Da xac nhan giao hang thanh cong.');
    }

    public function completePayment(Request $request, Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, 'Chi shipper duoc phep hoan tat thanh toan.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'receipt_image' => 'required|image|max:5120',
            'delivery_image' => 'nullable|image|max:5120',
            'note' => 'nullable|string|max:255',
        ]);

        $this->assertValidTransition($order, ['delivered'], 'completed');

        $netPaid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
            - (float) $order->transactions()->where('type', 'refund')->sum('amount');
        $amount = (float) $request->input('amount');
        $totalAfterPayment = $netPaid + $amount;

        if ($totalAfterPayment + 0.0001 < (float) $order->total) {
            return back()->with('error', 'Can thanh toan du de hoan tat don hang.');
        }

        $receiptImagePath = $request->file('receipt_image')->store('orders/receipts', 'public');
        $deliveryImagePath = $request->hasFile('delivery_image')
            ? $request->file('delivery_image')->store('orders/delivery-proof', 'public')
            : $order->delivered_image_path;

        $statusBefore = (string) $order->status;

        DB::transaction(function () use ($order, $amount, $request, $receiptImagePath, $deliveryImagePath) {
            Transaction::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'amount' => $amount,
                'type' => 'payment',
                'method' => 'shipper_collection',
                'note' => $request->input('note'),
                'receipt_image_path' => $receiptImagePath,
                'delivery_image_path' => $deliveryImagePath,
            ]);

            $netPaid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
                - (float) $order->transactions()->where('type', 'refund')->sum('amount');

            $order->update([
                'status' => 'completed',
                'payment_status' => PaymentStatus::Paid->value,
                'amount_paid' => $netPaid,
                'amount_due' => max((float) $order->total - $netPaid, 0),
                'delivered_image_path' => $deliveryImagePath,
            ]);
        });

        $this->logOrderHistory($order->fresh(), 'shipper_complete_payment', $statusBefore, 'completed', $request->input('note'));

        return back()->with('success', 'Da ghi nhan thanh toan va hoan tat don hang.');
    }

    public function refund(Order $order)
    {
        if (!auth()->check() || !$this->hasAnyRole(auth()->user(), ['shipper', 'admin'])) {
            abort(403, 'Chi shipper duoc phep tao yeu cau hoan tra.');
        }

        if (!in_array((string) $order->status, ['delivered', 'shipping'], true)) {
            return back()->with('error', 'Chi duoc tao refund sau khi don da giao hoac dang giao.');
        }

        $statusBefore = (string) $order->status;
        $order->update(['has_return_order' => true]);
        $this->logOrderHistory($order, 'shipper_refund_request', $statusBefore, $statusBefore, 'Tao don hoan tra tu don goc');

        return redirect()->route('order-returns.create', ['order_id' => $order->id]);
    }

    public function complete(Order $order)
    {
        $this->assertValidTransition($order, ['shipping', 'delivered'], 'completed');
        $statusBefore = (string) $order->status;
        $order->update(['status' => 'completed']);
        $this->logOrderHistory($order, 'manual_complete', $statusBefore, 'completed', 'Cap nhat hoan tat thu cong');

        return back()->with('success', 'Don hang da hoan tat.');
    }

    public function cancel(Order $order)
    {
        $this->assertValidTransition($order, ['pending_leader_approval', 'pending_manager_approval', 'approved', 'packing', 'pending', 'confirmed', 'picking'], 'cancelled');
        $statusBefore = (string) $order->status;

        DB::transaction(function () use ($order) {
            $this->releaseReservedStockForOrder($order);
            $order->update(['status' => 'cancelled']);
        });

        $this->logOrderHistory($order, 'cancel_order', $statusBefore, 'cancelled', 'Huy don hang va giai phong booking');

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

            $order->refresh();
            $this->logOrderHistory($order, 'create_order', null, (string) $order->status, 'Sale tao don hang');

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