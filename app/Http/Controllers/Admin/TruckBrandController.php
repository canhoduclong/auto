<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TruckBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TruckBrandController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $brands = TruckBrand::query()
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->withCount('stations', 'routes')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.truck-brands.index', compact('brands', 'q'));
    }

    public function create()
    {
        return view('admin.truck-brands.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $data['slug']       = Str::slug($data['name']);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();

        TruckBrand::create($data);

        return redirect()->route('admin.truck-brands.index')
            ->with('success', 'Đã thêm nhà xe: ' . $data['name']);
    }

    public function edit(TruckBrand $truckBrand)
    {
        return view('admin.truck-brands.edit', compact('truckBrand'));
    }

    public function update(Request $request, TruckBrand $truckBrand)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $truckBrand->update($data);

        return redirect()->route('admin.truck-brands.index')
            ->with('success', 'Đã cập nhật: ' . $truckBrand->name);
    }

    public function destroy(TruckBrand $truckBrand)
    {
        $truckBrand->delete();

        return redirect()->route('admin.truck-brands.index')
            ->with('success', 'Đã xóa nhà xe.');
    }

    /**
     * AJAX: danh sách tuyến đi của một nhà xe.
     */
    public function routes(TruckBrand $truckBrand)
    {
        $routes = $truckBrand->routes()
            ->with(['stops.station'])
            ->orderBy('name')
            ->get()
            ->map(function ($route) {
                $stops = $route->stops->map(fn ($s) => [
                    'id'            => $s->id,
                    'name'          => optional($s->station)->name ?? '?',
                    'arrival_time'  => $s->arrival_time,
                    'travel_duration' => $s->travel_duration,
                ]);
                return [
                    'id'            => $route->id,
                    'name'          => $route->name,
                    'current_price' => $route->current_price,
                    'is_active'     => $route->is_active,
                    'stops'         => $stops,
                    'edit_url'      => route('admin.truck-routes.edit', $route),
                ];
            });

        return response()->json([
            'brand'  => ['id' => $truckBrand->id, 'name' => $truckBrand->name],
            'routes' => $routes,
            'total'  => $routes->count(),
        ]);
    }
}
