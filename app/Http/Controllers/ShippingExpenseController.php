<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProcessDefinition;
use App\Models\ShippingExpenseClaim;
use App\Services\ShippingExpenseService;
use App\Support\TaskWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingExpenseController extends Controller
{
    public function __construct(private ShippingExpenseService $service) {}

    private function layout()
    {
        if (request('queue') === 'coordination') {
            return 'layouts.shipper';
        }if (request('queue') === 'accounting') {
            return 'layouts.accounting';
        }

        return TaskWorkspace::layout(auth()->user());
    }

    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:running,revision,rejected,confirmed', 'q' => 'nullable|string|max:200', 'queue' => 'nullable|in:mine,coordination,accounting']);
        if ($request->input('queue', 'mine') === 'mine' && $request->input('tab', 'orders') === 'orders' && $this->service->engine->hasRole($request->user(), 'shipper')) {
            return $this->create($request);
        }
        $user = $request->user();
        $queue = $request->input('queue', 'mine');
        if ($queue === 'coordination') {
            abort_unless($this->service->engine->isReviewer($user, 'manager_shipper'), 403);
        }
        if ($queue === 'accounting') {
            abort_unless(collect(['accountant', 'account', 'accounting'])->contains(fn ($role) => $this->service->engine->isReviewer($user, $role)), 403);
        }
        $claims = ShippingExpenseClaim::with('run.events', 'shipper')->when(! $user->hasRole('admin'), fn ($q) => $q->where(function ($q) use ($user, $queue) {
            if ($queue === 'mine') {
                $q->where('shipper_id', $user->id);
            } else {
                $q->whereHas('run', function ($q) use ($user, $queue) {
                    $roles = $queue === 'coordination' ? ['manager_shipper'] : ['accountant', 'account', 'accounting'];
                    $q->where(function ($q) use ($roles, $user) {
                        foreach ($roles as $role) {
                            if ($this->service->engine->hasRole($user, $role)) {
                                $q->orWhereJsonContains('configuration->steps', ['user_id' => $user->id, 'role' => $role])->orWhereJsonContains('configuration->steps', ['assignment_mode' => 'role', 'role' => $role]);
                            }
                        }
                    });
                });
            }
        }))->when($request->filled('status'), fn ($q) => $q->whereHas('run', fn ($r) => $r->where('status', $request->status)))->when($request->filled('q'), fn ($q) => $q->whereHas('shipper', fn ($s) => $s->where('name', 'like', '%'.$request->q.'%')))->latest()->paginate(20)->withQueryString();

        $ids = $claims->getCollection()->flatMap(fn ($claim) => collect($claim->run->configuration['steps'])->pluck('user_id'))->filter()->unique();
        $timelineUsers = \App\Models\User::whereIn('id', $ids)->get()->keyBy('id');

        return view('processes.claims', ['claims' => $claims, 'layout' => $this->layout(), 'queue' => $queue, 'timelineUsers' => $timelineUsers]);
    }

    public function create(Request $request)
    {
        abort_unless($this->service->engine->hasRole($request->user(), 'shipper'), 403);
        $request->validate([
            'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'order_status' => 'nullable|in:delivered,completed',
            'sort' => 'nullable|in:date,status,confirmation,payment',
            'direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:20,50,100',
            'confirmation' => 'nullable|in:none,running,revision,confirmed,rejected',
            'payment' => 'nullable|in:none,pending,paid,rejected',
        ]);
        $map = $this->service->assignedOrders($request->user());
        $latest = DB::table('shipping_expense_items as expense_item')
            ->join('shipping_expense_claims as expense_claim', 'expense_claim.id', '=', 'expense_item.claim_id')
            ->where('expense_claim.shipper_id', $request->user()->id)
            ->selectRaw('expense_item.order_id, MAX(expense_claim.id) as claim_id')->groupBy('expense_item.order_id');
        $query = Order::with('customer')->select('orders.*', 'expense.id as expense_claim_id', 'expense_run.status as expense_status', 'expense_payment.status as expense_payment_status')
            ->leftJoinSub($latest, 'latest_expense', fn ($join) => $join->on('latest_expense.order_id', '=', 'orders.id'))
            ->leftJoin('shipping_expense_claims as expense', 'expense.id', '=', 'latest_expense.claim_id')
            ->leftJoin('process_runs as expense_run', 'expense_run.id', '=', 'expense.process_run_id')
            ->leftJoin('transactions as expense_payment', 'expense_payment.id', '=', 'orders.shipping_fee_transaction_id')
            ->whereIn('orders.id', array_keys($map))->where('orders.shipper_id', $request->user()->id)
            ->whereIn('orders.status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('orders.delivered_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('orders.delivered_at', '<=', $request->to))
            ->when($request->filled('order_status'), fn ($q) => $q->where('orders.status', $request->order_status));
        if ($request->confirmation === 'none') {
            $query->whereNull('expense.id');
        } elseif ($request->filled('confirmation')) {
            $query->where('expense_run.status', $request->confirmation);
        }
        if ($request->payment === 'none') {
            $query->whereNull('orders.shipping_fee_transaction_id');
        } elseif ($request->payment === 'pending') {
            $query->whereIn('expense_payment.status', ['pending_approval', 'approved_pending_completion']);
        } elseif ($request->payment === 'paid') {
            $query->where('expense_payment.status', 'approved');
        } elseif ($request->payment === 'rejected') {
            $query->where('expense_payment.status', 'rejected');
        }
        $sortColumns = ['date' => 'orders.delivered_at', 'status' => 'orders.status', 'confirmation' => 'expense_run.status', 'payment' => 'expense_payment.status'];
        $orders = $query->orderBy($sortColumns[$request->input('sort', 'date')], $request->input('direction', 'desc'))
            ->orderByDesc('orders.id')->get();
        $locked = DB::table('shipping_expense_order_locks')->whereIn('order_id', $orders->pluck('id'))->pluck('order_id')->all();
        foreach ($orders as $order) {
            $order->expense_selectable = ! $order->shipping_fee_transaction_id && ! in_array($order->id, $locked);
        }

        $dispatches = \App\Models\ShipperDispatchHistory::whereIn('id', array_values($map))->get()->keyBy('id');
        $groups = $orders->groupBy(fn ($order) => $map[$order->id])->map(function ($items, $id) use ($dispatches) {
            $dispatch = $dispatches->get($id);

            return ['id' => $id, 'date' => $dispatch?->schedule_date?->format('d/m/Y'), 'orders' => $items,
                'total' => $items->sum('shipping_fee'), 'can_submit' => $items->contains(fn ($order) => $order->expense_selectable)];
        })->values();
        $size = (int) $request->input('per_page', 20);
        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $routes = new \Illuminate\Pagination\LengthAwarePaginator($groups->forPage($page, $size)->values(), $groups->count(), $size, $page,
            ['path' => $request->url(), 'query' => $request->query()]);

        return view('processes.claim-create', ['orders' => $routes, 'routes' => $routes, 'layout' => $this->layout(), 'definition' => ProcessDefinition::where('activity', 'shipping_expense')->where('is_active', true)->first()]);
    }

    private function data(Request $request): array
    {
        return $request->validate(['items' => 'required|array|min:1|max:100', 'items.*.order_id' => 'required|integer|distinct|exists:orders,id', 'items.*.amount' => 'required|numeric|min:0|max:1000000000', 'items.*.note' => 'nullable|string|max:1000', 'note' => 'nullable|string|max:2000']);
    }

    public function store(Request $request)
    {
        $request->validate(['attachments' => 'nullable|array|max:10', 'attachments.*' => 'file|max:20480']);
        $data = $this->data($request);
        if ($request->filled('route_dispatch_id')) {
            $request->validate(['route_dispatch_id' => 'required|integer|exists:shipper_dispatch_histories,id']);
            $map = $this->service->assignedOrders($request->user());
            foreach ($data['items'] as $item) {
                abort_unless((int) ($map[$item['order_id']] ?? 0) === (int) $request->route_dispatch_id, 422, 'Đơn không thuộc lộ trình được chọn.');
            }
            $dispatch = \App\Models\ShipperDispatchHistory::findOrFail($request->route_dispatch_id);
            $data['note'] = 'Lộ trình #'.$dispatch->id.' · '.$dispatch->schedule_date->format('d/m/Y')."\n".($data['note'] ?? '');
        }
        $claim = $this->service->submit($request->user(), $data, $request->file('attachments', []));

        return redirect()->route('shipping-expenses.show', $claim)->with('success', 'Đã gửi yêu cầu xác nhận chi phí ship.');
    }

    public function show(ShippingExpenseClaim $claim)
    {
        $claim->load('run.events.actor', 'run.events.documents.uploader', 'items.order.customer', 'items.dispatch.creator', 'shipper', 'payment');
        abort_unless($this->service->engine->canView($claim->run, auth()->user()), 403);

        return view('processes.claim-show', ['claim' => $claim, 'layout' => $this->layout(), 'canHandle' => $this->service->engine->canHandle($claim->run, auth()->user()), 'timelineUsers' => \App\Models\User::whereIn('id', collect($claim->run->configuration['steps'])->pluck('user_id')->filter())->get()->keyBy('id')]);
    }

    public function action(Request $request, ShippingExpenseClaim $claim)
    {
        $request->validate(['attachments' => 'nullable|array|max:10', 'attachments.*' => 'file|max:20480']);
        $data = $request->validate(['action' => 'required|in:approve,reject,revise', 'note' => in_array($request->action, ['reject', 'revise']) ? 'required|string|max:2000' : 'nullable|string|max:2000']);
        $this->service->act($claim, $request->user(), $data['action'], $data['note'] ?? null, $request->file('attachments', []));

        return back()->with('success', 'Đã xử lý và thông báo cho các bên.');
    }

    public function revise(Request $request, ShippingExpenseClaim $claim)
    {
        $request->validate(['attachments' => 'nullable|array|max:10', 'attachments.*' => 'file|max:20480']);
        $this->service->revise($claim, $request->user(), $this->data($request), $request->file('attachments', []));

        return back()->with('success', 'Đã gửi lại chi phí về đúng người yêu cầu điều chỉnh.');
    }

    public function payment(Request $request, ShippingExpenseClaim $claim)
    {
        $transaction = $this->service->payment($claim, $request->user());

        return back()->with('success', 'Đã gửi yêu cầu thanh toán #'.$transaction->id.'. Việc gửi yêu cầu chưa ghi nhận đã chi tiền.');
    }
}
