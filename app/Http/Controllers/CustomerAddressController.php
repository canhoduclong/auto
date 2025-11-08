<?php

namespace App\Http\Controllers;
//use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;

class CustomerAddressController extends Controller
{
    // Danh sách địa chỉ của khách hàng
    //use AuthorizesRequests;
    public function index($customerId)
    {
        $customer = Customer::with('addresses')->findOrFail($customerId);
        return view('customers.addresses.index', compact('customer'));
    }

    public function list(Request $request)
    {
        // Số item/trang (cho phép truyền ?perPage=25)
        $perPage = (int) $request->input('perPage', 15);
        $perPage = $perPage > 0 && $perPage <= 200 ? $perPage : 15;

        $query = CustomerAddress::with('customer');

        // Filter theo city
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        // Filter theo customer name (exact/like)
        if ($request->filled('customer_name')) {
            $name = $request->customer_name;
            $query->whereHas('customer', function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        // Filter theo customer phone
        if ($request->filled('customer_phone')) {
            $phone = $request->customer_phone;
            $query->whereHas('customer', function ($q) use ($phone) {
                $q->where('phone', 'like', "%{$phone}%");
            });
        }

        // Search chung (tìm trong nhiều trường address + customer)
        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(function ($q) use ($s) {
                // tìm trong các cột địa chỉ
                $q->where('project_name', 'like', "%{$s}%")
                  ->orWhere('zone', 'like', "%{$s}%")
                  ->orWhere('block', 'like', "%{$s}%")
                  ->orWhere('unit_number', 'like', "%{$s}%")
                  ->orWhere('street', 'like', "%{$s}%")
                  ->orWhere('ward', 'like', "%{$s}%")
                  ->orWhere('district', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%")
                  // hoặc trong thông tin customer
                  ->orWhereHas('customer', fn($qc) => $qc->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
            });
        }

        // Order & paginate (giữ query string khi chuyển trang)
        $addresses = $query->orderByDesc('id')->paginate($perPage)->appends($request->query());

        // Tạo danh sách distinct city dùng cho dropdown filter (nếu muốn)
        $cities = CustomerAddress::select('city')->distinct()->whereNotNull('city')->pluck('city');

        return view('customers.addresses.list', compact('addresses', 'cities'));
    }

    // Form thêm địa chỉ
    public function create($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        return view('customers.addresses.create', compact('customer'));
    }

    // Lưu địa chỉ mới
    public function store(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $data = $request->validate([
            'project_name' => 'nullable|string|max:255',
            'zone'         => 'nullable|string|max:100',
            'block'        => 'nullable|string|max:100',
            'floor'        => 'nullable|string|max:50',
            'unit_number'  => 'nullable|string|max:50',
            'house_number'  => 'nullable|string|max:50',
            'temporary_number'  => 'nullable|string|max:50',
            'note' => 'nullable|string|max:2000',
            'street'       => 'nullable|string|max:255',
            'ward'         => 'nullable|string|max:255',
            'district'     => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:255',
            'is_default'   => 'boolean',
        ]);

        if (!empty($data['is_default']) && $data['is_default']) {
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        }

        $customer->addresses()->create($data);

        return redirect()->route('customers.addresses.index', $customer->id)
            ->with('success', 'Địa chỉ đã được thêm thành công.');
    }

    // Form sửa địa chỉ
    public function edit($customerId, $addressId)
    {
        $customer = Customer::findOrFail($customerId);
        $address = CustomerAddress::where('customer_id', $customerId)->findOrFail($addressId);

        return view('customers.addresses.edit', compact('customer', 'address'));
    }

    // Cập nhật địa chỉ
    public function update(Request $request, $customerId, $addressId)
    {
        $customer = Customer::findOrFail($customerId);
        $address = CustomerAddress::where('customer_id', $customerId)->findOrFail($addressId);

        $data = $request->validate([
            'project_name' => 'nullable|string|max:255',
            'zone'         => 'nullable|string|max:100',
            'block'        => 'nullable|string|max:100',
            'floor'        => 'nullable|string|max:50',
            'unit_number'  => 'nullable|string|max:50',
            'house_number'  => 'nullable|string|max:50',
            'temporary_number'  => 'nullable|string|max:50',
            'note'          => 'nullable|string|max:2000',
            'street'       => 'nullable|string|max:255',
            'ward'         => 'nullable|string|max:255',
            'district'     => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:255',
            'is_default'   => 'boolean',
        ]);

        if (!empty($data['is_default']) && $data['is_default']) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        return redirect()->route('customers.addresses.index', $customer->id)
            ->with('success', 'Địa chỉ đã được cập nhật thành công.');
    }

    // Xóa địa chỉ
    public function destroy($customerId, $addressId)
    {
        $address = CustomerAddress::where('customer_id', $customerId)->findOrFail($addressId);
        $address->delete();

        return redirect()->route('customers.addresses.index', $customerId)
            ->with('success', 'Địa chỉ đã được xóa thành công.');
    }
}
