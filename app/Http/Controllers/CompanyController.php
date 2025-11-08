<?php
namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CompanyImportWithErrorReport;
use App\Exports\CompanyExport;

class CompanyController extends Controller
{
    use AuthorizesRequests;

    public function export()
    {
        return Excel::download(new CompanyExport, 'companies.xlsx');
    }

    public function importForm(Request $request)
    {
        $import_failures = session('import_errors', []);
        $success = session('import_success', null);
        return view('companies.import', compact('import_failures', 'success'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        $import = new CompanyImportWithErrorReport();
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
                return redirect()->route('companies.import.form')->with(['import_failures' => $errors]);
            }
            return redirect()->route('companies.import.form')->with(['import_success' => 'Import công ty thành công!']);
        } catch (\Exception $e) {
            return redirect()->route('companies.import.form')->with(['import_errors' => [['row' => '-', 'attribute' => '-', 'errors' => [$e->getMessage()], 'values' => []]]]);
        }
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Company::class);
        $query = Company::with(['assignedTo']);

        // Lọc theo user (chỉ admin)
        if (Gate::allows('filter_company_by_user') && $request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Tìm theo tên / phone / email
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mst', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $companies = $query->orderBy('name')
               ->paginate($perPage)
               ->appends($request->query()); // giữ query string khi phân trang

        $users = null;
        if (Gate::allows('filter_company_by_user')) {
            $users = User::orderBy('name')->get(['id', 'name']);
        }

        return view('companies.index', compact('companies', 'users'));
    }

    public function create()
    {
        $this->authorize('create', Company::class);
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Company::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30|unique:companies,phone',
            'email' => 'nullable|email|unique:companies,email',
            'address' => 'nullable|string|max:1000',
            'mst' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
        ]);
        $company = Company::create($data);

        return redirect()->route('companies.index')->with('success', 'Thêm công ty thành công.');
    }

    public function edit(Company $company)
    {
        $this->authorize('update', $company);
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $this->authorize('update', $company);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30|unique:companies,phone,' . $company->id,
            'email' => 'nullable|email|unique:companies,email,' . $company->id,
            'address' => 'nullable|string|max:1000',
            'mst' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
        ]);
        $company->update($data);

        return redirect()->route('companies.index')->with('success', 'Cập nhật công ty thành công.');
    }

    public function destroy(Company $company)
    {
        $this->authorize('delete', $company);
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Xóa công ty thành công.');
    }
}