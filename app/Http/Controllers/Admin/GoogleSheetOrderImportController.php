<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Customer, User, Warehouse};
use App\Services\GoogleSheetOrderImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB};
use Illuminate\Support\Str;
use Illuminate\Validation\{Rule, ValidationException};

class GoogleSheetOrderImportController extends Controller
{
    public function index(Request $request, GoogleSheetOrderImportService $service)
    {
        return $this->page($request, $service);
    }

    public function load(Request $request, GoogleSheetOrderImportService $service)
    {
        $data = $request->validate(['from_date' => 'required|date_format:Y-m-d', 'to_date' => 'required|date_format:Y-m-d|after_or_equal:from_date', 'source_text' => 'required|string|max:2000000', 'sale_id' => ['required', 'integer', Rule::in($this->sales()->pluck('id')->all())]]);
        try {
            $rows = $service->parseText($data['source_text'], $data['from_date'], $data['to_date']);
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', $e->getMessage());
        }
        $token = (string) Str::uuid();
        Cache::put('sheet-order-preview:'.$token, $data + ['rows' => $rows, 'user_id' => $request->user()->id], now()->addHour());
        return redirect()->route('admin.sheet-order-import.index', ['preview' => $token]);
    }

    private function snapshot(Request $request): ?array
    {
        $token = (string) $request->input('preview', '');
        if ($token === '') return null;
        $snapshot = Cache::get('sheet-order-preview:'.$token);
        abort_unless($snapshot && (int) $snapshot['user_id'] === (int) $request->user()->id, 419, 'Dữ liệu xem trước đã hết hạn. Vui lòng tải lại.');
        return $snapshot;
    }

    private function sales()
    {
        return User::whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name) IN (?,?,?,?,?,?)', ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale']))->orderBy('name')->get(['id', 'name']);
    }

    private function managedCustomers(int $saleId)
    {
        return Customer::query()->managedBySale($saleId);
    }

    public function customers(Request $request)
    {
        $data = $request->validate([
            'sale_id' => ['required', 'integer', Rule::in($this->sales()->pluck('id')->all())],
            'search' => 'nullable|string|max:150', 'page' => 'nullable|integer|min:1',
        ]);
        $search = trim($data['search'] ?? '');
        $customers = $this->managedCustomers((int) $data['sale_id'])
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')->orWhere('customer_code', 'like', '%'.$search.'%')->orWhere('old_name', 'like', '%'.$search.'%');
            }))
            ->orderBy('name')->orderBy('id')->paginate(20, ['id', 'name', 'customer_code', 'old_name'], 'page', (int) ($data['page'] ?? 1));
        return response()->json([
            'customers' => $customers->items(),
            'pagination' => ['current_page' => $customers->currentPage(), 'last_page' => $customers->lastPage(), 'total' => $customers->total(), 'per_page' => $customers->perPage()],
        ])->header('Cache-Control', 'no-store');
    }

    private function selection(Request $request, GoogleSheetOrderImportService $service): array
    {
        $data = $request->validate([
            'preview' => 'required|uuid', 'source_customer' => 'required|string|size:64',
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'sale_id' => ['required', 'integer', Rule::in($this->sales()->pluck('id')->all())],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('status', 1)],
            'stage' => 'required|in:created,delivered,completed',
            'shipper_id' => ['nullable', 'required_unless:stage,created', 'integer', Rule::exists('users', 'id')],
        ]);
        $customer = $this->managedCustomers((int) $data['sale_id'])->find($data['customer_id']);
        if (!$customer) throw ValidationException::withMessages(['customer_id' => 'Khách B không còn do sale đã chọn quản lý. Vui lòng chọn lại khách.']);
        if (!empty($data['shipper_id']) && !User::whereKey($data['shipper_id'])->whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name) IN (?,?)', ['shipper', 'manager_shipper']))->exists()) {
            throw ValidationException::withMessages(['shipper_id' => 'Vui lòng chọn tài khoản shipper.']);
        }
        $snapshot = $this->snapshot($request);
        $rows = array_values(array_filter($snapshot['rows'], fn ($r) => $r['customer_key'] === $data['source_customer']));
        if (!$rows) throw ValidationException::withMessages(['source_customer' => 'Không có dữ liệu cho khách đã chọn.']);
        $imported = DB::table('google_sheet_order_imports')->whereIn('source_key', array_map(fn ($r) => $service->sourceKey($r), $rows))->pluck('source_key')->all();
        $rows = array_values(array_filter($rows, fn ($r) => !in_array($service->sourceKey($r), $imported, true)));
        if (!$rows) throw ValidationException::withMessages(['source_customer' => 'Tất cả đơn của khách trong khoảng ngày đã import.']);
        return [$data, $rows, $customer, User::findOrFail($data['sale_id'])];
    }

    public function review(Request $request, GoogleSheetOrderImportService $service)
    {
        [$data, $rows, $customer, $sale] = $this->selection($request, $service);
        $prepared = $service->prepare($rows, $customer, $sale, $request->user());
        $confirmation = (string) Str::uuid();
        Cache::put('sheet-order-confirm:'.$confirmation, ['user_id' => $request->user()->id, 'data' => $data, 'rows' => $rows], now()->addMinutes(30));
        return $this->page($request, $service, ['review' => $prepared['preview'], 'selection' => $data, 'confirmation' => $confirmation]);
    }

    public function store(Request $request, GoogleSheetOrderImportService $service)
    {
        $request->validate(['confirmation' => 'required|uuid']);
        $key = 'sheet-order-confirm:'.$request->input('confirmation');
        $confirmed = Cache::get($key);
        abort_unless($confirmed && (int) $confirmed['user_id'] === (int) $request->user()->id, 419, 'Bản xem trước đã hết hạn. Vui lòng xem trước lại.');
        $request->merge($confirmed['data']);
        try {
            [$data, $rows, $customer, $sale] = $this->selection($request, $service);
        } catch (ValidationException $e) {
            return redirect()->route('admin.sheet-order-import.index', ['preview' => $confirmed['data']['preview']])->withErrors($e->errors())->withInput($confirmed['data']);
        }
        // An approval applies only to the exact snapshot reviewed by this administrator.
        if ($rows !== $confirmed['rows']) return redirect()->route('admin.sheet-order-import.index', ['preview' => $data['preview']])->with('error', 'Dữ liệu đã thay đổi sau khi xem trước. Vui lòng xem trước lại.');
        try {
            $orders = $service->import($rows, $customer, $sale, $request->user(), $data);
        } catch (ValidationException $e) {
            return redirect()->route('admin.sheet-order-import.index', ['preview' => $data['preview']])->withErrors($e->errors())->withInput($data);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.sheet-order-import.index', ['preview' => $data['preview']])->with('error', 'Không thể import. Dữ liệu của lần nhập này đã được hoàn tác. Vui lòng tải lại để kiểm tra trùng hoặc xem nhật ký lỗi.');
        }
        Cache::forget($key);
        return redirect()->route('admin.sheet-order-import.index', ['preview' => $data['preview']])->with('success', 'Đã import '.count($orders).' đơn: '.implode(', ', $orders).'. Đã lưu tên khách nguồn vào Tên cũ.');
    }

    private function page(Request $request, GoogleSheetOrderImportService $service, array $extra = [])
    {
        $snapshot = $this->snapshot($request);
        $rows = $snapshot['rows'] ?? [];
        $keys = array_unique(array_map(fn ($r) => $service->sourceKey($r), $rows));
        $imported = $keys ? DB::table('google_sheet_order_imports')->whereIn('source_key', $keys)->pluck('source_key')->all() : [];
        $groups = collect($rows)->groupBy('customer_key')->map(function ($rows, $key) use ($service, $imported) {
            $remaining = $rows->reject(fn ($r) => in_array($service->sourceKey($r), $imported, true));
            return ['key' => $key, 'name' => $rows->first()['customer_name'], 'rows' => $rows->count(), 'orders' => $rows->pluck('date')->unique()->count(), 'remaining' => $remaining->pluck('date')->unique()->count()];
        });
        $saleId = (int) $request->old('sale_id', $extra['selection']['sale_id'] ?? $snapshot['sale_id'] ?? 0);
        $customerId = (int) $request->old('customer_id', $extra['selection']['customer_id'] ?? 0);
        $selectedCustomer = $saleId && $customerId ? $this->managedCustomers($saleId)->find($customerId, ['id', 'name', 'customer_code', 'old_name']) : null;
        return view('admin.sheet-order-import.index', $extra + [
            'snapshot' => $snapshot, 'previewToken' => $request->input('preview'), 'groups' => $groups,
            'customers' => collect($selectedCustomer ? [$selectedCustomer] : []),
            'selectedCustomer' => $selectedCustomer,
            'sales' => $this->sales(), 'warehouses' => Warehouse::where('status', true)->orderBy('name')->get(['id', 'name']),
            'shippers' => User::whereHas('roles', fn ($q) => $q->whereRaw('LOWER(name) IN (?,?)', ['shipper', 'manager_shipper']))->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
