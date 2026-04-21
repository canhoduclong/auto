<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\TruckBrand;
use App\Models\TruckRoute;
use App\Models\TruckRouteStop;
use App\Models\TruckStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TruckRouteController extends Controller
{
    public function index(Request $request)
    {
        $q       = $request->input('q');
        $brandId = $request->input('brand_id');

        $routes = TruckRoute::query()
            ->with(['brand', 'stops' => fn ($q) => $q->with('station.province')->orderBy('sort_order')])
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->when($brandId, fn ($query) => $query->where('truck_brand_id', $brandId))
            ->withCount('stops')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $brands = TruckBrand::orderBy('name')->get(['id', 'name']);

        return view('admin.truck-routes.index', compact('routes', 'brands', 'q', 'brandId'));
    }

    public function create()
    {
        $brands    = TruckBrand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name']);
        $stations  = TruckStation::with(['brand', 'province'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'brand_id', 'province_id', 'address', 'phone']);

        return view('admin.truck-routes.create', compact('brands', 'provinces', 'stations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'truck_brand_id'=> 'nullable|exists:truck_brands,id',
            'current_price' => 'nullable|numeric|min:0',
            'regulations'   => 'nullable|string',
            'description'   => 'nullable|string',
            'note'          => 'nullable|string',
            'is_active'     => 'nullable|boolean',
            'stops'         => 'nullable|array',
            'stops.*.truck_station_id'   => 'required|exists:truck_stations,id',
            'stops.*.arrival_time'        => 'nullable|string|max:10',
            'stops.*.travel_duration'     => 'nullable|string|max:30',
            'stops.*.note'                => 'nullable|string|max:255',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();
        $stopsData = $data['stops'] ?? [];
        unset($data['stops']);

        DB::transaction(function () use ($data, $stopsData) {
            $route = TruckRoute::create($data);

            foreach ($stopsData as $order => $stop) {
                TruckRouteStop::create([
                    'truck_route_id'   => $route->id,
                    'truck_station_id' => $stop['truck_station_id'],
                    'sort_order'       => $order,
                    'arrival_time'     => $stop['arrival_time'] ?? null,
                    'travel_duration'  => $stop['travel_duration'] ?? null,
                    'note'             => $stop['note'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.truck-routes.index')
            ->with('success', 'Đã tạo tuyến: ' . $data['name']);
    }

    public function edit(TruckRoute $truckRoute)
    {
        $truckRoute->load(['stops' => fn ($q) => $q->with(['station.province', 'station.ward'])->orderBy('sort_order')]);
        $brands    = TruckBrand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name']);
        $stations  = TruckStation::with(['brand', 'province'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'brand_id', 'province_id', 'address', 'phone']);

        return view('admin.truck-routes.edit', compact('truckRoute', 'brands', 'provinces', 'stations'));
    }

    public function update(Request $request, TruckRoute $truckRoute)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'truck_brand_id'=> 'nullable|exists:truck_brands,id',
            'current_price' => 'nullable|numeric|min:0',
            'regulations'   => 'nullable|string',
            'description'   => 'nullable|string',
            'note'          => 'nullable|string',
            'is_active'     => 'nullable|boolean',
            'stops'         => 'nullable|array',
            'stops.*.truck_station_id'   => 'required|exists:truck_stations,id',
            'stops.*.arrival_time'        => 'nullable|string|max:10',
            'stops.*.travel_duration'     => 'nullable|string|max:30',
            'stops.*.note'                => 'nullable|string|max:255',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $stopsData = $data['stops'] ?? [];
        unset($data['stops']);

        DB::transaction(function () use ($data, $stopsData, $truckRoute) {
            $truckRoute->update($data);
            $truckRoute->stops()->delete();

            foreach ($stopsData as $order => $stop) {
                TruckRouteStop::create([
                    'truck_route_id'   => $truckRoute->id,
                    'truck_station_id' => $stop['truck_station_id'],
                    'sort_order'       => $order,
                    'arrival_time'     => $stop['arrival_time'] ?? null,
                    'travel_duration'  => $stop['travel_duration'] ?? null,
                    'note'             => $stop['note'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.truck-routes.index')
            ->with('success', 'Đã cập nhật tuyến: ' . $truckRoute->name);
    }

    public function destroy(TruckRoute $truckRoute)
    {
        $truckRoute->delete();

        return redirect()->route('admin.truck-routes.index')
            ->with('success', 'Đã xóa tuyến.');
    }

    // ── AJAX endpoints ──────────────────────────────────────────────────────────

    // ── Stop CRUD (AJAX) ────────────────────────────────────────────────────────

    public function storeStop(Request $request, TruckRoute $truckRoute)
    {
        $data = $request->validate([
            'truck_station_id' => 'required|exists:truck_stations,id',
            'arrival_time'     => 'nullable|string|max:10',
            'travel_duration'  => 'nullable|string|max:30',
            'note'             => 'nullable|string|max:255',
        ]);

        $maxOrder = $truckRoute->stops()->max('sort_order') ?? -1;

        $stop = TruckRouteStop::create([
            'truck_route_id'   => $truckRoute->id,
            'truck_station_id' => $data['truck_station_id'],
            'sort_order'       => $maxOrder + 1,
            'arrival_time'     => $data['arrival_time'] ?? null,
            'travel_duration'  => $data['travel_duration'] ?? null,
            'note'             => $data['note'] ?? null,
        ]);

        $stop->load('station');

        return response()->json(['success' => true, 'stop' => $this->stopToArray($stop)]);
    }

    public function updateStop(Request $request, TruckRoute $truckRoute, TruckRouteStop $stop)
    {
        abort_if((int) $stop->truck_route_id !== (int) $truckRoute->id, 403);

        $data = $request->validate([
            'truck_station_id' => 'nullable|exists:truck_stations,id',
            'arrival_time'     => 'nullable|string|max:10',
            'travel_duration'  => 'nullable|string|max:30',
            'note'             => 'nullable|string|max:255',
        ]);

        $stop->update([
            'truck_station_id' => $data['truck_station_id'] ?? $stop->truck_station_id,
            'arrival_time'     => $data['arrival_time'],
            'travel_duration'  => $data['travel_duration'],
        ]);

        $stop->load('station');

        return response()->json(['success' => true, 'stop' => $this->stopToArray($stop)]);
    }

    public function destroyStop(TruckRoute $truckRoute, TruckRouteStop $stop)
    {
        abort_if((int) $stop->truck_route_id !== (int) $truckRoute->id, 403);

        $stop->delete();

        // Re-number remaining stops
        $i = 0;
        foreach ($truckRoute->stops()->orderBy('sort_order')->get() as $s) {
            TruckRouteStop::where('id', $s->id)->update(['sort_order' => $i++]);
        }

        return response()->json(['success' => true]);
    }

    private function stopToArray(TruckRouteStop $stop): array
    {
        return [
            'id'               => $stop->id,
            'truck_station_id' => $stop->truck_station_id,
            'name'             => optional($stop->station)->name ?? '?',
            'arrival_time'     => $stop->arrival_time,
            'travel_duration'  => $stop->travel_duration,
            'note'             => $stop->note,
            'sort_order'       => $stop->sort_order,
        ];
    }

    /**
     * AJAX: search truck stations for route builder
     */
    public function searchStations(Request $request)
    {
        $q        = $request->input('q', '');
        $brandId  = $request->input('brand_id');
        $provinceId = $request->input('province_id');

        $stations = TruckStation::query()
            ->with(['brand:id,name', 'province:id,name', 'ward:id,name'])
            ->where('is_active', true)
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            }))
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->when($provinceId, fn ($query) => $query->where('province_id', $provinceId))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'brand_id', 'province_id', 'ward_id', 'address', 'phone',
                   'has_home_delivery', 'home_delivery_fee']);

        return response()->json($stations);
    }
}
