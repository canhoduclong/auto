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
use Illuminate\Support\Str;
use Throwable;

class ProcurementController extends Controller
{
    private const LIVE_SIZES = [3.0, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7];
    private const PROCESSED_SIZES = [2.0, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 3.0, 3.1, 3.2];

    public function __construct()
    {
        $this->middleware(['auth', 'role:procurement_manager,admin']);
    }

    public function dashboard(Request $request)
    {
        $today = now()->startOfDay();
        $period = in_array($request->input('period'), ['day', 'week', 'month'], true) ? $request->input('period') : 'month';
        try {
            $referenceDate = Carbon::parse($request->input('date', today()->toDateString()))->startOfDay();
        } catch (Throwable) {
            $referenceDate = today();
        }
        [$from, $to] = match ($period) {
            'day' => [$referenceDate->copy()->startOfDay(), $referenceDate->copy()->endOfDay()],
            'week' => [$referenceDate->copy()->startOfWeek(), $referenceDate->copy()->endOfWeek()],
            default => [$referenceDate->copy()->startOfMonth(), $referenceDate->copy()->endOfMonth()],
        };
        $purchaseFarms = DuckFarm::query()->where('is_active', true)->orderBy('name')->get();
        $farms = $purchaseFarms->map(function (DuckFarm $farm) use ($today) {
            $base = $farm->last_purchase_at?->copy()->startOfDay();
            $farm->available_from = $base?->copy()->addDays(39);
            $farm->available_to = $base?->copy()->addDays(45);
            $farm->raising_age_days = $base ? max(0, (int) floor($base->diffInDays($today, false))) : null;
            $farm->availability = !$base ? 'unknown' : ($farm->available_from->lte($today->copy()->addDays(7)) && $farm->available_to->gte($today) ? 'soon' : ($farm->available_to->lt($today) ? 'overdue' : 'later'));
            return $farm;
        })->filter(fn (DuckFarm $farm) => $farm->raising_age_days !== null
            && $farm->raising_age_days >= 39
            && $farm->raising_age_days <= 50
        )->sortBy('raising_age_days')->values();

        $dashboardPurchases = ProcurementPurchase::with(['farm', 'supplier', 'warehouse', 'paymentRequest', 'items'])
            ->whereBetween('purchased_at', [$from, $to])->latest('purchased_at')->get();
        $receivedPurchases = $dashboardPurchases->where('status', ProcurementPurchase::STATUS_RECEIVED);
        $quantity = (int) $dashboardPurchases->sum('quantity');
        $weight = (float) $dashboardPurchases->sum('total_weight');
        $subtotal = (float) $dashboardPurchases->sum('subtotal');
        $transportCost = (float) $dashboardPurchases->sum('transportation_fee');
        $operatingCost = (float) $dashboardPurchases->sum(fn (ProcurementPurchase $purchase) =>
            (float) $purchase->broker_fee + (float) $purchase->processing_fee + (float) $purchase->procurement_fee + (float) $purchase->other_fee
        );
        $receivedQuantity = (int) $receivedPurchases->sum(fn (ProcurementPurchase $purchase) => $purchase->items->where('stage', 'received')->whereIn('item_type', ['processed_duck', 'reject'])->sum('quantity'));
        $rejectQuantity = (int) $receivedPurchases->sum(fn (ProcurementPurchase $purchase) => $purchase->items->where('stage', 'received')->where('item_type', 'reject')->sum('quantity'));
        $receivedBaseQuantity = (int) $receivedPurchases->sum('quantity');
        $lossQuantity = max(0, $receivedBaseQuantity - $receivedQuantity);
        $lossRate = $receivedBaseQuantity > 0 ? $lossQuantity * 100 / $receivedBaseQuantity : 0;
        $rejectRate = $receivedBaseQuantity > 0 ? $rejectQuantity * 100 / $receivedBaseQuantity : 0;
        $averageRating = (float) ($receivedPurchases->whereNotNull('warehouse_rating')->avg('warehouse_rating') ?? 0);

        $dailyData = collect();
        for ($cursor = $from->copy()->startOfDay(); $cursor->lte($to); $cursor->addDay()) {
            $dayPurchases = $dashboardPurchases->filter(fn (ProcurementPurchase $purchase) => $purchase->purchased_at->isSameDay($cursor));
            $dailyData->push([
                'label' => $cursor->format('d/m'),
                'quantity' => (int) $dayPurchases->sum('quantity'),
                'price' => (float) ($dayPurchases->sum('total_weight') > 0 ? $dayPurchases->sum('subtotal') / $dayPurchases->sum('total_weight') : 0),
                'transport_per_duck' => (float) ($dayPurchases->sum('quantity') > 0 ? $dayPurchases->sum('transportation_fee') / $dayPurchases->sum('quantity') : 0),
            ]);
        }
        $sourceStructure = $dashboardPurchases->groupBy(fn (ProcurementPurchase $purchase) => $purchase->farm?->name ?? $purchase->supplier?->name ?? 'Khác')
            ->map(fn ($purchases, $name) => ['name' => $name, 'quantity' => (int) $purchases->sum('quantity')])
            ->sortByDesc('quantity')->values();
        $supplierRanking = $dashboardPurchases->groupBy(fn (ProcurementPurchase $purchase) => $purchase->farm?->name ?? $purchase->supplier?->name ?? 'Khác')
            ->map(function ($purchases, $name) {
                $totalWeight = (float) $purchases->sum('total_weight');
                $received = $purchases->where('status', ProcurementPurchase::STATUS_RECEIVED);
                return [
                    'name' => $name,
                    'quantity' => (int) $purchases->sum('quantity'),
                    'average_price' => $totalWeight > 0 ? (float) $purchases->sum('subtotal') / $totalWeight : 0,
                    'rating' => (float) ($received->whereNotNull('warehouse_rating')->avg('warehouse_rating') ?? 0),
                    'reject_rate' => $received->sum('quantity') > 0 ? (float) $received->sum(fn ($purchase) => $purchase->items->where('stage', 'received')->where('item_type', 'reject')->sum('quantity')) * 100 / $received->sum('quantity') : 0,
                ];
            })->sortByDesc(fn ($supplier) => $supplier['rating'] * 1000000 + $supplier['quantity'])->values();

        return view('procurement.dashboard', [
            'farms' => $farms,
            'purchaseFarms' => $purchaseFarms,
            'dashboardPurchases' => $dashboardPurchases,
            'period' => $period,
            'referenceDate' => $referenceDate,
            'from' => $from,
            'to' => $to,
            'dailyData' => $dailyData,
            'sourceStructure' => $sourceStructure,
            'supplierRanking' => $supplierRanking,
            'metrics' => [
                'trip_count' => $dashboardPurchases->count(), 'quantity' => $quantity, 'weight' => $weight,
                'average_price' => $weight > 0 ? $subtotal / $weight : 0, 'loss_rate' => $lossRate,
                'reject_rate' => $rejectRate, 'transport_cost' => $transportCost,
                'transport_per_duck' => $quantity > 0 ? $transportCost / $quantity : 0,
                'operating_cost' => $operatingCost, 'operating_per_duck' => $quantity > 0 ? $operatingCost / $quantity : 0,
                'total_cost' => (float) $dashboardPurchases->sum('total_amount'), 'average_rating' => $averageRating,
                'pending_warehouse' => $dashboardPurchases->where('status', ProcurementPurchase::STATUS_SENT)->count(),
                'unpaid' => (float) $dashboardPurchases->sum('remaining_amount'),
            ],
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
        return view('procurement.purchases', [
            'purchases' => $purchases,
            'from' => $from,
            'to' => $to,
            'purchaseFarms' => DuckFarm::query()->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'processedSizes' => self::PROCESSED_SIZES,
        ]);
    }

    public function storePurchase(Request $request)
    {
        $validated = $request->validate([
            'purchase_type' => ['required', 'in:live_duck,processed_duck'],
            'duck_farm_id' => ['nullable', 'required_if:purchase_type,live_duck', 'exists:duck_farms,id'],
            'supplier_id' => ['nullable', 'required_if:purchase_type,processed_duck', 'exists:suppliers,id'],
            'duck_type' => ['required', 'string', 'max:255'],
            'duck_type_other' => ['nullable', 'string', 'max:255', 'required_if:duck_type,other'],
            'farm_type' => ['required', 'string', 'max:255'],
            'farm_type_other' => ['nullable', 'string', 'max:255', 'required_if:farm_type,other'],
            'quantity' => ['required', 'integer', 'min:1'],
            'total_weight' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'broker_fee' => ['nullable', 'numeric', 'min:0'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'procurement_fee' => ['nullable', 'numeric', 'min:0'],
            'transportation_fee' => ['nullable', 'numeric', 'min:0'],
            'other_fee' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_due_date' => ['nullable', 'date'],
            'payment_status' => ['nullable', 'in:unpaid,paid,partial'],
            'duck_condition' => ['nullable', 'string', 'max:255'],
            'purchased_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'live_size' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['duck_type'] = ($validated['duck_type'] ?? null) === 'other'
            ? $validated['duck_type_other']
            : ($validated['duck_type'] ?? null);
        $validated['farm_type'] = ($validated['farm_type'] ?? null) === 'other'
            ? $validated['farm_type_other']
            : ($validated['farm_type'] ?? null);
        unset($validated['duck_type_other'], $validated['farm_type_other']);

        $purchase = DB::transaction(function () use ($validated): ProcurementPurchase {
            $quantity = (int) $validated['quantity'];
            $weight = (float) $validated['total_weight'];
            $subtotal = round($weight * (float) $validated['unit_price'], 2);
            $total = $subtotal + (float) ($validated['broker_fee'] ?? 0) + (float) ($validated['processing_fee'] ?? 0) + (float) ($validated['procurement_fee'] ?? 0) + (float) ($validated['transportation_fee'] ?? 0) + (float) ($validated['other_fee'] ?? 0);
            $paid = (float) ($validated['paid_amount'] ?? 0);
            $remaining = max(0, $total - $paid);
            $purchase = ProcurementPurchase::create([
                ...$validated,
                'code' => 'TM-' . now()->format('Ymd-His') . '-' . random_int(10, 99),
                'created_by' => auth()->id(),
                'average_weight' => round((float) ($validated['live_size'] ?? ($weight / $quantity)), 3),
                'subtotal' => $subtotal,
                'broker_fee' => (float) ($validated['broker_fee'] ?? 0),
                'processing_fee' => (float) ($validated['processing_fee'] ?? 0),
                'procurement_fee' => (float) ($validated['procurement_fee'] ?? 0),
                'transportation_fee' => (float) ($validated['transportation_fee'] ?? 0),
                'other_fee' => (float) ($validated['other_fee'] ?? 0),
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'payment_status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
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

    public function importPastedPurchases(Request $request)
    {
        $data = $request->validate([
            'paste_data' => ['required', 'string', 'max:2000000'],
        ]);

        $lines = preg_split('/\R/u', trim($data['paste_data'])) ?: [];
        $rows = collect($lines)
            ->map(fn (string $line): array => str_getcsv($line, "\t"))
            ->filter(fn (array $row): bool => collect($row)->contains(fn ($cell) => trim((string) $cell) !== ''))
            ->values();

        if ($rows->isEmpty()) {
            return back()->withErrors(['paste_data' => 'Không tìm thấy dữ liệu trong nội dung đã dán.']);
        }

        $fixedColumns = ['purchased_at', 'farm_name', 'address', 'phone', 'farm_type', 'quantity', 'total_weight', 'average_weight', 'unit_price', 'total_amount', 'paid_amount', 'remaining_amount', 'payment_due_date', 'notes', 'duck_type'];
        $headerAliases = [
            'ngay thang' => 'purchased_at', 'ngay' => 'purchased_at',
            'chu trai' => 'farm_name', 'ten chu trai' => 'farm_name', 'trang trai' => 'farm_name',
            'dia chi' => 'address', 'so dien thoai' => 'phone', 'dien thoai' => 'phone', 'sdt' => 'phone',
            'trai' => 'farm_type', 'loai trai' => 'farm_type', 'sl con' => 'quantity', 'so luong' => 'quantity',
            'khoi luong' => 'total_weight', 'so kg' => 'total_weight', 'tb size' => 'average_weight', 'size tb' => 'average_weight',
            'gia mua' => 'unit_price', 'gia mua kg' => 'unit_price', 'tong tien' => 'total_amount',
            'thanh toan' => 'paid_amount', 'da thanh toan' => 'paid_amount', 'con lai' => 'remaining_amount',
            'ngay phai tra' => 'payment_due_date', 'han thanh toan' => 'payment_due_date', 'ghi chu' => 'notes',
            'loai vit' => 'duck_type', 'giong vit' => 'duck_type',
        ];
        $firstRow = $rows->first();
        $normalizedHeaders = collect($firstRow)->map(fn ($value): string => $this->normalizePastedHeader((string) $value));
        $recognizedHeaders = $normalizedHeaders->filter(fn (string $header): bool => isset($headerAliases[$header]))->count();
        $hasHeader = $recognizedHeaders >= 4;
        $columnMap = $hasHeader
            ? $normalizedHeaders->map(fn (string $header) => $headerAliases[$header] ?? null)->all()
            : $fixedColumns;
        $dataRows = $hasHeader ? $rows->slice(1)->values() : $rows;

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($dataRows as $index => $cells) {
            $rowNumber = $index + ($hasHeader ? 2 : 1);
            $row = [];
            foreach ($columnMap as $cellIndex => $field) {
                if ($field !== null) {
                    $row[$field] = trim((string) ($cells[$cellIndex] ?? ''));
                }
            }

            try {
                $purchasedAt = $this->parsePastedDate($row['purchased_at'] ?? '');
                $farmName = trim((string) ($row['farm_name'] ?? ''));
                $quantity = (int) round($this->parsePastedNumber($row['quantity'] ?? ''));
                $weight = $this->parsePastedNumber($row['total_weight'] ?? '');
                if (!$purchasedAt || $farmName === '' || $quantity <= 0 || $weight <= 0) {
                    throw new \InvalidArgumentException('Thiếu ngày, chủ trại, số lượng hoặc khối lượng.');
                }

                $phone = preg_replace('/\D+/', '', (string) ($row['phone'] ?? ''));
                $address = trim((string) ($row['address'] ?? ''));
                $farm = null;
                if ($phone !== '') {
                    $farm = DuckFarm::where('phone', $phone)->first();
                }
                $farm ??= DuckFarm::where('name', $farmName)
                    ->when($address !== '', fn ($query) => $query->where('address', $address))
                    ->first();
                $farm ??= DuckFarm::create([
                    'name' => $farmName, 'phone' => $phone ?: null, 'address' => $address ?: null,
                    'business_type' => 'individual', 'raising_days' => 45, 'is_active' => true,
                ]);
                $farm->fill([
                    'phone' => $farm->phone ?: ($phone ?: null),
                    'address' => $farm->address ?: ($address ?: null),
                ])->save();

                $unitPrice = $this->parsePastedNumber($row['unit_price'] ?? '');
                $calculatedSubtotal = round($weight * $unitPrice, 2);
                $total = $this->hasPastedValue($row['total_amount'] ?? '')
                    ? $this->parsePastedNumber($row['total_amount'])
                    : $calculatedSubtotal;
                $paid = $this->parsePastedNumber($row['paid_amount'] ?? '');
                $remaining = $this->hasPastedValue($row['remaining_amount'] ?? '')
                    ? $this->parsePastedNumber($row['remaining_amount'])
                    : max(0, $total - $paid);

                $duplicate = ProcurementPurchase::query()
                    ->where('duck_farm_id', $farm->id)
                    ->whereDate('purchased_at', $purchasedAt->toDateString())
                    ->where('quantity', $quantity)
                    ->where('total_weight', round($weight, 3))
                    ->where('total_amount', round($total, 2))
                    ->exists();
                if ($duplicate) {
                    $skipped++;
                    continue;
                }

                DB::transaction(function () use ($row, $farm, $purchasedAt, $quantity, $weight, $unitPrice, $calculatedSubtotal, $total, $paid, $remaining): void {
                    $averageWeight = $this->hasPastedValue($row['average_weight'] ?? '')
                        ? $this->parsePastedNumber($row['average_weight'])
                        : round($weight / $quantity, 3);
                    $farmType = trim((string) ($row['farm_type'] ?? ''));
                    $farmType = match (Str::lower(Str::ascii($farmType))) {
                        'lanh' => 'Lạnh', 'ho' => 'Hở', default => $farmType,
                    };
                    $purchase = ProcurementPurchase::create([
                        'code' => $this->newImportedPurchaseCode($purchasedAt),
                        'purchase_type' => 'live_duck', 'duck_farm_id' => $farm->id, 'created_by' => auth()->id(),
                        'duck_type' => trim((string) ($row['duck_type'] ?? '')) ?: 'Chưa ghi nhận',
                        'farm_type' => $farmType ?: 'Chưa ghi nhận', 'quantity' => $quantity,
                        'total_weight' => $weight, 'average_weight' => $averageWeight, 'unit_price' => $unitPrice,
                        'subtotal' => $calculatedSubtotal, 'total_amount' => $total, 'paid_amount' => $paid,
                        'remaining_amount' => $remaining, 'payment_due_date' => $this->parsePastedDate($row['payment_due_date'] ?? '')?->toDateString(),
                        'payment_status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                        'purchased_at' => $purchasedAt, 'status' => ProcurementPurchase::STATUS_DRAFT,
                        'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                    ]);
                    foreach (['feathers', 'offal'] as $itemType) {
                        ProcurementPurchaseItem::create(['procurement_purchase_id' => $purchase->id, 'stage' => 'expected', 'item_type' => $itemType, 'quantity' => $quantity]);
                    }
                });

                if (!$farm->last_purchase_at || $purchasedAt->greaterThan($farm->last_purchase_at)) {
                    $farm->update(['last_purchase_at' => $purchasedAt]);
                }
                $created++;
            } catch (Throwable $exception) {
                $errors[] = 'Dòng ' . $rowNumber . ': ' . $exception->getMessage();
            }
        }

        $message = "Đã nhập {$created} dòng nhật ký" . ($skipped ? ", bỏ qua {$skipped} dòng trùng" : '') . '.';
        return back()->with('success', $message)->with('import_errors', array_slice($errors, 0, 20));
    }

    private function normalizePastedHeader(string $header): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($header))), ' ');
    }

    private function hasPastedValue(?string $value): bool
    {
        return trim((string) $value) !== '';
    }

    private function parsePastedNumber(?string $value): float
    {
        $number = preg_replace('/[^0-9,\.\-]/u', '', str_replace(["\u{00A0}", ' '], '', (string) $value));
        if ($number === '' || $number === '-') return 0;
        if (str_contains($number, ',')) {
            $number = str_replace('.', '', $number);
            $number = str_replace(',', '.', $number);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $number)) {
            $number = str_replace('.', '', $number);
        }
        return (float) $number;
    }

    private function parsePastedDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->startOfDay();
        }
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            try {
                return Carbon::createFromFormat('!' . $format, $value);
            } catch (Throwable) {
            }
        }
        return null;
    }

    private function newImportedPurchaseCode(Carbon $date): string
    {
        do {
            $code = 'TM-IMPORT-' . $date->format('Ymd') . '-' . random_int(10000, 99999);
        } while (ProcurementPurchase::where('code', $code)->exists());
        return $code;
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
        abort_if((float) $purchase->remaining_amount <= 0, 422, 'Lần thu mua này đã thanh toán đủ.');
        $category = TransactionCategory::where('flow_direction', 'out')->where('is_active', true)->where(fn ($q) => $q->where('name', 'like', '%thu mua%')->orWhere('name', 'like', '%mua hàng%'))->first();
        $category ??= TransactionCategory::updateOrCreate(['code' => 'PROCUREMENT'], ['name' => 'Chi phí thu mua', 'flow_direction' => 'out', 'sort_order' => (int) TransactionCategory::max('sort_order') + 1, 'is_active' => true]);
        $source = $purchase->farm?->name ?? $purchase->supplier?->name ?? 'Nhà cung cấp';
        $items = [
            ['stt' => 1, 'content' => 'Thu mua ' . ($purchase->purchase_type === 'live_duck' ? 'vịt lông' : 'vịt thịt') . ' - ' . $source . ' - ' . $purchase->code, 'unit' => 'kg', 'quantity' => (float) $purchase->total_weight, 'unit_price' => (float) $purchase->unit_price, 'line_total' => (float) $purchase->subtotal],
        ];
        if ((float) $purchase->broker_fee > 0) $items[] = ['stt' => count($items) + 1, 'content' => 'Phí cò giới thiệu', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => (float) $purchase->broker_fee, 'line_total' => (float) $purchase->broker_fee];
        if ((float) $purchase->processing_fee > 0) $items[] = ['stt' => count($items) + 1, 'content' => 'Chi phí xử lý sơ chế', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => (float) $purchase->processing_fee, 'line_total' => (float) $purchase->processing_fee];
        if ((float) $purchase->procurement_fee > 0) $items[] = ['stt' => count($items) + 1, 'content' => 'Chi phí thu mua chuyến', 'unit' => 'chuyến', 'quantity' => 1, 'unit_price' => (float) $purchase->procurement_fee, 'line_total' => (float) $purchase->procurement_fee];
        if ((float) $purchase->transportation_fee > 0) $items[] = ['stt' => count($items) + 1, 'content' => 'Chi phí vận chuyển', 'unit' => 'chuyến', 'quantity' => 1, 'unit_price' => (float) $purchase->transportation_fee, 'line_total' => (float) $purchase->transportation_fee];
        if ((float) $purchase->other_fee > 0) $items[] = ['stt' => count($items) + 1, 'content' => 'Chi phí khác', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => (float) $purchase->other_fee, 'line_total' => (float) $purchase->other_fee];
        $requestAmount = (float) $purchase->remaining_amount;
        if ((float) $purchase->paid_amount > 0) {
            $items = [['stt' => 1, 'content' => 'Số tiền còn phải thanh toán - ' . $source . ' - ' . $purchase->code, 'unit' => 'lần', 'quantity' => 1, 'unit_price' => $requestAmount, 'line_total' => $requestAmount]];
        }
        $transaction = Transaction::create(['amount' => $requestAmount, 'type' => 'extra_expense', 'transaction_category_id' => $category->id, 'note' => 'Yêu cầu thanh toán lần thu mua ' . $purchase->code, 'status' => Transaction::STATUS_PENDING_APPROVAL, 'submitted_by' => auth()->id(), 'request_source' => 'procurement', 'request_department' => 'Thu mua', 'request_form_type' => Transaction::REQUEST_FORM_PAYMENT, 'request_title' => 'Thanh toán thu mua ' . $purchase->code, 'request_items' => $items, 'request_subtotal' => $requestAmount, 'request_vat' => 0, 'request_total' => $requestAmount]);
        $purchase->update(['payment_transaction_id' => $transaction->id]);
        app(ApprovalService::class)->initTransactionApproval($transaction);
        return back()->with('success', 'Đã gửi phiếu yêu cầu thanh toán #' . $transaction->id . '.');
    }

    public function farms(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page', 20), [10, 20, 50, 100], true)
            ? (int) $request->input('per_page', 20)
            : 20;
        $query = DuckFarm::query()->with(['reviews.user']);

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));
            $query->where(function ($farmQuery) use ($keyword): void {
                $farmQuery->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('address', 'like', '%' . $keyword . '%')
                    ->orWhere('duck_breed', 'like', '%' . $keyword . '%');
            });
        }
        if (in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }
        if (in_array($request->input('business_type'), ['individual', 'household', 'company', 'cooperative'], true)) {
            $query->where('business_type', $request->input('business_type'));
        }
        if ($request->filled('duck_breed')) {
            $query->where('duck_breed', $request->input('duck_breed'));
        }

        return view('procurement.farms', [
            'farms' => $query->orderBy('name')->paginate($perPage)->withQueryString(),
            'totalFarms' => DuckFarm::count(),
            'duckBreeds' => DuckFarm::query()->whereNotNull('duck_breed')->where('duck_breed', '!=', '')->distinct()->orderBy('duck_breed')->pluck('duck_breed'),
            'perPage' => $perPage,
        ]);
    }

    public function farmDebts(Request $request)
    {
        $query = ProcurementPurchase::query()
            ->with(['farm', 'paymentRequest'])
            ->whereNotNull('duck_farm_id')
            ->where('remaining_amount', '>', 0);

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));
            $query->whereHas('farm', fn ($farmQuery) => $farmQuery
                ->where('name', 'like', '%' . $keyword . '%')
                ->orWhere('phone', 'like', '%' . $keyword . '%'));
        }

        match ($request->input('due_status')) {
            'overdue' => $query->whereNotNull('payment_due_date')->whereDate('payment_due_date', '<', today()),
            'due_soon' => $query->whereBetween('payment_due_date', [today(), today()->addDays(7)]),
            'no_due' => $query->whereNull('payment_due_date'),
            default => null,
        };

        if ($request->filled('from_date')) {
            $query->whereDate('purchased_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('purchased_at', '<=', $request->input('to_date'));
        }

        $outstandingPurchases = $query->latest('purchased_at')->get();
        $farmDebts = $outstandingPurchases->groupBy('duck_farm_id')->map(function ($purchases) {
            $farm = $purchases->first()->farm;
            $overduePurchases = $purchases->filter(fn ($purchase) => $purchase->payment_due_date?->isBefore(today()) ?? false);

            return [
                'farm' => $farm,
                'purchases' => $purchases,
                'purchase_count' => $purchases->count(),
                'total_amount' => (float) $purchases->sum('total_amount'),
                'paid_amount' => (float) $purchases->sum('paid_amount'),
                'remaining_amount' => (float) $purchases->sum('remaining_amount'),
                'overdue_amount' => (float) $overduePurchases->sum('remaining_amount'),
                'nearest_due_date' => $purchases->whereNotNull('payment_due_date')->min('payment_due_date'),
                'last_purchase_at' => $purchases->max('purchased_at'),
            ];
        })->sortByDesc('remaining_amount')->values();

        return view('procurement.farm-debts', [
            'farmDebts' => $farmDebts,
            'totalDebt' => (float) $farmDebts->sum('remaining_amount'),
            'totalOverdue' => (float) $farmDebts->sum('overdue_amount'),
            'totalPaid' => (float) $farmDebts->sum('paid_amount'),
        ]);
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

    public function destroyFarm(DuckFarm $farm)
    {
        $name = $farm->name;
        $farm->delete();

        return back()->with('success', 'Đã xóa trang trại ' . $name . '.');
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
        $request->merge([
            'rates' => collect((array) $request->input('rates', []))->map(fn ($sizes) =>
                collect((array) $sizes)->map(fn ($value) => is_string($value)
                    ? str_replace([' ', ','], ['', '.'], trim($value))
                    : $value)->all()
            )->all(),
        ]);
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
