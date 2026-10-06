<?php

namespace App\Http\Controllers;

use App\Models\{Order, WarehouseTransfer, User, OrderHistory};
use App\Services\ShipperHoldingExceptionService;
use Illuminate\Http\Request;

class AdminShipperHoldingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['shipper_id'=>['nullable','integer','min:1'],'search'=>['nullable','string','max:255']]);
        $shipperId = $filters['shipper_id'] ?? null;
        $search = trim($filters['search'] ?? '');
        $transfers = WarehouseTransfer::with(['order.customer','order.items.variant.product','shipper','sourceWarehouse','targetWarehouse'])
            ->where('status', WarehouseTransfer::STATUS_IN_TRANSIT)->whereHas('order')
            ->when($shipperId, fn($q) => $q->where('shipper_id',$shipperId))
            ->when($search !== '', fn($q) => $q->whereHas('order', fn($o) => $o->where('code','like','%'.$search.'%')->orWhereHas('customer',fn($c) => $c->where('name','like','%'.$search.'%'))))
            ->orderBy('picked_up_at')->paginate(30,['*'],'transfer_page')->withQueryString();
        $orders = Order::with(['customer','shipper','warehouse','items.variant.product'])
            ->whereNotNull('shipper_id')->whereIn('status',ShipperHoldingExceptionService::DELIVERY_STATUSES)
            ->whereDoesntHave('warehouseTransfers', fn($q) => $q->whereIn('status',['in_transit','pending_shipper_pickup','delivered_waiting_receive']))
            ->when($shipperId,fn($q) => $q->where('shipper_id',$shipperId))
            ->when($search !== '', fn($q) => $q->where(fn($o) => $o->where('code','like','%'.$search.'%')->orWhereHas('customer',fn($c) => $c->where('name','like','%'.$search.'%'))))
            ->oldest('updated_at')->paginate(30,['*'],'order_page')->withQueryString();
        $shipperIds = WarehouseTransfer::where('status','in_transit')->whereHas('order')->pluck('shipper_id')
            ->merge(Order::whereNotNull('shipper_id')->whereIn('status',ShipperHoldingExceptionService::DELIVERY_STATUSES)->pluck('shipper_id'))->filter()->unique();
        $shippers = User::whereIn('id',$shipperIds)->orderBy('name')->get(['id','name']);
        $histories = OrderHistory::with(['order.customer','user'])->whereIn('action',['admin_shipper_transfer_complete','admin_shipper_transfer_return','admin_shipper_order_complete','admin_shipper_order_return'])->latest('id')->limit(30)->get();
        return view('admin.shipper_holdings.index', compact('transfers','orders','shippers','shipperId','search','histories'));
    }
    public function process(Request $request, string $kind, int $id, ShipperHoldingExceptionService $service)
    {
        abort_unless(in_array($kind,['order','transfer'],true),404);
        $data = $request->validate(['action'=>['required','in:complete,return'],'reason'=>['required','string','max:1000'],'shipper_id'=>['required','integer','min:1']]);
        $service->{$kind}($id,$data['action'],trim($data['reason']),$request->user()->id,(int) $data['shipper_id']);
        return back()->with('success',$data['action'] === 'complete' ? 'Đã hoàn tất ngoại lệ và ghi lịch sử xử lý.' : 'Đã gỡ khỏi shipper và trả hàng về kho lấy hàng, có ghi lịch sử xử lý.');
    }
}
