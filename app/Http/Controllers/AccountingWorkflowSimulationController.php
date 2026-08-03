<?php

namespace App\Http\Controllers;

use App\Models\AccountingReconciliation;
use App\Models\Customer;
use App\Models\InventoryDocument;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\AccountingWorkflowSimulationService;
use App\Services\AccountingSalesImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountingWorkflowSimulationController extends Controller
{
    public function index(Request $request)
    {
        $date = Carbon::parse($request->input('date', now()->toDateString()))->toDateString();
        $orders = Order::query()->with([
            'customer:id,name', 'user:id,name', 'shipper:id,name', 'warehouse:id,name',
            'histories:id,order_id,action', 'accountingReconciliation:id,order_id,status',
            'warehouseTransfers' => fn ($query) => $query->with(['shipper:id,name', 'sourceWarehouse:id,name', 'targetWarehouse:id,name'])->latest('id'),
        ])->whereDate('created_at', $date)->orderBy('created_at')->orderBy('id')->get();

        $hasAction = fn (Order $order, array $actions): bool => $order->histories->contains(fn ($history) => in_array($history->action, $actions, true));
        $advancedStatuses = [Order::STATUS_APPROVED, Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING, Order::STATUS_PACKED, Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED];
        $stats = [
            'orders' => $orders->count(),
            'stock_in' => InventoryDocument::query()->where('type', 'import')->whereDate('document_date', $date)->count(),
            'leader' => $orders->filter(fn ($o) => $hasAction($o, ['leader_approved']) || ! in_array($o->status, [Order::STATUS_PENDING_LEADER_APPROVAL, 'pending'], true))->count(),
            'manager' => $orders->filter(fn ($o) => $hasAction($o, ['manager_approved']) || in_array($o->status, $advancedStatuses, true))->count(),
            'warehouse' => $orders->filter(fn ($o) => $hasAction($o, ['warehouse_confirm_pack', 'start_packing']))->count(),
            'packed' => $orders->filter(fn ($o) => $hasAction($o, ['complete_packing', 'warehouse_complete_packing']) || in_array($o->status, [Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true))->count(),
            'transfer_pickup' => $orders->filter(fn ($o) => in_array($o->warehouseTransfers->first()?->status, [WarehouseTransfer::STATUS_IN_TRANSIT, WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE, WarehouseTransfer::STATUS_RECEIVED_COMPLETED], true))->count(),
            'transfer_received' => $orders->filter(fn ($o) => $o->warehouseTransfers->first()?->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED)->count(),
            'assigned' => $orders->whereNotNull('shipper_id')->count(),
            'delivered' => $orders->filter(fn ($o) => in_array($o->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true))->count(),
            'accounted' => $orders->filter(fn ($o) => $o->accountingReconciliation?->status === AccountingReconciliation::STATUS_CONFIRMED)->count(),
        ];

        $transferredOrderIds = WarehouseTransfer::query()->pluck('order_id');

        return view('accounting.workflow-simulation', [
            'date' => $date,
            'orders' => $orders,
            'stats' => $stats,
            'transferCandidates' => $orders->whereIn('status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_PACKED])->whereNotIn('id', $transferredOrderIds),
            'leaderCandidates' => $orders->whereIn('status', [Order::STATUS_PENDING_LEADER_APPROVAL, 'pending']),
            'managerCandidates' => $orders->where('status', Order::STATUS_PENDING_MANAGER_APPROVAL),
            'warehouseCandidates' => $orders->whereIn('status', [Order::STATUS_APPROVED, Order::STATUS_READY_TO_PACK]),
            'packingCandidates' => $orders->where('status', Order::STATUS_PACKING),
            'pendingPickup' => $this->transfersForDate($date, WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP),
            'inTransit' => $this->transfersForDate($date, WarehouseTransfer::STATUS_IN_TRANSIT),
            'waitingReceive' => $this->transfersForDate($date, WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE),
            'assignmentCandidates' => $orders->filter(fn ($order) => in_array($order->status, [Order::STATUS_READY_TO_SHIP, Order::STATUS_PACKED], true)
                && $order->warehouseTransfers->first()?->status === WarehouseTransfer::STATUS_RECEIVED_COMPLETED),
            'deliveryCandidates' => $orders->filter(fn ($order) => $order->shipper_id && in_array($order->status, [Order::STATUS_READY_TO_SHIP, Order::STATUS_PACKED, Order::STATUS_DELIVERING], true)),
            'accountingCandidates' => $orders->filter(fn ($order) => $order->status === Order::STATUS_DELIVERED && $order->accountingReconciliation?->status !== AccountingReconciliation::STATUS_CONFIRMED),
            'warehouses' => Warehouse::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'sales' => User::query()->whereHas('roles', fn ($query) => $query->whereRaw('LOWER(name) = ?', ['sale']))->orderBy('name')->get(['id', 'name']),
            'bulkSales' => User::query()->whereHas('roles', fn ($query) => $query->whereIn(DB::raw('LOWER(name)'), ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']))->orderBy('name')->get(['id', 'name', 'short_name']),
            'shippers' => User::query()->whereHas('roles', fn ($query) => $query->whereIn(DB::raw('LOWER(name)'), ['shipper', 'manager_shipper']))->orderBy('name')->get(['id', 'name']),
            'variants' => ProductVariant::query()->with('product:id,name')->orderBy('product_id')->orderBy('name')->get(),
            'bulkResult' => session('workflow_bulk_result'),
        ]);
    }

    public function bulkOrders(Request $request, AccountingSalesImportService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'text_data' => ['required', 'string', 'max:1000000'],
            'text_action' => ['required', Rule::in(['preview', 'import'])],
            'sale_mapping' => ['nullable', 'array'],
            'sale_mapping.*' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            $result = $data['text_action'] === 'import'
                ? $service->importPendingOrders(
                    $data['text_data'],
                    $request->user(),
                    $data['sale_mapping'] ?? [],
                    Carbon::parse($data['date'])->toDateString(),
                    (int) $data['warehouse_id']
                )
                : $service->preview(
                    $data['text_data'],
                    $request->user(),
                    $data['sale_mapping'] ?? [],
                    Carbon::parse($data['date'])->toDateString()
                );
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['text_data' => $exception->getMessage()]);
        }

        if (($result['imported'] ?? false) === true) {
            return $this->back($data['date'], 'Đã lên hàng loạt '.$result['orders_created'].' đơn từ '.count($result['rows']).' dòng dữ liệu. Các đơn đang chờ Trưởng phòng duyệt.');
        }

        return redirect()
            ->route('accounting.workflow-simulation.index', ['date' => $data['date'], 'step' => 3])
            ->withInput()
            ->with('workflow_bulk_result', $result);
    }

    public function stockIn(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_variant_id' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
        $document = $service->stockInMany($data['items'], (int) $data['warehouse_id'], $data['date'], $request->user());

        return $this->back($data['date'], 'Đã nhập '.count($data['items']).' sản phẩm và tạo phiếu #'.$document->id.'.');
    }

    public function createOrder(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'sale_id' => ['required', 'integer', 'exists:users,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'price' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $order = $service->createOrder($data, $request->user());

        return $this->back($data['date'], 'Sale đã tạo đơn '.$order->code.'.');
    }

    public function advanceOrders(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate($this->bulkRules([
            'action' => ['required', Rule::in(['leader_approve', 'manager_approve', 'warehouse_confirm', 'complete_packing'])],
        ]));
        try {
            $count = $service->advanceOrders($this->scopedOrderIds($data), $data['action'], $request->user());
        } catch (\RuntimeException $exception) {
            return redirect()->route('accounting.workflow-simulation.index', ['date' => $data['date'], 'step' => (int) $request->input('wizard_step', 4)])->with('error', $exception->getMessage());
        }
        $labels = [
            'leader_approve' => 'Trưởng phòng đã duyệt',
            'manager_approve' => 'Manager đã duyệt',
            'warehouse_confirm' => 'Kho đã xác nhận đóng hàng',
            'complete_packing' => 'Đã hoàn tất đóng hàng',
        ];

        return $this->back($data['date'], $labels[$data['action']]." {$count} đơn.");
    }

    public function createTransfers(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate($this->bulkRules([
            'target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
        ]));
        $count = $service->createTransfers($data['order_ids'], (int) $data['target_warehouse_id'], (int) $data['shipper_id'], $request->user());
        return $this->back($data['date'], "Đã tạo {$count} phiếu điều chuyển.");
    }

    public function advanceTransfers(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'action' => ['required', Rule::in(['pickup', 'deliver'])],
        ]);
        $status = $data['action'] === 'pickup' ? WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP : WarehouseTransfer::STATUS_IN_TRANSIT;
        $transfers = $this->transfersForDate($data['date'], $status);
        $count = $data['action'] === 'pickup' ? $service->pickupTransfers($transfers, $request->user()) : $service->deliverTransfers($transfers, $request->user());
        return $this->back($data['date'], "Đã xử lý {$count} phiếu điều chuyển.");
    }

    public function receiveAll(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate(['date' => ['required', 'date']]);
        $count = $service->receiveTransfers($this->transfersForDate($data['date'], WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE), $request->user());
        return $this->back($data['date'], "Kho đích đã nhận tất cả {$count} phiếu điều chuyển.");
    }

    public function assignOrders(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate($this->bulkRules(['shipper_id' => ['required', 'integer', 'exists:users,id']]));
        $count = $service->assignOrders($this->scopedOrderIds($data), (int) $data['shipper_id'], $request->user());
        return $this->back($data['date'], "Đã điều phối {$count} đơn cho shipper.");
    }

    public function deliverOrders(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate($this->bulkRules(['payment_mode' => ['required', Rule::in(['paid', 'debt'])]]));
        $count = $service->deliverOrders($this->scopedOrderIds($data), $data['payment_mode'], $request->user());
        return $this->back($data['date'], "Đã giao và ghi nhận thanh toán {$count} đơn.");
    }

    public function confirmOrders(Request $request, AccountingWorkflowSimulationService $service)
    {
        $data = $request->validate($this->bulkRules());
        $count = $service->confirmOrders($this->scopedOrderIds($data), $request->user());
        return $this->back($data['date'], "Kế toán đã xác nhận {$count} đơn, ghi nhận doanh số và hoa hồng.");
    }

    private function transfersForDate(string $date, string $status)
    {
        return WarehouseTransfer::query()->with(['order.items', 'order.customer', 'shipper'])
            ->where('status', $status)->whereHas('order', fn ($query) => $query->whereDate('created_at', Carbon::parse($date)->toDateString()))
            ->orderBy('id')->get();
    }

    private function bulkRules(array $extra = []): array
    {
        return array_merge([
            'date' => ['required', 'date'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ], $extra);
    }

    private function scopedOrderIds(array $data): array
    {
        return Order::query()->whereIn('id', $data['order_ids'])->whereDate('created_at', Carbon::parse($data['date'])->toDateString())->pluck('id')->all();
    }

    private function back(string $date, string $message)
    {
        return redirect()->route('accounting.workflow-simulation.index', [
            'date' => Carbon::parse($date)->toDateString(),
            'step' => max(1, min(6, (int) request()->input('next_step', request()->input('wizard_step', 1)))),
        ])->with('success', $message);
    }
}
