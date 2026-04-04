<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaleMobileController extends Controller
{
    public function index()
    {
        return view('mobile.sale.index');
    }

    public function customers(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));

        $query = Customer::query()
            ->select(['id', 'name', 'phone', 'address', 'assigned_to', 'status'])
            ->orderByDesc('id');

        if (!$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('user_id', $user->id);
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $rows = $query->limit(60)->get()->map(function (Customer $customer) {
            return [
                'id' => (int) $customer->id,
                'name' => (string) $customer->name,
                'phone' => (string) ($customer->phone ?? '—'),
                'address' => (string) ($customer->address ?? '—'),
                'status' => (string) ($customer->status ?? 'active'),
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = trim((string) $request->query('status', ''));

        $query = Order::query()
            ->with(['customer:id,name,phone'])
            ->select(['id', 'code', 'customer_id', 'status', 'total', 'amount_due', 'created_at', 'user_id'])
            ->orderByDesc('id');

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $rows = $query->limit(60)->get()->map(function (Order $order) {
            return [
                'id' => (int) $order->id,
                'code' => (string) ($order->code ?: ('#' . $order->id)),
                'customer' => (string) ($order->customer?->name ?? '—'),
                'phone' => (string) ($order->customer?->phone ?? '—'),
                'status' => (string) $order->status,
                'total' => (float) ($order->total ?? 0),
                'amount_due' => (float) ($order->amount_due ?? 0),
                'created_at' => optional($order->created_at)->format('d/m H:i'),
                'detail_url' => route('site.orders.show', $order),
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function metrics(Request $request): JsonResponse
    {
        $user = $request->user();
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        $ordersQuery = Order::query()->whereBetween('created_at', [$from, $to]);

        if (!$user->hasRole('admin')) {
            $ordersQuery->where('user_id', $user->id);
        }

        $orderCount = (clone $ordersQuery)->count();
        $revenue = (float) (clone $ordersQuery)->sum('total');
        $debt = (float) (clone $ordersQuery)->sum('amount_due');

        $commissionRules = 0;
        if (Schema::hasTable('accounting_customer_commissions')) {
            $commissionRulesQuery = DB::table('accounting_customer_commissions')->where('is_active', true);

            if (!$user->hasRole('admin')) {
                $customerIds = Customer::query()
                    ->where(function ($q) use ($user) {
                        $q->where('assigned_to', $user->id)
                            ->orWhere('user_id', $user->id);
                    })
                    ->pluck('id');

                if ($customerIds->isNotEmpty()) {
                    $commissionRulesQuery->whereIn('customer_id', $customerIds->all());
                } else {
                    $commissionRulesQuery->whereRaw('1=0');
                }
            }

            $commissionRules = (int) $commissionRulesQuery->count();
        }

        return response()->json([
            'data' => [
                'order_count_month' => $orderCount,
                'revenue_month' => $revenue,
                'debt_month' => $debt,
                'active_commission_rules' => $commissionRules,
            ],
        ]);
    }
}
