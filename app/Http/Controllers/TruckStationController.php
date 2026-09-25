<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\TruckStation;
use App\Models\Ward;
use Illuminate\Http\Request;

class TruckStationController extends Controller
{
    public function index(Request $request)
    {
        $query = TruckStation::query()->with(['province', 'ward']);

        $keyword = trim((string) $request->input('q', ''));
        if ($keyword !== '') {
            $query->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        $truckStations = $query->orderBy('name')->paginate(20)->appends($request->query());

        return view('truck_stations.index', compact('truckStations', 'keyword'));
    }

    public function create()
    {
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);

        return view('truck_stations.create', compact('provinces'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($data['ward_id']) && !empty($data['province_id'])) {
            $wardBelongsToProvince = Ward::query()
                ->whereKey($data['ward_id'])
                ->where('province_id', $data['province_id'])
                ->exists();

            if (!$wardBelongsToProvince) {
                return back()->withErrors([
                    'ward_id' => 'Phường/Xã không thuộc Tỉnh/Thành đã chọn.',
                ])->withInput();
            }
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        TruckStation::create($data);

        return redirect()->route('truck-stations.index')->with('success', 'Đã tạo nhà xe mới.');
    }

    public function edit(TruckStation $truckStation)
    {
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);

        return view('truck_stations.edit', compact('truckStation', 'provinces'));
    }

    public function update(Request $request, TruckStation $truckStation)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'ward_id' => ['nullable', 'exists:wards,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($data['ward_id']) && !empty($data['province_id'])) {
            $wardBelongsToProvince = Ward::query()
                ->whereKey($data['ward_id'])
                ->where('province_id', $data['province_id'])
                ->exists();

            if (!$wardBelongsToProvince) {
                return back()->withErrors([
                    'ward_id' => 'Phường/Xã không thuộc Tỉnh/Thành đã chọn.',
                ])->withInput();
            }
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $truckStation->update($data);

        return redirect()->route('truck-stations.index')->with('success', 'Đã cập nhật nhà xe.');
    }

    public function destroy(TruckStation $truckStation)
    {
        $truckStation->delete();

        return redirect()->route('truck-stations.index')->with('success', 'Đã xóa nhà xe.');
    }
}
