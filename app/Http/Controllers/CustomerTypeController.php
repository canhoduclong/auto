<?php

namespace App\Http\Controllers;
//use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\CustomerType;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;

class CustomerTypeController extends Controller
{
    //use AuthorizesRequests;
    // Danh sách loại khách hàng
    public function index()
    {
        //$this->authorize('viewAny', CustomerType::class);

        $types = CustomerType::orderBy('priority_level', 'desc')
            ->orderBy('name')
            ->paginate(15);

        return view('customers.types.index', compact('types'));
    }

    // Form thêm mới
    public function create()
    {
        return view('customers.types.create');
    }

    // Lưu loại khách hàng mới
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255|unique:customer_types,name',
            'description'     => 'nullable|string',
            'min_orders'      => 'nullable|integer|min:0',
            'min_total_spent' => 'nullable|numeric|min:0',
            'valid_days'      => 'nullable|integer|min:0',
            'discount_rate'   => 'nullable|numeric|min:0|max:100',
            'free_shipping'   => 'boolean',
            'priority_level'  => 'nullable|integer|min:0',
        ]);

        CustomerType::create($data);

        return redirect()->route('customertypes.index')
            ->with('success', 'Thêm loại khách hàng thành công!');
    }

    // Form chỉnh sửa
    public function edit(CustomerType $customerType)
    {
        return view('customers.types.edit', compact('customerType'));
    }

    // Cập nhật loại khách hàng
    public function update(Request $request, CustomerType $customerType)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255|unique:customer_types,name,' . $customerType->id,
            'description'     => 'nullable|string',
            'min_orders'      => 'nullable|integer|min:0',
            'min_total_spent' => 'nullable|numeric|min:0',
            'valid_days'      => 'nullable|integer|min:0',
            'discount_rate'   => 'nullable|numeric|min:0|max:100',
            'free_shipping'   => 'boolean',
            'priority_level'  => 'nullable|integer|min:0',
        ]);

        $customerType->update($data);

        return redirect()->route('customertypes.index')
            ->with('success', 'Cập nhật loại khách hàng thành công!');
    }

    // Xóa loại khách hàng
    public function destroy(CustomerType $customerType)
    {
        $customerType->delete();

        return redirect()->route('customertypes.index')
            ->with('success', 'Xóa loại khách hàng thành công!');
    }
}
