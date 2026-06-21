<?php

namespace App\Http\Controllers;

use App\Models\DuckFarm;
use App\Models\DuckFarmReview;
use App\Models\DuckProcessingConversionRate;
use App\Models\ProcurementPurchase;
use App\Models\ProcurementPurchaseItem;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcurementController extends Controller
{
    private const LIVE_SIZES = [3.0, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7];
    private const PROCESSED_SIZES = [2.0, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 3.0, 3.1, 3.2];

    public function __construct()
    {
        $this->middleware(['auth', 'role:procurement_manager,admin']);
    }

    public function dashboard()
    {
        $today = now()->startOfDay();
        $farms = DuckFarm::query()->where('is_active', true)->get()->map(function (DuckFarm $farm) use ($today) {
            $base = $farm->last_purchase_at?->copy()->startOfDay();
            $farm->available_from = $base?->copy()->addDays(39);
            $farm->available_to = $base?->copy()->addDays(45);
            $farm->availability = !$base ? 'unknown' : ($farm->available_from->lte($today->copy()->addDays(7)) && $farm->available_to->gte($today) ? 'soon' : ($farm->available_to->lt($today) ? 'overdue' : 'later'));
            return $farm;
        })->filter(fn (DuckFarm $farm) => in_array($farm->availability, ['soon', 'overdue', 'unknown'], true))->sortBy(fn (DuckFarm $farm) => $farm->available_from?->timestamp ?? PHP_INT_MAX)->values();

        $todayPurchases = ProcurementPurchase::with(['farm', 'supplier', 'warehouse', 'paymentRequest'])->whereDate('purchased_at', today())->latest('purchased_at')->get();
        return view('procurement.dashboard', [
            'farms' => $farms,
            'todayPurchases' => $todayPurchases,
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'liveSizes' => self::LIVE_SIZES,
            'processedSizes' => self::PROCESSED_SIZES,
        ]);
    }

    public function purchases(Request $request)
    {
        $from = $request->filled('from_date') ? Carbon::parse($request->input('from_date'))->toDateString() : today()->startOfMonth()->toDateString();
        $to = $request->filled('to_date') ? Carbon::parse($request->input('to_date'))->toDateString() : today()->toDateString();
        $purchases = ProcurementPurchase::with(['farm', 'supplier', 'warehouse', 'paymentRequest', 'items'])
            ->whereDate('purchased_at', '>=', $from)->whereDate('purchased_at', '<=', $to)->latest('purchased_at')->paginate(30)->withQueryString();
        return view('procurement.purchases', compact('purchases', 'from', 'to'));
    }

    public function storePurchase(Request $request)
    {
        $validated = $request->validate([
            'purchase_type' => ['required', 'in:live_duck,processed_duck'],
            'duck_farm_id' => ['nullable', 'required_if:purchase_type,live_duck', 'exists:duck_farms,id'],
            'supplier_id' => ['nullable', 'required_if:purchase_type,processed_duck', 'exists:suppliers,id'],
            'duck_type' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'total_weight' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'broker_fee' => ['nullable', 'numeric', 'min:0'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:unpaid,paid,partial'],
            'duck_condition' => ['nullable', 'string', 'max:255'],
            'purchased_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'live_size' => ['nullable', 'numeric', 'in:' . implode(',', self::LIVE_SIZES)],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $purchase = DB::transaction(function () use ($validated): ProcurementPurchase {
            $quantity = (int) $validated['quantity'];
            $weight = (float) $validated['total_weight'];
            $subtotal = round($weight * (float) $validated['unit_price'], 2);
            $purchase = ProcurementPurchase::create([
                ...$validated,
                'code' => 'TM-' . now()->format('Ymd-His') . '-' . random_int(10, 99),
                'created_by' => auth()->id(),
                'average_weight' => round($weight / $quantity, 3),
                'subtotal' => $subtotal,
                'broker_fee' => (float) ($validated['broker_fee'] ?? 0),
                'processing_fee' => (float) ($validated['processing_fee'] ?? 0),
                'total_amount' => $subtotal + (float) ($validated['broker_fee'] ?? 0) + (float) ($validated['processing_fee'] ?? 0),
                'status' => ProcurementPurchase::STATUS_DRAFT,
            ]);

            if ($validated['purchase_type'] === 'live_duck') {
                $liveSize = round((float) ($validated['live_size'] ?? ($weight / $quantity)), 1);
                $rates = DuckProcessingConversionRate::where('live_size', $liveSize)->where('percentage', '>', 0)->get();
                foreach ($rates as $rate) {
                    ProcurementPurchaseItem::create([
                        'procurement_purchase_id' => $purchase->id,
                        'stage' => 'expected', 'item_type' => 'processed_duck', 'size' => $rate->processed_size,
                        'quantity' => (int) round($quantity * (float) $rate->percentage / 100),
                    ]);
                }
                foreach (['feathers', 'offal'] as $type) {
                    ProcurementPurchaseItem::create(['procurement_purchase_id' => $purchase->id, 'stage' => 'expected', 'item_type' => $type, 'quantity' => $quantity]);
                }
            } else {
                foreach (($validated['sizes'] ?? []) as $size => $sizeQuantity) {
                    if ((int) $sizeQuantity > 0) {
                        ProcurementPurchaseItem::create(['procurement_purchase_id' => $purchase->id, 'stage' => 'expected', 'item_type' => 'processed_duck', 'size' => (float) $size, 'quantity' => (int) $sizeQuantity]);
                    }
                }
            }

            if ($purchase->farm) {
                $purchase->farm->update(['last_purchase_at' => $purchase->purchased_at]);
            }
            return $purchase;
        });

        return back()->with('success', 'Đã ghi nhận lần thu mua ' . $purchase->code . '.');
    }

    public function sendToWarehouse(Request $request, ProcurementPurchase $purchase)
    {
        abort_unless($purchase->status === ProcurementPurchase::STATUS_DRAFT, 422, 'Phiếu đã được gửi hoặc tiếp nhận.');
        $validated = $request->validate(['warehouse_id' => ['required', 'exists:warehouses,id']]);
        $purchase->update(['warehouse_id' => $validated['warehouse_id'], 'status' => ProcurementPurchase::STATUS_SENT, 'sent_to_warehouse_at' => now()]);
        return back()->with('success', 'Đã gửi ' . $purchase->code . ' sang kho tiếp nhận.');
    }

    public function requestPayment(ProcurementPurchase $purchase)
    {
        abort_if($purchase->payment_transaction_id, 422, 'Lần thu mua đã có phiếu yêu cầu thanh toán.');
        $category = TransactionCategory::where('flow_direction', 'out')->where('is_active', true)->where(fn ($q) => $q->where('name', 'like', '%thu mua%')->orWhere('name', 'like', '%mua hàng%'))->first();
        $category ??= TransactionCategory::updateOrCreate(['code' => 'PROCUREMENT'], ['name' => 'Chi phí thu mua', 'flow_direction' => 'out', 'sort_order' => (int) TransactionCategory::max('sort_order') + 1, 'is_active' => true]);
        $source = $purchase->farm?->name ?? $purchase->supplier?->name ?? 'Nhà cung cấp';
        $items = [
            ['stt' => 1, 'content' => 'Thu mua ' . ($purchase->purchase_type === 'live_duck' ? 'vịt lông' : 'vịt thịt') . ' - ' . $source . ' - ' . $purchase->code, 'unit' => 'kg', 'quantity' => (float) $purchase->total_weight, 'unit_price' => (float) $purchase->unit_price, 'line_total' => (float) $purchase->subtotal],
        ];
        if ((float) $purchase->broker_fee > 0) $items[] = ['stt' => count($items) + 1, 'content' => 'Phí cò giới thiệu', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => (float) $purchase->broker_fee, 'line_total' => (float) $purchase->broker_fee];
        if ((float) $purchase->processing_fee > 0) $items[] = ['stt' => count($items) + 1, 'content' => 'Chi phí xử lý sơ chế', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => (float) $purchase->processing_fee, 'line_total' => (float) $purchase->processing_fee];
        $transaction = Transaction::create(['amount' => $purchase->total_amount, 'type' => 'extra_expense', 'transaction_category_id' => $category->id, 'note' => 'Yêu cầu thanh toán lần thu mua ' . $purchase->code, 'status' => Transaction::STATUS_PENDING_APPROVAL, 'submitted_by' => auth()->id(), 'request_source' => 'procurement', 'request_department' => 'Thu mua', 'request_title' => 'Thanh toán thu mua ' . $purchase->code, 'request_items' => $items, 'request_subtotal' => $purchase->total_amount, 'request_vat' => 0, 'request_total' => $purchase->total_amount]);
        $purchase->update(['payment_transaction_id' => $transaction->id]);
        app(ApprovalService::class)->initTransactionApproval($transaction);
        return back()->with('success', 'Đã gửi phiếu yêu cầu thanh toán #' . $transaction->id . '.');
    }

    public function farms()
    {
        return view('procurement.farms', ['farms' => DuckFarm::with(['reviews.user'])->latest()->get()]);
    }

    public function storeFarm(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'address' => ['nullable', 'string', 'max:1000'], 'scale' => ['nullable', 'integer', 'min:0'], 'duck_breed' => ['nullable', 'string', 'max:255'], 'business_type' => ['required', 'in:individual,household,company,cooperative'], 'raising_days' => ['required', 'integer', 'min:30', 'max:60'], 'notes' => ['nullable', 'string', 'max:1000']]);
        DuckFarm::create($data);
        return back()->with('success', 'Đã thêm trang trại.');
    }

    public function updateFarm(Request $request, DuckFarm $farm)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'address' => ['nullable', 'string', 'max:1000'], 'scale' => ['nullable', 'integer', 'min:0'], 'duck_breed' => ['nullable', 'string', 'max:255'], 'business_type' => ['required', 'in:individual,household,company,cooperative'], 'raising_days' => ['required', 'integer', 'min:30', 'max:60'], 'notes' => ['nullable', 'string', 'max:1000'], 'is_active' => ['nullable', 'boolean']]);
        $farm->update([...$data, 'is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Đã cập nhật trang trại.');
    }

    public function reviewFarm(Request $request, DuckFarm $farm)
    {
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:1000']]);
        DuckFarmReview::create([...$data, 'duck_farm_id' => $farm->id, 'user_id' => auth()->id()]);
        $farm->update(['rating' => round((float) $farm->reviews()->avg('rating'), 2)]);
        return back()->with('success', 'Đã ghi nhận đánh giá trang trại.');
    }

    public function conversions()
    {
        $rates = DuckProcessingConversionRate::all()->groupBy(fn ($rate) => number_format((float) $rate->live_size, 1));
        return view('procurement.conversions', ['rates' => $rates, 'liveSizes' => self::LIVE_SIZES, 'processedSizes' => self::PROCESSED_SIZES]);
    }

    public function storeConversions(Request $request)
    {
        $data = $request->validate(['rates' => ['required', 'array'], 'rates.*.*' => ['nullable', 'numeric', 'min:0', 'max:100']]);
        DB::transaction(function () use ($data): void {
            foreach ($data['rates'] as $liveSize => $sizes) {
                $total = collect($sizes)->sum(fn ($value) => (float) ($value ?: 0));
                if ($total > 0 && abs($total - 100) > 0.01) abort(422, 'Tổng tỷ lệ size vịt lông ' . $liveSize . ' phải bằng 100%.');
                foreach ($sizes as $processedSize => $percentage) DuckProcessingConversionRate::updateOrCreate(['live_size' => $liveSize, 'processed_size' => $processedSize], ['percentage' => (float) ($percentage ?: 0)]);
            }
        });
        return back()->with('success', 'Đã lưu ma trận tỷ lệ quy đổi sơ chế.');
    }
}
