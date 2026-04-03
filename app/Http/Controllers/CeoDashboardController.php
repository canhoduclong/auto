<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CeoDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:ceo,admin']);
    }

    public function dashboard(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $overview = $this->buildOverview($from, $to);
        $salesTop = $this->buildSalesTop($from, $to, 5);
        $customerTop = $this->buildCustomerTop($from, $to, 5);
        $shipperTop = $this->buildShipperTop($from, $to, 5);
        $alerts = $this->buildAlerts($overview);

        return view('ceo.dashboard', compact('overview', 'salesTop', 'customerTop', 'shipperTop', 'alerts', 'from', 'to', 'rangeLabel'));
    }

    public function revenue(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $payments = Transaction::query()
            ->where('type', 'payment')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $refunds = Transaction::query()
            ->where('type', 'refund')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $netRevenue = (float) $payments - (float) $refunds;

        $dailySeries = Transaction::query()
            ->selectRaw('DATE(created_at) as day_key')
            ->selectRaw("SUM(CASE WHEN type='payment' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type='refund' THEN amount ELSE 0 END) as refund")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->get();

        return view('ceo.section', [
            'pageTitle' => 'Tổng Quan Doanh Thu',
            'pageSubtitle' => 'Theo dõi doanh thu thuần, thu tiền và hoàn tiền theo kỳ.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Thu tiền', 'value' => number_format((float) $payments) . ' đ'],
                ['label' => 'Hoàn tiền', 'value' => number_format((float) $refunds) . ' đ'],
                ['label' => 'Doanh thu thuần', 'value' => number_format($netRevenue) . ' đ'],
            ],
            'tableTitle' => 'Diễn biến doanh thu theo ngày',
            'columns' => ['Ngày', 'Thu tiền', 'Hoàn tiền', 'Doanh thu thuần'],
            'rows' => $dailySeries->map(function ($row) {
                $net = (float) $row->income - (float) $row->refund;

                return [
                    Carbon::parse($row->day_key)->format('d/m/Y'),
                    number_format((float) $row->income) . ' đ',
                    number_format((float) $row->refund) . ' đ',
                    number_format($net) . ' đ',
                ];
            })->all(),
        ]);
    }

    public function orders(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $statusRows = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $total = (int) $statusRows->sum('total');

        return view('ceo.section', [
            'pageTitle' => 'Tổng Đơn Hàng',
            'pageSubtitle' => 'Theo dõi pipeline đơn hàng theo trạng thái xử lý.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Tổng đơn', 'value' => number_format($total)],
                ['label' => 'Đơn hoàn thành', 'value' => number_format((int) $statusRows->whereIn('status', ['completed', 'delivered'])->sum('total'))],
                ['label' => 'Đơn trả/hủy', 'value' => number_format((int) $statusRows->whereIn('status', ['returning', 'returned_completed', 'returned', 'cancelled', 'rejected'])->sum('total'))],
            ],
            'tableTitle' => 'Phân bổ trạng thái đơn',
            'columns' => ['Trạng thái', 'Số đơn', 'Tỷ trọng'],
            'rows' => $statusRows->map(function ($row) use ($total) {
                $rate = $total > 0 ? round(((int) $row->total / $total) * 100, 1) : 0;

                return [
                    (string) $row->status,
                    number_format((int) $row->total),
                    $rate . '%',
                ];
            })->all(),
        ]);
    }

    public function sales(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $rows = $this->buildSalesTop($from, $to, 20);

        return view('ceo.section', [
            'pageTitle' => 'Hiệu Suất Sale',
            'pageSubtitle' => 'Bảng xếp hạng doanh số theo nhân sự kinh doanh.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Số sale có phát sinh', 'value' => number_format($rows->count())],
                ['label' => 'Tổng doanh số', 'value' => number_format((float) $rows->sum('total_amount')) . ' đ'],
                ['label' => 'Tổng số đơn', 'value' => number_format((int) $rows->sum('total_orders'))],
            ],
            'tableTitle' => 'Top sale theo doanh số',
            'columns' => ['Nhân sự', 'Số đơn', 'Doanh số'],
            'rows' => $rows->map(fn ($row) => [
                (string) $row->name,
                number_format((int) $row->total_orders),
                number_format((float) $row->total_amount) . ' đ',
            ])->all(),
        ]);
    }

    public function debts(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $hasAmountDue = Schema::hasColumn('orders', 'amount_due');

        $rows = collect();
        $totalDebt = 0;

        if ($hasAmountDue) {
            $rows = Order::query()
                ->select('customer_id', DB::raw('SUM(amount_due) as debt_total'), DB::raw('COUNT(*) as total_orders'))
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('customer_id')
                ->where('amount_due', '>', 0)
                ->groupBy('customer_id')
                ->with('customer:id,name,phone')
                ->orderByDesc('debt_total')
                ->limit(20)
                ->get();

            $totalDebt = (float) Order::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('amount_due', '>', 0)
                ->sum('amount_due');
        }

        return view('ceo.section', [
            'pageTitle' => 'Công Nợ Khách Hàng',
            'pageSubtitle' => 'Theo dõi nợ phải thu và nhóm khách cần ưu tiên xử lý.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Tổng công nợ', 'value' => number_format($totalDebt) . ' đ'],
                ['label' => 'Khách có nợ', 'value' => number_format($rows->count())],
                ['label' => 'Đơn còn nợ', 'value' => number_format((int) $rows->sum('total_orders'))],
            ],
            'tableTitle' => 'Top khách hàng công nợ',
            'columns' => ['Khách hàng', 'Điện thoại', 'Số đơn nợ', 'Tổng nợ'],
            'rows' => $rows->map(fn ($row) => [
                (string) ($row->customer?->name ?? 'N/A'),
                (string) ($row->customer?->phone ?? '-'),
                number_format((int) $row->total_orders),
                number_format((float) $row->debt_total) . ' đ',
            ])->all(),
        ]);
    }

    public function warehouse(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $rows = Warehouse::query()
            ->withCount(['inventories as sku_count'])
            ->withSum('inventories as stock_total', 'quantity')
            ->withSum('inventories as reserved_total', 'reserved_quantity')
            ->orderByDesc('stock_total')
            ->get();

        $lowStockCount = Inventory::query()
            ->where('quantity', '>', 0)
            ->whereRaw('quantity <= COALESCE(low_stock_threshold, 5)')
            ->count();

        $outOfStock = Inventory::query()->where('quantity', '<=', 0)->count();

        return view('ceo.section', [
            'pageTitle' => 'Hiệu Suất Kho',
            'pageSubtitle' => 'Theo dõi mức tồn, hàng giữ chỗ và cảnh báo thiếu hàng.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'SKU sắp hết', 'value' => number_format($lowStockCount)],
                ['label' => 'SKU hết hàng', 'value' => number_format($outOfStock)],
                ['label' => 'Tổng kho', 'value' => number_format($rows->count())],
            ],
            'tableTitle' => 'Hiệu suất theo kho',
            'columns' => ['Kho', 'Số SKU', 'Tồn kho', 'Giữ chỗ', 'Khả dụng'],
            'rows' => $rows->map(function ($row) {
                $stock = (int) ($row->stock_total ?? 0);
                $reserved = (int) ($row->reserved_total ?? 0);
                $available = max(0, $stock - $reserved);

                return [
                    (string) $row->name,
                    number_format((int) ($row->sku_count ?? 0)),
                    number_format($stock),
                    number_format($reserved),
                    number_format($available),
                ];
            })->all(),
        ]);
    }

    public function shipper(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $rows = Order::query()
            ->select('shipper_id', DB::raw('COUNT(*) as total_orders'))
            ->selectRaw("SUM(CASE WHEN status IN ('completed', 'delivered') THEN 1 ELSE 0 END) as success_orders")
            ->selectRaw("SUM(CASE WHEN status IN ('returning', 'returned', 'returned_completed') THEN 1 ELSE 0 END) as return_orders")
            ->whereNotNull('shipper_id')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('shipper_id')
            ->with('shipper:id,name')
            ->orderByDesc('total_orders')
            ->limit(20)
            ->get();

        return view('ceo.section', [
            'pageTitle' => 'Hiệu Suất Shipper',
            'pageSubtitle' => 'Đánh giá tỷ lệ giao thành công và tỷ lệ hoàn theo shipper.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Shipper hoạt động', 'value' => number_format($rows->count())],
                ['label' => 'Tổng đơn shipper', 'value' => number_format((int) $rows->sum('total_orders'))],
                ['label' => 'Đơn hoàn/trả', 'value' => number_format((int) $rows->sum('return_orders'))],
            ],
            'tableTitle' => 'Top shipper theo khối lượng đơn',
            'columns' => ['Shipper', 'Tổng đơn', 'Giao thành công', 'Hoàn/Trả', 'Tỷ lệ thành công'],
            'rows' => $rows->map(function ($row) {
                $total = (int) $row->total_orders;
                $success = (int) $row->success_orders;
                $rate = $total > 0 ? round(($success / $total) * 100, 1) : 0;

                return [
                    (string) ($row->shipper?->name ?? 'N/A'),
                    number_format($total),
                    number_format($success),
                    number_format((int) $row->return_orders),
                    $rate . '%',
                ];
            })->all(),
        ]);
    }

    public function customers(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $rows = $this->buildCustomerTop($from, $to, 20);

        return view('ceo.section', [
            'pageTitle' => 'Khách Hàng Lớn / Tiềm Năng',
            'pageSubtitle' => 'Nhận diện nhóm khách mang doanh thu cao và cần chăm sóc ưu tiên.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Khách phát sinh đơn', 'value' => number_format($rows->count())],
                ['label' => 'Tổng doanh số top', 'value' => number_format((float) $rows->sum('total_amount')) . ' đ'],
                ['label' => 'Tổng số đơn top', 'value' => number_format((int) $rows->sum('total_orders'))],
            ],
            'tableTitle' => 'Top khách hàng theo doanh số',
            'columns' => ['Khách hàng', 'Điện thoại', 'Số đơn', 'Doanh số'],
            'rows' => $rows->map(fn ($row) => [
                (string) ($row->name ?? 'N/A'),
                (string) ($row->phone ?? '-'),
                number_format((int) $row->total_orders),
                number_format((float) $row->total_amount) . ' đ',
            ])->all(),
        ]);
    }

    public function alerts(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);
        $overview = $this->buildOverview($from, $to);
        $alerts = $this->buildAlerts($overview);

        return view('ceo.section', [
            'pageTitle' => 'Cảnh Báo CEO',
            'pageSubtitle' => 'Những vấn đề cần theo dõi và xử lý ở cấp điều hành.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Tổng cảnh báo', 'value' => number_format(count($alerts))],
                ['label' => 'Mức cao', 'value' => number_format(collect($alerts)->where('level', 'high')->count())],
                ['label' => 'Mức trung bình', 'value' => number_format(collect($alerts)->where('level', 'medium')->count())],
            ],
            'tableTitle' => 'Danh sách cảnh báo',
            'columns' => ['Mức độ', 'Tiêu đề', 'Mô tả'],
            'rows' => collect($alerts)->map(fn ($alert) => [
                strtoupper((string) $alert['level']),
                (string) $alert['title'],
                (string) $alert['description'],
            ])->all(),
        ]);
    }

    public function reports(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $monthly = Order::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total) as total_amount')
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return view('ceo.section', [
            'pageTitle' => 'Báo Cáo Điều Hành',
            'pageSubtitle' => 'Tổng hợp dữ liệu phục vụ họp điều hành theo kỳ.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Số kỳ báo cáo', 'value' => number_format($monthly->count())],
                ['label' => 'Tổng đơn 6 tháng', 'value' => number_format((int) $monthly->sum('total_orders'))],
                ['label' => 'Tổng giá trị 6 tháng', 'value' => number_format((float) $monthly->sum('total_amount')) . ' đ'],
            ],
            'tableTitle' => 'Tổng hợp theo tháng',
            'columns' => ['Tháng', 'Số đơn', 'Tổng giá trị'],
            'rows' => $monthly->map(fn ($row) => [
                (string) $row->period,
                number_format((int) $row->total_orders),
                number_format((float) $row->total_amount) . ' đ',
            ])->all(),
        ]);
    }

    private function resolveRange(Request $request): array
    {
        $range = (string) $request->input('range', 'month');
        $today = Carbon::today();

        $from = match ($range) {
            'day' => $today->copy()->startOfDay(),
            'week' => $today->copy()->startOfWeek(),
            'year' => $today->copy()->startOfYear(),
            'custom' => $request->filled('from_date')
                ? Carbon::parse((string) $request->input('from_date'))->startOfDay()
                : $today->copy()->startOfMonth(),
            default => $today->copy()->startOfMonth(),
        };

        $to = match ($range) {
            'day' => $today->copy()->endOfDay(),
            'week' => $today->copy()->endOfWeek(),
            'year' => $today->copy()->endOfYear(),
            'custom' => $request->filled('to_date')
                ? Carbon::parse((string) $request->input('to_date'))->endOfDay()
                : $today->copy()->endOfDay(),
            default => $today->copy()->endOfMonth(),
        };

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $rangeLabel = match ($range) {
            'day' => 'Theo ngày',
            'week' => 'Theo tuần',
            'year' => 'Theo năm',
            'custom' => 'Tùy chọn',
            default => 'Theo tháng',
        };

        return [$from, $to, $rangeLabel];
    }

    private function buildOverview(Carbon $from, Carbon $to): array
    {
        $totalOrders = (int) Order::query()->whereBetween('created_at', [$from, $to])->count();
        $completedOrders = (int) Order::query()->whereBetween('created_at', [$from, $to])->whereIn('status', ['completed', 'delivered'])->count();
        $cancelledOrders = (int) Order::query()->whereBetween('created_at', [$from, $to])->whereIn('status', ['cancelled', 'rejected'])->count();
        $returningOrders = (int) Order::query()->whereBetween('created_at', [$from, $to])->whereIn('status', ['returning', 'returned', 'returned_completed'])->count();

        $grossRevenue = (float) Order::query()->whereBetween('created_at', [$from, $to])->sum('total');

        $income = (float) Transaction::query()->whereBetween('created_at', [$from, $to])->where('type', 'payment')->sum('amount');
        $refund = (float) Transaction::query()->whereBetween('created_at', [$from, $to])->where('type', 'refund')->sum('amount');

        $debtTotal = 0;
        if (Schema::hasColumn('orders', 'amount_due')) {
            $debtTotal = (float) Order::query()->whereBetween('created_at', [$from, $to])->where('amount_due', '>', 0)->sum('amount_due');
        }

        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

        $lowStockCount = (int) Inventory::query()
            ->where('quantity', '>', 0)
            ->whereRaw('quantity <= COALESCE(low_stock_threshold, 5)')
            ->count();

        $outOfStockCount = (int) Inventory::query()->where('quantity', '<=', 0)->count();

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'returning_orders' => $returningOrders,
            'gross_revenue' => $grossRevenue,
            'net_revenue' => $income - $refund,
            'debt_total' => $debtTotal,
            'completion_rate' => $completionRate,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'active_customers' => (int) Customer::query()->whereHas('orders', function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to]);
            })->count(),
            'total_warehouses' => (int) Warehouse::count(),
        ];
    }

    private function buildSalesTop(Carbon $from, Carbon $to, int $limit)
    {
        return Order::query()
            ->select('user_id', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(total) as total_amount'))
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->name = $row->user?->name ?? 'N/A';

                return $row;
            });
    }

    private function buildCustomerTop(Carbon $from, Carbon $to, int $limit)
    {
        return Order::query()
            ->select('customer_id', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(total) as total_amount'))
            ->whereNotNull('customer_id')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('customer_id')
            ->with('customer:id,name,phone')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->name = $row->customer?->name ?? 'N/A';
                $row->phone = $row->customer?->phone ?? '-';

                return $row;
            });
    }

    private function buildShipperTop(Carbon $from, Carbon $to, int $limit)
    {
        return Order::query()
            ->select('shipper_id', DB::raw('COUNT(*) as total_orders'))
            ->selectRaw("SUM(CASE WHEN status IN ('completed', 'delivered') THEN 1 ELSE 0 END) as success_orders")
            ->whereNotNull('shipper_id')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('shipper_id')
            ->with('shipper:id,name')
            ->orderByDesc('total_orders')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->name = $row->shipper?->name ?? 'N/A';
                $row->success_rate = ((int) $row->total_orders) > 0
                    ? round(((int) $row->success_orders / (int) $row->total_orders) * 100, 1)
                    : 0;

                return $row;
            });
    }

    private function buildAlerts(array $overview): array
    {
        $alerts = [];

        if ($overview['debt_total'] > 50000000) {
            $alerts[] = [
                'level' => 'high',
                'title' => 'Công nợ vượt ngưỡng an toàn',
                'description' => 'Tổng công nợ đang vượt 50 triệu, cần kế hoạch thu hồi ưu tiên.',
            ];
        }

        if ($overview['completion_rate'] < 70) {
            $alerts[] = [
                'level' => 'high',
                'title' => 'Tỷ lệ hoàn tất đơn thấp',
                'description' => 'Tỷ lệ hoàn tất dưới 70%, cần rà soát quy trình bán hàng và vận hành.',
            ];
        }

        if ($overview['returning_orders'] > 0 && $overview['total_orders'] > 0) {
            $returnRate = round(($overview['returning_orders'] / $overview['total_orders']) * 100, 1);
            if ($returnRate >= 10) {
                $alerts[] = [
                    'level' => 'medium',
                    'title' => 'Tỷ lệ hoàn/trả tăng cao',
                    'description' => 'Đơn hoàn/trả đạt ' . $returnRate . '%, cần kiểm tra nguyên nhân chất lượng hoặc vận chuyển.',
                ];
            }
        }

        if ($overview['out_of_stock_count'] > 0) {
            $alerts[] = [
                'level' => 'medium',
                'title' => 'Có SKU đã hết hàng',
                'description' => 'Hiện có ' . number_format($overview['out_of_stock_count']) . ' SKU hết hàng, cần ưu tiên nhập kho.',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'level' => 'low',
                'title' => 'Không có cảnh báo nghiêm trọng',
                'description' => 'Các chỉ số hiện tại đang trong ngưỡng theo dõi bình thường.',
            ];
        }

        return $alerts;
    }
}
