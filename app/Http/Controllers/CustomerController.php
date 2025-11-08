<?php
namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CustomerImport;
use App\Exports\CustomerExport;


class CustomerController extends Controller
{
    // Export excel
    public function export()
    {
        return Excel::download(new CustomerExport, 'customers.xlsx');
    }

    // Hiển thị form import và kết quả
    public function importForm(Request $request)
    {
        $import_failures = session('import_errors', []);
        $success = session('import_success', null);
        return view('customers.import', compact('import_failures', 'success'));
    }

    // Xử lý import excel
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        $import = new \App\Imports\CustomerImportWithErrorReport();
        try {
            Excel::import($import, $request->file('file'));
            $failures = $import->failures();
            if (count($failures) > 0) {
                $errors = [];
                foreach ($failures as $failure) {
                    $row = $failure->row();
                    $attr = $failure->attribute();
                    $errs = $failure->errors();
                    $values = $failure->values();
                    $errors[] = [
                        'row' => $row,
                        'attribute' => $attr,
                        'errors' => $errs,
                        'values' => $values,
                    ];
                }
                return redirect()->route('customers.import.form')->with(['import_failures' => $errors]);
            }
            return redirect()->route('customers.import.form')->with(['import_success' => 'Import khách hàng thành công!']);
        } catch (\Exception $e) {
            return redirect()->route('customers.import.form')->with(['import_errors' => [['row' => '-', 'attribute' => '-', 'errors' => [$e->getMessage()], 'values' => []]]]);
        }
    }
    // List + filter + search + paginate
    use AuthorizesRequests;
    public function index(Request $request)
    { 
        $this->authorize('viewAny', Customer::class);
        $query = Customer::with(['type', 'addresses', 'assignedTo']);

        // Lọc theo loại
        if ($request->filled('type_id')) {
            $query->where('customer_type_id', $request->type_id);
        }

        // Lọc theo user (chỉ admin)
        if (Gate::allows('filter_customer_by_user') && $request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Tìm theo tên / phone / email
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

    $perPage = $request->input('per_page', 15);
    $customers = $query->orderBy('name')
               ->paginate($perPage)
               ->appends($request->query()); // giữ query string khi phân trang

        // Dùng để hiển thị dropdown lọc
        $types = CustomerType::orderByDesc('priority_level')
                             ->orderBy('name')
                             ->get(['id', 'name']);

        $users = null;
        if (Gate::allows('filter_customer_by_user')) {
            $users = User::orderBy('name')->get(['id', 'name']);
        }

        return view('customers.index', compact('customers', 'types', 'users'));
    }

    // Form create
    public function create()
    {
        $types = CustomerType::orderBy('name')->get(['id', 'name']);
        return view('customers.create', compact('types'));
    }

    // Store
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30|unique:customers,phone',
            'email' => 'nullable|email|unique:customers,email',
            'website' => 'nullable|url|max:255',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'note' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:1000',
            'delivery_time' => 'nullable|string|max:255',
            'foam_box_required' => 'nullable|boolean',
            'foam_box_price' => 'nullable|integer',
            'use_truck_station' => 'nullable|boolean',
            'truck_station_address' => 'nullable|string|max:255',
            'truck_receive_time' => 'nullable|string|max:255',
            'truck_return_time' => 'nullable|string|max:255',
            'truck_return_address' => 'nullable|string|max:255',
            'truck_invoice_image' => 'nullable|string|max:255',
            'truck_delivery_image' => 'nullable|string|max:255',
            'truck_station_phone' => 'nullable|string|max:30',
            'truck_fee' => 'nullable|integer',
        ]);
        $customer = Customer::create($data);

        if ($request->filled('address')) {
            $customer->addresses()->create([
                'note' => $request->address,
                'is_default' => 1,
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'Thêm khách hàng thành công.');
    }

    // Form edit
    public function edit(Customer $customer)
    {
        $types = CustomerType::orderBy('name')->get(['id', 'name']);
        return view('customers.edit', compact('customer', 'types'));
    }

    // Update
    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'website' => 'nullable|url|max:255',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'note' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:1000',
            'delivery_time' => 'nullable|string|max:255',
            'foam_box_required' => 'nullable|boolean',
            'foam_box_price' => 'nullable|integer',
            'use_truck_station' => 'nullable|boolean',
            'truck_station_address' => 'nullable|string|max:255',
            'truck_receive_time' => 'nullable|string|max:255',
            'truck_return_time' => 'nullable|string|max:255',
            'truck_return_address' => 'nullable|string|max:255',
            'truck_invoice_image' => 'nullable|string|max:255',
            'truck_delivery_image' => 'nullable|string|max:255',
            'truck_station_phone' => 'nullable|string|max:30',
            'truck_fee' => 'nullable|integer',
        ]);
        $customer->update($data);

        if ($request->filled('address')) {
            $customer->addresses()->updateOrCreate(
                ['is_default' => 1],
                ['note' => $request->address]
            );
        }

        return redirect()->route('customers.index')->with('success', 'Cập nhật khách hàng thành công.');
    }

    // Delete
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Xóa khách hàng thành công.');
    }

    // Bulk Delete
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
        ]);

        $ids = explode(',', $request->input('ids'));

        Customer::whereIn('id', $ids)->delete();

        return redirect()->route('customers.index')->with('success', 'Đã xóa các khách hàng đã chọn.');
    }
}
