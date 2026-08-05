<?php

namespace App\Http\Controllers;

use App\Models\ApprovalWorkflow;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\OrderAdjustmentItem;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use App\Models\ReturnItem;
use App\Models\Warehouse;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\OrderAutoApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;


class OrderAdjustmentController extends Controller
{
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless($user->canManageOrderAdjustments(), 403, 'Chỉ Leader hoặc Manager được truy cập Fix số liệu.');

        $managerRoles = ['manager', 'manager_sale', 'director'];
        $leaderRoles = ['leader', 'leader_sale', 'sale_manager'];
        $isAdminOrManager = $user->hasRole('admin') || $user->hasRole($managerRoles);
        $isLeader = $user->hasRole($leaderRoles);
        $status = (string) $request->input('status', OrderAdjustment::STATUS_PENDING_APPROVAL);
        $keyword = trim((string) $request->input('keyword', ''));

        $query = OrderAdjustment::query()
            ->with([
                'order.customer:id,name,customer_code',
                'order.user:id,name,team_id',
                'requester:id,name',
                'items.variant.product:id,name',
                'approvalSteps.step:id,role_slug,step_order',
                'approvalSteps.approver:id,name',
            ])
            ->whereHas('order', fn ($orderQuery) => $orderQuery->where('status', Order::STATUS_COMPLETED));

        if (!$isAdminOrManager) {
            if ($isLeader) {
                $query->whereHas('order', function ($orderQuery) use ($user): void {
                    $orderQuery->where(function ($scope) use ($user): void {
                        $scope->where('user_id', $user->id);
                        if ((int) ($user->team_id ?? 0) > 0) {
                            $scope->orWhereHas('user', fn ($saleQuery) => $saleQuery->where('team_id', $user->team_id));
                        }
                    });
                });
            } else {
                $query->where('requested_by', $user->id);
            }
        }

        if ($status !== '' && in_array($status, [
            OrderAdjustment::STATUS_DRAFT,
            OrderAdjustment::STATUS_PENDING_APPROVAL,
            OrderAdjustment::STATUS_APPROVED,
            OrderAdjustment::STATUS_REJECTED,
            OrderAdjustment::STATUS_COMPLETED,
        ], true)) {
            $query->where('status', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($scope) use ($keyword): void {
                $scope->where('id', $keyword)
                    ->orWhereHas('order', function ($orderQuery) use ($keyword): void {
                        $orderQuery->where('code', 'like', "%{$keyword}%")
                            ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$keyword}%"));
                    });
            });
        }

        $adjustments = $query
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(20)
            ->appends($request->query());

        $approvalService = app(ApprovalService::class);
        $currentSteps = [];
        $canApproveByAdjustment = [];
        foreach ($adjustments as $adjustment) {
            $currentSteps[$adjustment->id] = $approvalService->getCurrentPendingAdjustmentStep($adjustment);
            $canApproveByAdjustment[$adjustment->id] = $adjustment->status === OrderAdjustment::STATUS_PENDING_APPROVAL
                && ($user->hasRole('admin') || $approvalService->canApproveAdjustmentStep($adjustment, $user));
        }

        return view('site.orders.adjustments.index', [
            'adjustments' => $adjustments,
            'currentSteps' => $currentSteps,
            'canApproveByAdjustment' => $canApproveByAdjustment,
            'status' => $status,
            'keyword' => $keyword,
            'settings' => $this->settings,
        ]);
    }

    public function create(Order $order): View
    {
        $this->authorizeCreate($order);
        $settings   = $this->settings;
        $order->load([
            'customer.addresses',
            'items.product.avatar.media',
            'items.variant.avatar.media',
            'items.variant.product',
        ]);
        $warehouses = Warehouse::query()->orderBy('name')->get();

        return view('site.orders.adjustments.create', [
            'order' => $order,
            'warehouses' => $warehouses,
            'settings' => $settings,
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeCreate($order);

        $data = $request->validate([
            'action' => 'required|in:draft,submit',
            'adjustment_note' => 'nullable|string|max:5000',
            'return_warehouse_id' => 'nullable|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'nullable|required_without:items.*.product_variant_id|exists:order_items,id',
            'items.*.product_variant_id' => 'nullable|required_without:items.*.order_item_id|exists:product_variants,id',
            'items.*.adjusted_quantity' => 'required|integer|min:0',
            'items.*.adjusted_price' => 'required|numeric|min:0',
            'items.*.adjusted_weight' => 'nullable|numeric|min:0',
            'items.*.note' => 'nullable|string|max:1000',
            'evidence_images' => 'nullable|array|max:8',
            'evidence_images.*' => 'image|max:5120',
        ]);

        $order->load(['items.variant.product']);
        $orderItems = $order->items->keyBy('id');
        $newVariantIds = collect($data['items'])
            ->filter(fn (array $itemData): bool => empty($itemData['order_item_id']))
            ->pluck('product_variant_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();
        $newVariants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $newVariantIds->unique()->all())
            ->where('status', true)
            ->whereHas('product', fn ($query) => $query->where('status', true))
            ->get()
            ->keyBy('id');

        $requiresReturn = false;
        $requiresWarehouse = false;
        $preparedItems = [];
        $seenNewVariantIds = [];
        foreach ($data['items'] as $itemData) {
            $orderItemId = (int) ($itemData['order_item_id'] ?? 0);
            $orderItem = $orderItemId > 0 ? $orderItems->get($orderItemId) : null;

            if ($orderItemId > 0 && !$orderItem) {
                return back()->withErrors(['items' => 'Có sản phẩm không thuộc đơn gốc.'])->withInput();
            }

            if (!$orderItem) {
                $variantId = (int) ($itemData['product_variant_id'] ?? 0);
                $variant = $newVariants->get($variantId);
                if (!$variant) {
                    return back()->withErrors(['items' => 'Loại hàng thêm mới không tồn tại hoặc đã ngừng sử dụng.'])->withInput();
                }
                if ($order->items->contains(fn ($existingItem): bool => (int) $existingItem->product_variant_id === $variantId)) {
                    return back()->withErrors(['items' => 'Loại hàng này đã có trong đơn. Hãy điều chỉnh số lượng ở dòng hiện có.'])->withInput();
                }
                if (in_array($variantId, $seenNewVariantIds, true)) {
                    return back()->withErrors(['items' => 'Không thể thêm trùng một loại hàng mới trong cùng yêu cầu.'])->withInput();
                }
                if ((int) $itemData['adjusted_quantity'] <= 0) {
                    return back()->withErrors(['items' => 'Số lượng hàng thêm mới phải lớn hơn 0.'])->withInput();
                }

                $seenNewVariantIds[] = $variantId;
                $requiresWarehouse = true;
                $defaultWeight = (float) ($variant->effective_kg ?? 0) * (int) $itemData['adjusted_quantity'];
                $preparedItems[] = [
                    'order_item_id' => null,
                    'product_id' => (int) $variant->product_id,
                    'product_variant_id' => $variantId,
                    'original_quantity' => 0,
                    'adjusted_quantity' => (int) $itemData['adjusted_quantity'],
                    'original_price' => 0,
                    'adjusted_price' => (float) $itemData['adjusted_price'],
                    'original_weight' => 0,
                    'adjusted_weight' => isset($itemData['adjusted_weight'])
                        ? (float) $itemData['adjusted_weight']
                        : $defaultWeight,
                    'note' => $itemData['note'] ?? 'Bổ sung hàng thiếu trong đơn',
                ];
                continue;
            }

            if ((int) $itemData['adjusted_quantity'] < (int) ($orderItem->quantity ?? 0)) {
                $requiresReturn = true;
            }

            if ((int) $itemData['adjusted_quantity'] !== (int) ($orderItem->quantity ?? 0)) {
                $requiresWarehouse = true;
            }

            $originalWeight = (float) ($orderItem->actual_weight ?? $orderItem->total_weight ?? $orderItem->display_total_value ?? 0);
            $preparedItems[] = [
                'order_item_id' => (int) $orderItem->id,
                'product_id' => $orderItem->product_id ? (int) $orderItem->product_id : null,
                'product_variant_id' => $orderItem->product_variant_id ? (int) $orderItem->product_variant_id : null,
                'original_quantity' => (int) ($orderItem->quantity ?? 0),
                'adjusted_quantity' => (int) $itemData['adjusted_quantity'],
                'original_price' => (float) ($orderItem->price ?? 0),
                'adjusted_price' => (float) $itemData['adjusted_price'],
                'original_weight' => $originalWeight,
                'adjusted_weight' => isset($itemData['adjusted_weight']) ? (float) $itemData['adjusted_weight'] : $originalWeight,
                'note' => $itemData['note'] ?? null,
            ];
        }

        if ($requiresReturn && empty($data['return_warehouse_id'])) {
            return back()->withErrors(['return_warehouse_id' => 'Bat buoc chon kho tra hang khi giam so luong.'])->withInput();
        }

        $imagePaths = [];
        foreach ($request->file('evidence_images', []) as $file) {
            $imagePaths[] = $file->store('orders/adjustments', 'public');
        }

        $status = $data['action'] === 'submit'
            ? OrderAdjustment::STATUS_PENDING_APPROVAL
            : OrderAdjustment::STATUS_DRAFT;

        $adjustment = DB::transaction(function () use ($order, $data, $preparedItems, $status, $requiresWarehouse, $imagePaths) {
            $adjustment = OrderAdjustment::create([
                'order_id' => $order->id,
                'requested_by' => (int) auth()->id(),
                'workflow_code' => 'order_adjustments',
                'status' => $status,
                'adjustment_note' => $data['adjustment_note'] ?? null,
                'evidence_images' => $imagePaths ?: null,
                'return_warehouse_id' => $data['return_warehouse_id'] ?? null,
                'warehouse_confirmation_status' => $requiresWarehouse ? 'pending' : 'not_required',
                'submitted_at' => $status === OrderAdjustment::STATUS_PENDING_APPROVAL ? now() : null,
            ]);

            foreach ($preparedItems as $itemData) {
                OrderAdjustmentItem::create(array_merge($itemData, [
                    'order_adjustment_id' => $adjustment->id,
                ]));
            }

            return $adjustment;
        });

        if ($adjustment->status === OrderAdjustment::STATUS_PENDING_APPROVAL) {
            $this->createOrSyncReturnOrder($adjustment->fresh('items', 'order'));
            app(ApprovalService::class)->initAdjustmentApproval($adjustment);
            $autoApproval = app(OrderAutoApprovalService::class)->processAdjustment($adjustment);
            if ($autoApproval['all_approved'] && $autoApproval['approver']) {
                $this->finalizeAutoApprovedAdjustment($adjustment, $autoApproval['approver']);
            }
        }

        return redirect()
            ->route('site.order-adjustments.show', $adjustment)
            ->with('success', $adjustment->status === OrderAdjustment::STATUS_DRAFT
                ? 'Da luu ban nhap dieu chinh don hang.'
                : 'Da gui yeu cau dieu chinh don hang cho quy trinh duyet.');
    }

    public function show(OrderAdjustment $orderAdjustment): View
    {
        $settings   = $this->settings;
        $this->authorizeView($orderAdjustment);

        $orderAdjustment->load([
            'order.customer',
            'order.user',
            'items.variant.product',
            'requester',
            'approver',
            'rejecter',
            'completer',
            'returnWarehouse',
            'warehouseConfirmer',
            'orderReturn',
        ]);

        $user = auth()->user();

        return view('site.orders.adjustments.show', [
            'adjustment' => $orderAdjustment,
            'canApprove' => $user ? $this->canApprove($user, $orderAdjustment) : false,
            'canWarehouseConfirm' => $user ? $this->canWarehouseConfirm($user, $orderAdjustment) : false,
            'settings' => $settings,
        ]);
    }

    public function warehouseIndex(Request $request, ApprovalService $approvalService): View
    {
        $keyword = mb_strtolower(trim((string) $request->input('keyword', '')));
        $adjustments = $approvalService->warehouseAdjustmentQueue();

        if ($keyword !== '') {
            $adjustments = $adjustments->filter(function (OrderAdjustment $adjustment) use ($keyword): bool {
                return str_contains(mb_strtolower((string) $adjustment->order?->code), $keyword)
                    || str_contains(mb_strtolower((string) $adjustment->order?->customer?->name), $keyword)
                    || str_contains(mb_strtolower((string) $adjustment->requester?->name), $keyword)
                    || str_contains((string) $adjustment->id, $keyword);
            })->values();
        }

        return view('warehouse.order_adjustments', [
            'adjustments' => $adjustments,
            'keyword' => trim((string) $request->input('keyword', '')),
        ]);
    }

    public function approve(Request $request, OrderAdjustment $orderAdjustment): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && $this->canApprove($user, $orderAdjustment), 403);

        if ($orderAdjustment->status !== OrderAdjustment::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Yeu cau dieu chinh khong o trang thai cho duyet.');
        }

        $note = trim((string) $request->input('note', ''));

        $approvalService = app(ApprovalService::class);
        $hasPendingStep = $orderAdjustment->approvalSteps()->where('status', 'pending')->exists();
        $approvalActor = $user;

        DB::transaction(function () use ($orderAdjustment, $user, $note, $approvalService, $hasPendingStep, &$approvalActor): void {
            if ($hasPendingStep) {
                $allApproved = $approvalService->approveAdjustmentStep($orderAdjustment, $user, $note !== '' ? $note : null);

                if (!$allApproved) {
                    $autoApproval = app(OrderAutoApprovalService::class)->processAdjustment($orderAdjustment);
                    if (!$autoApproval['all_approved']) {
                        return;
                    }
                    $approvalActor = $autoApproval['approver'] ?: $user;
                }
            }

            // All steps approved (or no workflow) — finalize
            $orderAdjustment->update([
                'status' => OrderAdjustment::STATUS_APPROVED,
                'approved_by' => $approvalActor->id,
                'approved_at' => now(),
                'approval_note' => $note !== '' ? $note : null,
            ]);

            $this->createOrSyncReturnOrder($orderAdjustment->fresh(['items', 'order']));
        });

        $orderAdjustment->refresh();

        if ($orderAdjustment->status === OrderAdjustment::STATUS_APPROVED
            && $orderAdjustment->warehouse_confirmation_status === 'not_required') {
            $this->completeAdjustment($orderAdjustment, $approvalActor);
            return back()->with('success', 'Da duyet va hoan tat dieu chinh don hang.');
        }

        if ($orderAdjustment->status === OrderAdjustment::STATUS_APPROVED) {
            return back()->with('success', 'Đã duyệt yêu cầu điều chỉnh. Chờ Kho xác nhận hàng hóa và sản lượng.');
        }

        return back()->with('success', 'Da duyet buoc nay. Yeu cau chuyen sang buoc tiep theo.');
    }

    public function finalizeAutoApprovedAdjustment(OrderAdjustment $orderAdjustment, User $approver): void
    {
        if ($orderAdjustment->status !== OrderAdjustment::STATUS_PENDING_APPROVAL) {
            return;
        }

        $orderAdjustment->update([
            'status' => OrderAdjustment::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_note' => 'Đã tự động duyệt theo tiêu chí giá Min và sản lượng.',
        ]);

        $this->createOrSyncReturnOrder($orderAdjustment->fresh(['items', 'order']));
        $orderAdjustment->refresh();

        if ($orderAdjustment->warehouse_confirmation_status === 'not_required') {
            $this->completeAdjustment($orderAdjustment, $approver);
        }
    }

    public function reject(Request $request, OrderAdjustment $orderAdjustment): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && $this->canApprove($user, $orderAdjustment), 403);

        if ($orderAdjustment->status !== OrderAdjustment::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Yeu cau dieu chinh khong o trang thai cho duyet.');
        }

        $data = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $approvalService = app(ApprovalService::class);
        $hasPendingStep = $orderAdjustment->approvalSteps()->where('status', 'pending')->exists();

        if ($hasPendingStep) {
            $approvalService->rejectAdjustmentStep($orderAdjustment, $user, $data['reason']);
        }

        $orderAdjustment->update([
            'status' => OrderAdjustment::STATUS_REJECTED,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'reject_reason' => $data['reason'],
        ]);

        return back()->with('success', 'Da tu choi yeu cau dieu chinh.');
    }

    public function warehouseConfirm(Request $request, OrderAdjustment $orderAdjustment): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && $this->canWarehouseConfirm($user, $orderAdjustment), 403);

        $data = $request->validate([
            'mode' => 'required|in:confirm_full,confirm_partial,reject',
            'note' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:order_adjustment_items,id',
            'items.*.warehouse_received_quantity' => 'nullable|integer|min:0',
            'items.*.warehouse_received_weight' => 'nullable|numeric|min:0',
            'items.*.warehouse_condition' => 'nullable|string|max:255',
        ]);

        if ($data['mode'] === 'reject') {
            $orderAdjustment->update([
                'warehouse_confirmation_status' => 'rejected',
                'warehouse_confirmed_by' => $user->id,
                'warehouse_confirmed_at' => now(),
                'warehouse_confirmation_note' => $data['note'] ?? null,
                'status' => OrderAdjustment::STATUS_REJECTED,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'reject_reason' => $data['note'] ?? 'Kho tu choi nhan hang tra cho yeu cau dieu chinh.',
            ]);

            return back()->with('success', 'Kho da tu choi xac nhan hang tra.');
        }

        DB::transaction(function () use ($orderAdjustment, $data, $user): void {
            $items = collect($data['items'] ?? [])->keyBy('id');
            $orderAdjustment->loadMissing(['items', 'orderReturn.returnItems']);

            foreach ($orderAdjustment->items as $item) {
                $posted = $items->get($item->id, []);
                $expectedDecrease = max((int) $item->original_quantity - (int) $item->adjusted_quantity, 0);
                $receivedQty = (int) ($posted['warehouse_received_quantity'] ?? $expectedDecrease);
                $receivedQty = max(0, min($receivedQty, $expectedDecrease));

                $item->update([
                    'warehouse_received_quantity' => $receivedQty,
                    'warehouse_received_weight' => isset($posted['warehouse_received_weight']) ? (float) $posted['warehouse_received_weight'] : null,
                    'warehouse_condition' => $posted['warehouse_condition'] ?? null,
                ]);
            }

            if ($orderAdjustment->orderReturn) {
                $returnsByVariant = $orderAdjustment->orderReturn->returnItems->keyBy('product_variant_id');

                foreach ($orderAdjustment->items as $adjItem) {
                    $returnItem = $returnsByVariant->get($adjItem->product_variant_id);
                    if (!$returnItem) {
                        continue;
                    }

                    $expectedDecrease = max((int) $adjItem->original_quantity - (int) $adjItem->adjusted_quantity, 0);
                    $receivedQty = (int) ($adjItem->warehouse_received_quantity ?? $expectedDecrease);
                    $receivedQty = max(0, min($receivedQty, $expectedDecrease));

                    $returnItem->update([
                        'quantity' => $receivedQty,
                        'condition' => $adjItem->warehouse_condition,
                    ]);
                }

                if ($orderAdjustment->orderReturn->status === 'ship_confirmed') {
                    app(OrderReturnController::class)->warehouseConfirm($orderAdjustment->orderReturn);
                }
            }

            $orderAdjustment->update([
                'warehouse_confirmation_status' => $data['mode'] === 'confirm_full' ? 'confirmed_full' : 'confirmed_partial',
                'warehouse_confirmed_by' => $user->id,
                'warehouse_confirmed_at' => now(),
                'warehouse_confirmation_note' => $data['note'] ?? null,
            ]);
        });

        $this->completeAdjustment($orderAdjustment->fresh(['items', 'order.items.variant.product']), $user);

        return back()->with('success', 'Kho da xac nhan hang tra va he thong da hoan tat dieu chinh.');
    }

    private function authorizeCreate(Order $order): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless(
            $order->canRequestAdjustment(),
            422,
            'Chỉ có thể gửi yêu cầu điều chỉnh sau khi đơn đã giao và kế toán đã xác nhận doanh thu.'
        );

        if ($user->hasRole('admin')) {
            return;
        }

        if (!$user->hasRole('sale')) {
            abort(403);
        }

        if ((int) $order->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function authorizeView(OrderAdjustment $orderAdjustment): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if ($user->hasRole('admin') || $user->hasRole('account') || $user->hasRole('accountant') || $user->hasRole('warehouse')) {
            return;
        }

        if ((int) $orderAdjustment->requested_by === (int) $user->id) {
            return;
        }

        $orderAdjustment->loadMissing('order.user');
        if ($user->hasRole(['manager', 'manager_sale', 'director'])) {
            return;
        }

        if ($user->hasRole(['leader', 'leader_sale', 'sale_manager'])
            && (int) ($user->team_id ?? 0) > 0
            && (int) ($orderAdjustment->order?->user?->team_id ?? 0) === (int) $user->team_id) {
            return;
        }

        abort(403);
    }

    private function canApprove(User $user, OrderAdjustment $adjustment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($adjustment->status !== OrderAdjustment::STATUS_PENDING_APPROVAL) {
            return false;
        }

        $approvalService = app(ApprovalService::class);

        // Check if workflow steps exist for this adjustment
        $hasPendingStep = $adjustment->approvalSteps()->where('status', 'pending')->exists();

        if ($hasPendingStep) {
            return $approvalService->canApproveAdjustmentStep($adjustment, $user);
        }

        // Fallback: no workflow configured → accountant can approve
        return $user->hasRole('account') || $user->hasRole('accountant');
    }

    private function canWarehouseConfirm(User $user, OrderAdjustment $adjustment): bool
    {
        if (!$user->hasRole('admin') && !$user->hasRole('warehouse')) {
            return false;
        }

        return $adjustment->status === OrderAdjustment::STATUS_APPROVED
            && $adjustment->warehouse_confirmation_status === 'pending'
            && $adjustment->requiresWarehouseConfirmation();
    }

    private function createOrSyncReturnOrder(OrderAdjustment $adjustment): void
    {
        $adjustment->loadMissing(['order', 'items']);

        $returnItemsPayload = $adjustment->items
            ->map(function (OrderAdjustmentItem $item): ?array {
                $decreaseQty = max((int) $item->original_quantity - (int) $item->adjusted_quantity, 0);
                if ($decreaseQty <= 0) {
                    return null;
                }

                return [
                    'product_variant_id' => (int) $item->product_variant_id,
                    'quantity' => $decreaseQty,
                    'condition' => $item->note,
                ];
            })
            ->filter()
            ->values();

        if ($returnItemsPayload->isEmpty()) {
            $adjustment->update([
                'warehouse_confirmation_status' => $adjustment->requiresWarehouseConfirmation() ? 'pending' : 'not_required',
                'order_return_id' => null,
            ]);
            return;
        }

        $firstEvidence = (array) ($adjustment->evidence_images ?? []);

        $orderReturn = $adjustment->orderReturn;
        if (!$orderReturn) {
            $orderReturn = OrderReturn::create([
                'order_id' => $adjustment->order_id,
                'order_adjustment_id' => $adjustment->id,
                'customer_id' => $adjustment->order?->customer_id,
                'warehouse_id' => $adjustment->return_warehouse_id,
                'created_by' => $adjustment->requested_by,
                'ship_confirmed_by' => $adjustment->requested_by,
                'ship_confirmed_at' => now(),
                'status' => 'ship_confirmed',
                'reason' => 'Don hoan tra tu yeu cau dieu chinh #' . $adjustment->id,
                'evidence_image_path' => $firstEvidence[0] ?? null,
                'note' => $adjustment->adjustment_note,
            ]);

            $adjustment->update(['order_return_id' => $orderReturn->id]);
        } else {
            $orderReturn->update([
                'warehouse_id' => $adjustment->return_warehouse_id,
                'order_adjustment_id' => $adjustment->id,
                'status' => 'ship_confirmed',
                'ship_confirmed_by' => $adjustment->requested_by,
                'ship_confirmed_at' => now(),
                'reason' => 'Don hoan tra tu yeu cau dieu chinh #' . $adjustment->id,
                'evidence_image_path' => $firstEvidence[0] ?? $orderReturn->evidence_image_path,
                'note' => $adjustment->adjustment_note,
            ]);

            $orderReturn->returnItems()->delete();
        }

        foreach ($returnItemsPayload as $itemPayload) {
            ReturnItem::create([
                'order_return_id' => $orderReturn->id,
                'product_variant_id' => $itemPayload['product_variant_id'],
                'quantity' => $itemPayload['quantity'],
                'condition' => $itemPayload['condition'],
            ]);
        }

        $adjustment->order?->update(['has_return_order' => true]);
    }

    private function completeAdjustment(OrderAdjustment $adjustment, User $actor): void
    {
        if ($adjustment->status === OrderAdjustment::STATUS_COMPLETED) {
            return;
        }

        $adjustment->loadMissing(['order.items.variant.product', 'items.variant.product']);
        $order = $adjustment->order;
        if (!$order) {
            return;
        }

        DB::transaction(function () use ($adjustment, $order, $actor): void {
            $orderItems = $order->items->keyBy('id');

            foreach ($adjustment->items as $adjItem) {
                $orderItem = $orderItems->get((int) $adjItem->order_item_id);
                if (!$orderItem) {
                    $variant = $adjItem->variant;
                    $adjustedQty = (int) $adjItem->adjusted_quantity;
                    if (!$variant || $adjustedQty <= 0) {
                        continue;
                    }

                    $unitWeight = (float) ($variant->effective_kg ?? 1);
                    $isPricedByKg = (bool) ($variant->effective_priced_by_kg ?? true);
                    $finalWeight = (float) ($adjItem->adjusted_weight ?? 0);
                    if ($finalWeight <= 0) {
                        $finalWeight = $adjustedQty * max($unitWeight, 0);
                    }
                    $finalPrice = (float) $adjItem->adjusted_price;

                    $order->items()->create([
                        'product_id' => $adjItem->product_id ?: $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $adjustedQty,
                        'price' => $finalPrice,
                        'base_price' => $finalPrice,
                        'unit_discount' => 0,
                        'discount_type' => 'decrease',
                        'discount_total' => 0,
                        'unit_weight' => $unitWeight,
                        'is_priced_by_kg' => $isPricedByKg,
                        'actual_weight' => $finalWeight,
                        'total_weight' => $finalWeight,
                        'total' => round($finalPrice * ($isPricedByKg ? $finalWeight : $adjustedQty), 2),
                    ]);
                    continue;
                }

                $originalQty = (int) $adjItem->original_quantity;
                $adjustedQty = (int) $adjItem->adjusted_quantity;
                $expectedDecrease = max($originalQty - $adjustedQty, 0);

                if ($expectedDecrease > 0) {
                    $confirmedDecrease = $adjustment->warehouse_confirmation_status === 'not_required'
                        ? $expectedDecrease
                        : (int) ($adjItem->warehouse_received_quantity ?? 0);

                    $confirmedDecrease = max(0, min($confirmedDecrease, $expectedDecrease));
                    $finalQty = max($originalQty - $confirmedDecrease, 0);
                } else {
                    $finalQty = $adjustedQty;
                }

                $finalPrice = (float) $adjItem->adjusted_price;
                $finalWeight = (float) ($adjItem->adjusted_weight ?? $adjItem->original_weight ?? 0);
                $targetVariant = $adjItem->variant;
                $isPricedByKg = $targetVariant
                    ? (bool) ($targetVariant->effective_priced_by_kg ?? true)
                    : (bool) $orderItem->effective_priced_by_kg;
                $unitWeight = $targetVariant
                    ? (float) ($targetVariant->effective_kg ?? $orderItem->unit_weight ?? 1)
                    : (float) ($orderItem->unit_weight ?? 1);

                $lineTotal = $isPricedByKg
                    ? ($finalPrice * max($finalWeight, 0))
                    : ($finalPrice * max($finalQty, 0));

                $orderItem->update([
                    'product_id' => $adjItem->product_id ?: $orderItem->product_id,
                    'product_variant_id' => $adjItem->product_variant_id ?: $orderItem->product_variant_id,
                    'quantity' => $finalQty,
                    'price' => $finalPrice,
                    'unit_weight' => $unitWeight,
                    'is_priced_by_kg' => $isPricedByKg,
                    'actual_weight' => $finalWeight > 0 ? $finalWeight : $orderItem->actual_weight,
                    'total_weight' => $finalWeight > 0 ? $finalWeight : $orderItem->total_weight,
                    'total' => round($lineTotal, 2),
                ]);
            }

            $order->load('items');
            $subtotal = (float) $order->items->sum(fn ($item) => (float) ($item->total ?? 0));
            $extraDiscount = (float) ($order->extra_discount_total ?? 0);
            $newTotal = max($subtotal - $extraDiscount, 0);

            $amountPaid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
                - (float) $order->transactions()->where('type', 'refund')->sum('amount');

            $order->update([
                'subtotal_amount' => round($subtotal, 2),
                'item_discount_total' => 0,
                'total_discount' => round($extraDiscount, 2),
                'total' => round($newTotal, 2),
                'amount_paid' => round($amountPaid, 2),
                'amount_due' => round(max($newTotal - $amountPaid, 0), 2),
                'payment_status' => $amountPaid >= $newTotal
                    ? 'paid'
                    : ($amountPaid > 0 ? 'partially_paid' : 'unpaid'),
            ]);

            $adjustment->update([
                'status' => OrderAdjustment::STATUS_COMPLETED,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);
        });
    }
}
