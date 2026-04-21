<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\TruckBrand;
use App\Models\TruckStation;
use App\Models\Ward;
use Illuminate\Http\Request;

class TruckStationAdminController extends Controller
{
    public function index(Request $request)
    {
        $q         = $request->input('q');
        $brandId   = $request->input('brand_id');
        $provinceId= $request->input('province_id');
        $status    = $request->input('status');

        $stations = TruckStation::query()
            ->with(['brand', 'province', 'ward'])
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            }))
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->when($provinceId, fn ($query) => $query->where('province_id', $provinceId))
            ->when($status !== null && $status !== '', fn ($query) => $query->where('is_active', $status))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $brands    = TruckBrand::orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name']);

        return view('admin.truck-stations.index', compact('stations', 'brands', 'provinces', 'q', 'brandId', 'provinceId', 'status'));
    }

    public function create()
    {
        $brands    = TruckBrand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name']);

        return view('admin.truck-stations.create', compact('brands', 'provinces'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'brand_id'           => 'nullable|exists:truck_brands,id',
            'province_id'        => 'nullable|exists:provinces,id',
            'ward_id'            => 'nullable|exists:wards,id',
            'address'            => 'nullable|string|max:500',
            'phone'              => 'nullable|string|max:30',
            'parking_fee'        => 'nullable|numeric|min:0',
            'branch_info'        => 'nullable|string|max:500',
            'has_home_delivery'  => 'nullable|boolean',
            'home_delivery_fee'  => 'nullable|numeric|min:0',
            'note'               => 'nullable|string',
            'is_active'          => 'nullable|boolean',
        ]);

        if ($data['ward_id'] ?? null) {
            $ward = Ward::where('id', $data['ward_id'])
                ->where('province_id', $data['province_id'] ?? null)
                ->exists();
            if (!$ward) {
                return back()->withErrors(['ward_id' => 'Phường/xã không thuộc tỉnh/thành đã chọn.'])->withInput();
            }
        }

        $data['is_active']         = $request->boolean('is_active', true);
        $data['has_home_delivery'] = $request->boolean('has_home_delivery', false);
        $data['home_delivery_fee'] = $data['home_delivery_fee'] ?? 0;
        $data['created_by']        = auth()->id();

        TruckStation::create($data);

        return redirect()->route('admin.truck-stations.index')
            ->with('success', 'Đã thêm trạm xe: ' . $data['name']);
    }

    public function edit(TruckStation $truckStation)
    {
        $brands    = TruckBrand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name']);
        $wards     = $truckStation->province_id
            ? Ward::where('province_id', $truckStation->province_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.truck-stations.edit', compact('truckStation', 'brands', 'provinces', 'wards'));
    }

    public function update(Request $request, TruckStation $truckStation)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'brand_id'           => 'nullable|exists:truck_brands,id',
            'province_id'        => 'nullable|exists:provinces,id',
            'ward_id'            => 'nullable|exists:wards,id',
            'address'            => 'nullable|string|max:500',
            'phone'              => 'nullable|string|max:30',
            'parking_fee'        => 'nullable|numeric|min:0',
            'branch_info'        => 'nullable|string|max:500',
            'has_home_delivery'  => 'nullable|boolean',
            'home_delivery_fee'  => 'nullable|numeric|min:0',
            'note'               => 'nullable|string',
            'is_active'          => 'nullable|boolean',
        ]);

        if ($data['ward_id'] ?? null) {
            $ward = Ward::where('id', $data['ward_id'])
                ->where('province_id', $data['province_id'] ?? null)
                ->exists();
            if (!$ward) {
                return back()->withErrors(['ward_id' => 'Phường/xã không thuộc tỉnh/thành đã chọn.'])->withInput();
            }
        }

        $data['is_active']         = $request->boolean('is_active', true);
        $data['has_home_delivery'] = $request->boolean('has_home_delivery', false);
        $data['home_delivery_fee'] = $data['home_delivery_fee'] ?? 0;

        $truckStation->update($data);

        return redirect()->route('admin.truck-stations.index')
            ->with('success', 'Đã cập nhật: ' . $truckStation->name);
    }

    public function destroy(TruckStation $truckStation)
    {
        $truckStation->delete();

        return redirect()->route('admin.truck-stations.index')
            ->with('success', 'Đã xóa trạm xe.');
    }
}
