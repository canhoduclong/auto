<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerDebtSheetService;
use App\Services\GoogleSheetCustomerImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class GoogleSheetCustomerImportController extends Controller
{
    private function authorizeImport(Request $request): void
    {
        abort_unless(Gate::forUser($request->user())->allows('import', Customer::class), 403);
    }

    public function index(Request $request, GoogleSheetCustomerImportService $service, CustomerDebtSheetService $sheets)
    {
        $this->authorizeImport($request);
        $data = $request->validate(['preview' => 'nullable|uuid', 'search' => 'nullable|string|max:150', 'status' => 'nullable|in:all,link,create,conflict,imported,skip,error', 'page' => 'nullable|integer|min:1']);
        $snapshot = ! empty($data['preview']) ? $service->snapshot($data['preview'], $request->user()) : null;
        $rows = collect($snapshot ? $service->plan($snapshot) : []);
        $counts = $rows->countBy('status');
        $visible = $rows->filter(function ($row) use ($data) {
            $status = $data['status'] ?? 'all';
            if ($status === 'error' ? empty($row['result']) || ($row['result']['ok'] ?? true) : ($status !== 'all' && $row['status'] !== $status)) {
                return false;
            }
            $search = Customer::normalizeName($data['search'] ?? '');

            return $search === '' || str_contains(Customer::normalizeName($row['name'].' '.$row['code'].' '.($row['target']?->name ?? '')), $search);
        });
        $page = (int) ($data['page'] ?? 1);
        $rows = new \Illuminate\Pagination\LengthAwarePaginator($visible->forPage($page, 40)->values(), $visible->count(), 40, $page, ['path' => $request->url(), 'query' => $request->except('page')]);

        return view('customers.google-sheet-import', ['snapshot' => $snapshot, 'rows' => $rows, 'counts' => $counts, 'total' => $counts->sum(), 'sales' => $service->sales(), 'canCreate' => Gate::forUser($request->user())->allows('create', Customer::class), 'connectionEmail' => $sheets->connectionEmail()]);
    }

    public function load(Request $request, GoogleSheetCustomerImportService $service)
    {
        $this->authorizeImport($request);
        try {
            $token = $service->load($request->user());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Không tải được khách hàng từ Google Sheet gốc. Kiểm tra quyền Người xem của tài khoản kết nối và tab nhatkybanhang.');
        }

        return redirect()->route('customers.sheet-import.index', ['preview' => $token]);
    }

    public function customers(Request $request)
    {
        $this->authorizeImport($request);
        $data = $request->validate(['search' => 'nullable|string|max:150', 'page' => 'nullable|integer|min:1']);
        $search = trim($data['search'] ?? '');
        $customers = Customer::query()->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('customer_code', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')))->orderBy('name')->paginate(20, ['id', 'name', 'customer_code', 'phone']);

        return response()->json(['customers' => $customers->items(), 'page' => $customers->currentPage(), 'last_page' => $customers->lastPage(), 'total' => $customers->total()])->header('Cache-Control', 'no-store');
    }

    public function decide(Request $request, GoogleSheetCustomerImportService $service)
    {
        $this->authorizeImport($request);
        $data = $request->validate([
            'preview' => 'required|uuid', 'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'action' => 'required|in:auto,link,create,skip',
            'customer_id' => ['nullable', 'required_if:action,link', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'name' => 'nullable|required_if:action,create|string|max:255', 'customer_code' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:255',
            'sale_id' => ['nullable', 'integer', Rule::in($service->sales()->pluck('id')->all())],
        ]);
        abort_if($data['action'] === 'create' && ! Gate::forUser($request->user())->allows('create', Customer::class), 403);
        foreach (['phone', 'address'] as $field) {
            $data[$field] = trim($data[$field] ?? '');
        }
        $service->decide($data['preview'], $request->user(), $data);

        return response()->json(['message' => 'Đã lưu lựa chọn.']);
    }

    public function commit(Request $request, GoogleSheetCustomerImportService $service)
    {
        $this->authorizeImport($request);
        $data = $request->validate(['preview' => 'required|uuid']);
        $canCreate = Gate::forUser($request->user())->allows('create', Customer::class);
        $counts = $service->commit($data['preview'], $request->user(), $canCreate);
        $rows = collect($service->plan($service->snapshot($data['preview'], $request->user())));
        $remaining = $rows->filter(fn ($row) => $row['status'] === 'link' || ($canCreate && $row['status'] === 'create'))->count();
        $created = $rows->filter(fn ($row) => ($row['result']['ok'] ?? false) && $row['result']['action'] === 'create')->count();
        $linked = $rows->filter(fn ($row) => ($row['result']['ok'] ?? false) && $row['result']['action'] === 'link')->count();
        $message = "Bản import này đã tạo {$created} khách mới, ghép {$linked} khách hiện có. Còn {$remaining} khách sẵn sàng chưa import.";
        if ($counts['errors'] > 0) {
            $message .= ' Có dòng lỗi, vui lòng kiểm tra danh sách.';
        }
        $request->session()->flash('success', $message);
        $url = route('customers.sheet-import.index', ['preview' => $data['preview']]);
        if ($request->expectsJson()) {
            return response()->json(['counts' => $counts, 'remaining' => $remaining, 'url' => $url]);
        }

        return redirect()->to($url);
    }
}
