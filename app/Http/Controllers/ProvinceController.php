<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvinceController extends Controller
{
    public function index(Request $request)
    {
        $provinces = Province::withCount('wards')
            ->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        return view('provinces.index', compact('provinces'));
    }

    public function create()
    {
        return view('provinces.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:provinces,name',
            'wards' => 'nullable|string',
        ]);

        $province = Province::create([
            'code' => $this->generateUniqueCode($validated['name'], Province::class),
            'name' => $validated['name'],
            'type' => null,
        ]);

        $this->createWardsFromLines($province, $validated['wards'] ?? '');

        return redirect()->route('provinces.index')->with('success', 'Đã tạo tỉnh/thành phố thành công.');
    }

    public function show(Province $province)
    {
        $wards = $province->wards()->orderBy('name')->get();

        return view('provinces.show', compact('province', 'wards'));
    }

    public function edit(Province $province)
    {
        return view('provinces.edit', compact('province'));
    }

    public function update(Request $request, Province $province)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:provinces,name,' . $province->id,
        ]);

        $province->update([
            'name' => $validated['name'],
        ]);

        return redirect()->route('provinces.index')->with('success', 'Đã cập nhật tỉnh/thành phố thành công.');
    }

    public function destroy(Province $province)
    {
        DB::transaction(function () use ($province) {
            $province->delete();
        });

        return redirect()->route('provinces.index')->with('success', 'Đã xóa tỉnh/thành phố và tất cả phường/xã liên quan.');
    }

    public function storeWard(Request $request, Province $province)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $province->wards()->create([
            'name' => $validated['name'],
            'code' => $this->generateUniqueCode($validated['name'], Ward::class),
            'type' => 'Phường/Xã',
        ]);

        return redirect()->route('provinces.show', $province)->with('success', 'Đã thêm phường/xã mới.');
    }

    public function updateWard(Request $request, Province $province, Ward $ward)
    {
        abort_unless($ward->province_id === $province->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $ward->update(['name' => $validated['name']]);

        return redirect()->route('provinces.show', $province)->with('success', 'Đã cập nhật phường/xã.');
    }

    public function destroyWard(Province $province, Ward $ward)
    {
        abort_unless($ward->province_id === $province->id, 404);

        $ward->delete();

        return redirect()->route('provinces.show', $province)->with('success', 'Đã xóa phường/xã.');
    }

    protected function createWardsFromLines(Province $province, string $lines): void
    {
        collect(explode("\n", $lines))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->each(function ($name) use ($province) {
                $province->wards()->create([
                    'name' => $name,
                    'code' => $this->generateUniqueCode($name, Ward::class),
                    'type' => 'Phường/Xã',
                ]);
            });
    }

    protected function generateUniqueCode(string $name, string $modelClass): string
    {
        $baseCode = Str::slug($name) ?: 'item';
        $code = $baseCode;
        $counter = 1;

        while ($modelClass::where('code', $code)->exists()) {
            $counter++;
            $code = $baseCode . '-' . $counter;
        }

        return $code;
    }
}
