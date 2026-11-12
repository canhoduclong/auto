<?php

namespace App\Http\Controllers;

use App\Models\{Supplier, Product, ProcurementPurchase, SupplierDebtPayment, SupplierProduct};
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProcurementSupplierController extends Controller
{
    private function period(Request $request): array
    {
        $data = $request->validate([
            'period' => 'nullable|in:today,latest,week,custom,all',
            'from_date' => 'nullable|required_if:period,custom|date',
            'to_date' => 'nullable|required_if:period,custom|date|after_or_equal:from_date',
            'search' => 'nullable|string|max:200',
            'status' => 'nullable|in:active,inactive',
        ]);
        $period = $data['period'] ?? 'latest';
        [$from, $to] = match ($period) {
            'today' => [today(), today()],
            'week' => [today()->startOfWeek(), today()],
            'custom' => [Carbon::parse($data['from_date']), Carbon::parse($data['to_date'])],
            'all' => [null, null],
            default => [today()->subDays(29), today()],
        };
        return [$period, $from?->toDateString(), $to?->toDateString()];
    }

    private function purchases(?string $from, ?string $to)
    {
        return ProcurementPurchase::query()->whereNotNull('supplier_id')->where('purchase_type', '!=', 'live_duck')
            ->when($from, fn ($q) => $q->where('purchased_at', '>=', $from.' 00:00:00'))
            ->when($to, fn ($q) => $q->where('purchased_at', '<', Carbon::parse($to)->addDay()->toDateString()));
    }

    public function index(Request $request)
    {
        [$period, $from, $to] = $this->period($request);
        $search = trim((string) $request->input('search'));
        $suppliers = Supplier::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->input('status') === 'active'))
            ->withCount(['procurementPurchases as period_count' => fn ($q) => $q->whereIn('id', $this->purchases($from, $to)->select('id'))])
            ->withSum(['procurementPurchases as period_total' => fn ($q) => $q->whereIn('id', $this->purchases($from, $to)->select('id'))], 'total_amount')
            ->withSum(['procurementPurchases as current_debt' => fn ($q) => $q->where('purchase_type', '!=', 'live_duck')], 'remaining_amount')
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('procurement.suppliers.index', compact('suppliers', 'period', 'from', 'to'));
    }

    public function show(Request $request, Supplier $supplier)
    {
        [$period, $from, $to] = $this->period($request);
        $query = $this->purchases($from, $to)->where('supplier_id', $supplier->id);
        $summary = (clone $query)->selectRaw('COUNT(*) as count, COALESCE(SUM(subtotal),0) as goods, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(paid_amount),0) as paid, COALESCE(SUM(remaining_amount),0) as debt')->first();
        $currentDebt = $this->purchases(null, null)->where('supplier_id', $supplier->id)->sum('remaining_amount');
        $overdueDebt = $this->purchases(null, null)->where('supplier_id', $supplier->id)->whereDate('payment_due_date', '<', today())->sum('remaining_amount');
        $purchases = $query->with(['productItems.productVariant.product', 'warehouse'])->latest('purchased_at')->orderByDesc('id')->paginate(20, ['*'], 'purchases_page')->withQueryString();
        $paymentsQuery = SupplierDebtPayment::query()->whereHas('purchase', fn ($q) => $q->where('supplier_id', $supplier->id)->where('purchase_type', '!=', 'live_duck'))
            ->when($from, fn ($q) => $q->where('paid_at', '>=', $from.' 00:00:00'))
            ->when($to, fn ($q) => $q->where('paid_at', '<', Carbon::parse($to)->addDay()->toDateString()));
        $periodPayments = (clone $paymentsQuery)->sum('amount');
        $payments = $paymentsQuery->with(['purchase', 'recorder'])->latest('paid_at')->paginate(20, ['*'], 'payments_page')->withQueryString();
        $supplier->load('supplierProducts.product');
        $products = Product::where('status', true)->orderBy('name')->get();
        return view('procurement.suppliers.show', compact('supplier', 'period', 'from', 'to', 'summary', 'currentDebt', 'overdueDebt', 'purchases', 'payments', 'periodPayments', 'products'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(['name' => 'required|string|max:200', 'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:500', 'notes' => 'nullable|string|max:2000', 'is_active' => 'required|boolean']);
        return $data;
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create($this->validated($request));
        return redirect()->route('procurement.suppliers.show', $supplier)->with('success', 'Đã thêm nhà cung cấp.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));
        return back()->with('success', 'Đã cập nhật nhà cung cấp.');
    }

    public function attachProduct(Request $request, Supplier $supplier)
    {
        $data = $request->validate(['product_id' => 'required|exists:products,id', 'active' => 'required|boolean']);
        SupplierProduct::updateOrCreate(['supplier_id' => $supplier->id, 'product_id' => $data['product_id']], ['active' => $data['active']]);
        return back()->with('success', 'Đã cập nhật sản phẩm cung cấp.');
    }
}
