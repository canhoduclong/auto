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
        $data = $request->validate(['preview' => 'nullable|uuid', 'search' => 'nullable|string|max:150', 'status' => 'nullable|in:all,pending_link,link,create,update,unchanged,conflict,imported,skip,error', 'page' => 'nullable|integer|min:1']);
        $pendingOnly = ($data['status'] ?? null) === 'pending_link';
        if (empty($data['preview'])) {
            $latest = $service->latestPreview($request->user()) ?? $service->openSaved($request->user());
            if ($latest) {
                return redirect()->route('customers.sheet-import.index', array_replace($data, ['preview' => $latest]));
            }
        }
        $snapshot = ! empty($data['preview']) ? $service->snapshot($data['preview'], $request->user()) : null;
        if ($snapshot) {
            $service->rememberPreview($data['preview'], $request->user());
        }
        $rows = collect($snapshot ? $service->plan($snapshot) : []);
        $rows = $rows->map(fn ($row) => $row + ['can_update' => $row['target'] && Gate::forUser($request->user())->allows('update', $row['target'])]);
        $counts = $rows->countBy('status');
        $pendingCount = $rows->where('pending_link', true)->count();
        $canCreate = Gate::forUser($request->user())->allows('create', Customer::class);
        $readyCount = $rows->filter(fn ($row) => $row['status'] === 'link' || ($canCreate && $row['status'] === 'create') || ($row['status'] === 'update' && $row['can_update']))->count();
        $visible = $rows->filter(function ($row) use ($data) {
            $status = $data['status'] ?? 'all';
            $matchesStatus = match ($status) {
                'all' => true,
                'pending_link' => $row['pending_link'],
                'error' => isset($row['result']) && ! ($row['result']['ok'] ?? true),
                default => $row['status'] === $status,
            };
            if (!$matchesStatus) {
                return false;
            }
            $search = Customer::normalizeName($data['search'] ?? '');

            return $search === '' || str_contains(Customer::normalizeName($row['name'].' '.$row['code'].' '.($row['target']?->name ?? '')), $search);
        });
        $page = (int) ($data['page'] ?? 1);
        $rows = new \Illuminate\Pagination\LengthAwarePaginator($visible->forPage($page, 40)->values(), $visible->count(), 40, $page, ['path' => $request->url(), 'query' => $request->except('page')]);

        return view('customers.google-sheet-import', ['snapshot' => $snapshot, 'rows' => $rows, 'counts' => $counts, 'total' => $counts->sum(), 'sales' => $service->sales(), 'canCreate' => Gate::forUser($request->user())->allows('create', Customer::class), 'connectionEmail' => $sheets->connectionEmail(), 'readyCount' => $readyCount, 'pendingCount' => $pendingCount, 'pendingOnly' => $pendingOnly, 'importHistory' => $service->importHistory(), 'syncMode' => ($snapshot['mode'] ?? 'import') === 'sync', 'canReassign' => $request->user()->isAdmin() || $request->user()->hasPermission('customers.assign-sale')]);
    }

    public function load(Request $request, GoogleSheetCustomerImportService $service)
    {
        $this->authorizeImport($request);
        $data = $request->validate(['mode' => 'nullable|in:import,sync', 'status' => 'nullable|in:pending_link', 'sync_fields' => 'nullable|required_if:mode,sync|array|min:1', 'sync_fields.*' => [Rule::in(array_keys(GoogleSheetCustomerImportService::SYNC_FIELDS))]]);
        $fields = $data['sync_fields'] ?? ['name', 'phone', 'address', 'delivery_time'];
        abort_if(in_array('sale_id', $fields, true) && ! $request->user()->isAdmin() && ! $request->user()->hasPermission('customers.assign-sale'), 403);
        try {
            $token = $service->load($request->user(), $data['mode'] ?? 'import', $fields);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Không tải được khách hàng từ Google Sheet gốc. Kiểm tra quyền Người xem của tài khoản kết nối và tab nhatkybanhang.');
        }

        return redirect()->route('customers.sheet-import.index', ['preview' => $token, 'status' => $data['status'] ?? null]);
    }

    public function customers(Request $request)
    {
        $this->authorizeImport($request);
        $data = $request->validate(['search' => 'nullable|string|max:150', 'page' => 'nullable|integer|min:1']);
        $search = trim($data['search'] ?? '');
        $customers = Customer::query()->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('customer_code', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')))->orderBy('name')->paginate(20, ['id', 'name', 'customer_code', 'phone', 'address', 'delivery_time', 'assigned_to', 'current_owner_sale_id', 'customer_status', 'assigned_at']);
        $service = app(GoogleSheetCustomerImportService::class);
        $customers->getCollection()->each(fn ($customer) => $customer->setAttribute('revision', $service->customerRevision($customer)));

        return response()->json(['customers' => $customers->items(), 'page' => $customers->currentPage(), 'last_page' => $customers->lastPage(), 'total' => $customers->total()])->header('Cache-Control', 'no-store');
    }

    public function decide(Request $request, GoogleSheetCustomerImportService $service)
    {
        $this->authorizeImport($request);
        $data = $request->validate([
            'preview' => 'required|uuid', 'source_key' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'action' => 'required|in:auto,link,create,update,skip',
            'sync_fields' => 'nullable|required_if:action,update|array|min:1',
            'sync_fields.*' => [Rule::in(array_keys(GoogleSheetCustomerImportService::SYNC_FIELDS))],
            'customer_revision' => 'nullable|required_if:action,update|string|size:64',
            'customer_id' => ['nullable', 'required_if:action,link,update', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'name' => 'nullable|required_if:action,create|string|max:255', 'customer_code' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30', 'address' => 'nullable|string|max:255', 'delivery_time' => 'nullable|date_format:H:i',
            'sale_id' => ['nullable', 'integer', Rule::in($service->sales()->pluck('id')->all())],
        ]);
        abort_if($data['action'] === 'create' && ! Gate::forUser($request->user())->allows('create', Customer::class), 403);
        foreach (['phone', 'address', 'delivery_time'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = trim($data[$field] ?? '');
            }
        }
        $service->decide($data['preview'], $request->user(), $data);

        return response()->json(['message' => 'Đã lưu lựa chọn.']);
    }

    public function commit(Request $request, GoogleSheetCustomerImportService $service)
    {
        $this->authorizeImport($request);
        $data = $request->validate(['preview' => 'required|uuid', 'source_key' => 'nullable|string|size:64|regex:/^[a-f0-9]{64}$/', 'page' => 'nullable|integer|min:1', 'search' => 'nullable|string|max:150', 'status' => 'nullable|in:all,pending_link,link,create,update,unchanged,conflict,imported,skip,error']);
        $canCreate = Gate::forUser($request->user())->allows('create', Customer::class);
        $counts = $service->commit($data['preview'], $request->user(), $canCreate, 25, $data['source_key'] ?? null);
        $rows = collect($service->plan($service->snapshot($data['preview'], $request->user())));
        $remaining = $rows->filter(fn ($row) => $row['status'] === 'link' || ($canCreate && $row['status'] === 'create') || ($row['status'] === 'update' && Gate::forUser($request->user())->allows('update', $row['target'])))->count();
        $created = $rows->filter(fn ($row) => ($row['result']['ok'] ?? false) && $row['result']['action'] === 'create')->count();
        $linked = $rows->filter(fn ($row) => ($row['result']['ok'] ?? false) && $row['result']['action'] === 'link')->count();
        $updated = $rows->filter(fn ($row) => ($row['result']['ok'] ?? false) && $row['result']['action'] === 'update')->count();
        $message = "Bản import này đã tạo {$created} khách mới, ghép {$linked} khách hiện có, cập nhật {$updated} khách. Còn {$remaining} khách sẵn sàng chưa import.";
        if ($counts['errors'] > 0) {
            $message .= ' Có dòng lỗi, vui lòng kiểm tra danh sách.';
        }
        if (! empty($data['source_key'])) {
            $result = $service->snapshot($data['preview'], $request->user())['results'][$data['source_key']] ?? null;
            $message = ! empty($result['ok']) ? 'Đã áp dụng đồng bộ cho khách được chọn.' : ($result['message'] ?? 'Khách này chưa sẵn sàng để áp dụng.');
        }
        $request->session()->flash($counts['errors'] > 0 ? 'error' : 'success', $message);
        $url = route('customers.sheet-import.index', array_intersect_key($data, array_flip(['preview', 'page', 'search', 'status'])));
        if ($request->expectsJson()) {
            return response()->json(['counts' => $counts, 'remaining' => $remaining, 'url' => $url]);
        }

        return redirect()->to($url);
    }
}
