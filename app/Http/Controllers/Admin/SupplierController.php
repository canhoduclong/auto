<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,warehouse']);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');

        $suppliers = Supplier::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"))
            ->when($status === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers', 'search', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:200',
            'phone'     => 'nullable|string|max:30',
            'address'   => 'nullable|string|max:500',
            'notes'     => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Supplier::create($validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Đã thêm nhà cung cấp "' . $validated['name'] . '".');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:200',
            'phone'     => 'nullable|string|max:30',
            'address'   => 'nullable|string|max:500',
            'notes'     => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $supplier->update($validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Đã cập nhật nhà cung cấp "' . $supplier->name . '".');
    }

    public function destroy(Supplier $supplier)
    {
        $hasDocuments = $supplier->inventoryDocuments()->exists();
        if ($hasDocuments) {
            return back()->with('error', 'Không thể xóa nhà cung cấp này vì đã có phiếu nhập kho liên kết.');
        }

        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Đã xóa nhà cung cấp "' . $name . '".');
    }
}
