<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DailyOrderSchedule;
use App\Models\OrderSchedule;
use App\Models\ProductVariant;
use App\Services\OrderScheduleService;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class OrderScheduleController extends Controller
{
    protected $settings;
    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }
    public function index(Request $request)
    {
        $settings  = $this->settings;
        $userId    = auth()->id();

        $status    = (string) $request->input('status', 'all');
        $allowed   = ['all', 'pending', 'need_review', 'approved', 'generated'];
        if (!in_array($status, $allowed, true)) {
            $status = 'all';
        }

        $customerId = (int) $request->input('customer_id', 0);
        $fromDate   = $request->input('from_date', '');
        $toDate     = $request->input('to_date', '');
        $search     = trim((string) $request->input('search', ''));

        $sortAllowed = ['schedule_date', 'customer_name', 'is_active'];
        $sort        = $request->input('sort', 'schedule_date');
        $sortDir     = $request->input('sort_dir', 'desc');
        if (!in_array($sort, $sortAllowed, true)) $sort = 'schedule_date';
        if (!in_array($sortDir, ['asc', 'desc'], true)) $sortDir = 'desc';

        $baseQuery = fn () => OrderSchedule::query()->where('created_by', $userId);

        $query = OrderSchedule::query()
            ->with(['customer:id,name,phone', 'creator:id,name', 'generatedOrder:id,code', 'items.variant.product'])
            ->where('created_by', $userId)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($customerId > 0, fn ($q) => $q->where('customer_id', $customerId))
            ->when($fromDate, fn ($q) => $q->whereDate('schedule_date', '>=', $fromDate))
            ->when($toDate,   fn ($q) => $q->whereDate('schedule_date', '<=', $toDate))
            ->when($search, function ($q) use ($search) {
                $q->whereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when($sort === 'customer_name', function ($q) use ($sortDir) {
                $q->join('customers as sc', 'sc.id', '=', 'order_schedules.customer_id')
                  ->orderBy('sc.name', $sortDir)
                  ->select('order_schedules.*');
            })
            ->when($sort !== 'customer_name', fn ($q) => $q->orderBy($sort, $sortDir))
            ->orderByDesc('order_schedules.id');

        $schedules = $query->paginate((int) $request->input('per_page', 20))->withQueryString();

        $counts = [
            'pending'     => ($baseQuery)()->where('status', 'pending')->count(),
            'need_review' => ($baseQuery)()->where('status', 'need_review')->count(),
            'approved'    => ($baseQuery)()->where('status', 'approved')->count(),
            'generated'   => ($baseQuery)()->where('status', 'generated')->count(),
        ];

        // Customers for sidebar filter — only those who have schedules created by this user
        $schedCounts = OrderSchedule::where('created_by', $userId)
            ->selectRaw('customer_id, count(*) as schedule_count')
            ->groupBy('customer_id')
            ->pluck('schedule_count', 'customer_id');

        $myCustomers = Customer::whereIn('id', $schedCounts->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->each(fn ($c) => $c->schedule_count = (int) ($schedCounts[$c->id] ?? 0));

        $viewData = [
            'schedules'        => $schedules,
            'activeStatus'     => $status,
            'counts'           => $counts,
            'myCustomers'      => $myCustomers,
            'activeCustomerId' => $customerId,
            'fromDate'         => $fromDate,
            'toDate'           => $toDate,
            'search'           => $search,
            'settings'         => $settings,
            'sort'             => $sort,
            'sortDir'          => $sortDir,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html'         => view('site.my_customer.schedules._results', $viewData)->render(),
                'counts'       => $counts,
                'total'        => $schedules->total(),
                'activeStatus' => $status,
            ]);
        }

        return view('site.my_customer.schedules.index', $viewData);
    }

    public function create()
    {
        $customers = Customer::query()
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhere('assigned_to', auth()->id());
            })
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'address', 'company_name', 'customer_code']);

        return view('site.my_customer.schedules.create', [
            'customers' => $customers,
            'selectedCustomer' => null,
            'settings' => $this->settings,
        ]);
    }

    public function createForCustomer(Customer $customer)
    {
        abort_unless(
            $customer->user_id === auth()->id() || $customer->assigned_to === auth()->id(),
            403
        );

        $customers = Customer::query()
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhere('assigned_to', auth()->id());
            })
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'address', 'company_name', 'customer_code']);

        return view('site.my_customer.schedules.create', [
            'customers' => $customers,
            'selectedCustomer' => $customer,
            'settings' => $this->settings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'schedule_mode' => ['required', 'in:specific_dates,daily_auto'],
            'approval_required' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $scheduleMode = (string) $validated['schedule_mode'];

        if ($scheduleMode === 'specific_dates') {
            $request->validate([
                'schedule_dates' => ['required', 'array', 'min:1'],
                'schedule_dates.*' => ['required', 'date', 'after_or_equal:today'],
            ]);
        }

        $customer = Customer::query()
            ->where('id', (int) $validated['customer_id'])
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhere('assigned_to', auth()->id());
            })
            ->firstOrFail();

        $variantIds = collect($validated['items'])->pluck('variant_id')->map(fn ($id) => (int) $id)->unique()->values();
        $variants = ProductVariant::query()
            ->with(['latestPriceRule'])
            ->whereIn('id', $variantIds->all())
            ->get()
            ->keyBy('id');

        if ($scheduleMode === 'daily_auto') {
            $dailySchedule = DailyOrderSchedule::create([
                'customer_id' => $customer->id,
                'created_by' => auth()->id(),
                'approval_required' => $request->boolean('approval_required'),
                'is_active' => true,
                'start_date' => now()->toDateString(),
                'meta' => [
                    'created_from' => 'my_customer.schedules.create',
                ],
            ]);

            foreach ($validated['items'] as $item) {
                $variantId = (int) $item['variant_id'];
                $qty = (int) $item['quantity'];
                if ($qty <= 0) {
                    continue;
                }

                $variant = $variants->get($variantId);
                if (!$variant) {
                    continue;
                }

                $scheduledPrice = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);

                $dailySchedule->items()->create([
                    'product_id' => (int) $variant->product_id,
                    'product_variant_id' => $variantId,
                    'quantity' => $qty,
                    'scheduled_price' => $scheduledPrice,
                ]);
            }

            if ($dailySchedule->items()->count() === 0) {
                $dailySchedule->delete();

                return back()->withInput()->with('error', 'Không có sản phẩm hợp lệ để tạo lên đơn mỗi ngày.');
            }

            return redirect()->route('my_customer.schedules.index')
                ->with('success', $request->boolean('approval_required')
                    ? 'Đã tạo cấu hình lên đơn mỗi ngày. Mỗi ngày hệ thống sẽ tạo lịch và chờ sale duyệt.'
                    : 'Đã tạo cấu hình lên đơn mỗi ngày. Hệ thống sẽ tự tạo đơn hằng ngày từ hôm nay.');
        }

        $dateValidation = $request->validate([
            'schedule_dates' => ['required', 'array', 'min:1'],
            'schedule_dates.*' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $dateStrings = collect($dateValidation['schedule_dates'])
            ->map(fn ($d) => date('Y-m-d', strtotime((string) $d)))
            ->unique()
            ->sort()
            ->values();

        $createdCount = 0;

        foreach ($dateStrings as $date) {
            $schedule = OrderSchedule::create([
                'customer_id' => $customer->id,
                'schedule_date' => $date,
                'status' => 'pending',
                'price_status' => 'ok',
                'stock_status' => 'ok',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $variantId = (int) $item['variant_id'];
                $qty = (int) $item['quantity'];
                if ($qty <= 0) {
                    continue;
                }

                $variant = $variants->get($variantId);
                if (!$variant) {
                    continue;
                }

                $scheduledPrice = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);

                // Kho chưa biết vào ngày hẹn — không so sánh lúc tạo lịch
                $schedule->items()->create([
                    'product_id' => (int) $variant->product_id,
                    'product_variant_id' => $variantId,
                    'quantity' => $qty,
                    'scheduled_price' => $scheduledPrice,
                    'current_price' => $scheduledPrice,
                    'price_diff' => false,
                    'stock_available' => 0,
                    'stock_diff' => false,
                ]);
            }

            if ($schedule->items()->count() === 0) {
                $schedule->delete();
                continue;
            }

            $createdCount++;
        }

        return redirect()->route('my_customer.schedules.index')
            ->with('success', "Đã tạo {$createdCount} lịch lên đơn.");
    }

    public function edit(OrderSchedule $schedule)
    {
        abort_unless((int) $schedule->created_by === (int) auth()->id() || auth()->user()?->hasRole('admin'), 403);
        abort_if($schedule->status === 'generated', 403, 'Không thể sửa lịch đã tạo đơn.');

        $schedule->load(['customer:id,name,phone', 'items.variant.product']);

        return view('site.my_customer.schedules.edit', [
            'schedule' => $schedule,
            'settings' => $this->settings,
        ]);
    }

    public function update(Request $request, OrderSchedule $schedule)
    {
        abort_unless((int) $schedule->created_by === (int) auth()->id() || auth()->user()?->hasRole('admin'), 403);
        abort_if($schedule->status === 'generated', 403, 'Không thể sửa lịch đã tạo đơn.');

        $validated = $request->validate([
            'schedule_date' => ['required', 'date'],
            'items'         => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $date = date('Y-m-d', strtotime((string) $validated['schedule_date']));

        $variantIds = collect($validated['items'])->pluck('variant_id')->map(fn ($id) => (int) $id)->unique()->values();
        $variants = ProductVariant::query()
            ->with(['latestPriceRule'])
            ->whereIn('id', $variantIds->all())
            ->get()
            ->keyBy('id');

        $schedule->update([
            'schedule_date' => $date,
            'status'        => 'pending',
            'price_status'  => 'ok',
            'stock_status'  => 'ok',
        ]);

        $schedule->items()->delete();

        foreach ($validated['items'] as $item) {
            $variantId = (int) $item['variant_id'];
            $qty       = (int) $item['quantity'];
            if ($qty <= 0) continue;

            $variant = $variants->get($variantId);
            if (!$variant) continue;

            $scheduledPrice = (float) ($variant->latestPriceRule?->price ?? $variant->final_price ?? 0);

            // Kho chưa biết vào ngày hẹn — không so sánh lúc sửa lịch
            $schedule->items()->create([
                'product_id'         => (int) $variant->product_id,
                'product_variant_id' => $variantId,
                'quantity'           => $qty,
                'scheduled_price'    => $scheduledPrice,
                'current_price'      => $scheduledPrice,
                'price_diff'         => false,
                'stock_available'    => 0,
                'stock_diff'         => false,
            ]);
        }

        if ($schedule->items()->count() === 0) {
            return back()->with('error', 'Không có sản phẩm hợp lệ, lịch không được cập nhật.');
        }

        return redirect()->route('my_customer.schedules.index')
            ->with('success', 'Đã cập nhật lịch lên đơn #' . $schedule->id . '.');
    }

    public function destroy(OrderSchedule $schedule)
    {
        abort_unless((int) $schedule->created_by === (int) auth()->id() || auth()->user()?->hasRole('admin'), 403);
        abort_if($schedule->status === 'generated', 403, 'Không thể xoá lịch đã tạo đơn.');

        $schedule->items()->delete();
        $schedule->delete();

        return redirect()->route('my_customer.schedules.index')
            ->with('success', 'Đã xoá lịch #' . $schedule->id . '.');
    }

    public function show(OrderSchedule $schedule)
    {
        abort_unless((int) $schedule->created_by === (int) auth()->id() || auth()->user()?->hasRole('admin'), 403);

        $schedule->load(['customer:id,name,phone', 'items.variant.product']);

        return view('site.my_customer.schedules.show', [
            'schedule' => $schedule,
            'settings' => $this->settings,  
        ]);
    }

    public function toggleActive(OrderSchedule $schedule)
    {
        abort_unless((int) $schedule->created_by === (int) auth()->id() || auth()->user()?->hasRole('admin'), 403);

        $schedule->update(['is_active' => !$schedule->is_active]);

        return response()->json([
            'is_active' => $schedule->is_active,
            'message'   => $schedule->is_active ? 'Đã bật lên đơn tự động.' : 'Đã tắt lên đơn tự động.',
        ]);
    }

    public function evaluateToday(OrderScheduleService $service)
    {
        $todaySchedules = OrderSchedule::query()
            ->whereDate('schedule_date', now()->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->where('is_active', true)
            ->whereNull('generated_order_id')
            ->when(!auth()->user()?->hasRole('admin'), function ($q) {
                $q->where('created_by', auth()->id());
            })
            ->get();

        $changed = 0;
        foreach ($todaySchedules as $schedule) {
            $service->evaluateSchedule($schedule);
            $changed++;
        }

        return back()->with('success', "Đã kiểm tra {$changed} lịch hôm nay.");
    }

    public function generateFromReview(Request $request, OrderSchedule $schedule, OrderScheduleService $service)
    {
        abort_unless((int) $schedule->created_by === (int) auth()->id() || auth()->user()?->hasRole('admin'), 403);

        if (now()->startOfDay()->lt($schedule->schedule_date->startOfDay())) {
            return back()->with('error', 'Chỉ được tạo đơn vào ngày hẹn hoặc sau ngày hẹn.');
        }

        if ($request->boolean('no_generate')) {
            return back()->with('success', 'Đã giữ lịch, chưa tạo đơn.');
        }

        $decisions = [];
        foreach ($schedule->items as $item) {
            $action = (string) $request->input("decision.{$item->id}.action", 'keep');
            $approvedQty = (int) $request->input("decision.{$item->id}.approved_quantity", $item->quantity);
            $decisions[$item->id] = [
                'action' => $action,
                'approved_quantity' => $approvedQty,
            ];
        }

        $order = $service->generateOrder($schedule, $decisions);
        if (!$order) {
            return back()->with('error', 'Không còn sản phẩm hợp lệ để tạo đơn.');
        }

        return redirect()->route('site.orders.show', $order)->with('success', 'Đã tạo đơn từ lịch thành công.');
    }
}
