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
        $validated = $request->validateWithBag('storeWard', [
            'name'  => 'nullable|string|max:255',
            'wards' => 'nullable|string',
        ]);

        $lines = $validated['wards'] ?? '';

        if (! empty($validated['name'])) {
            $lines = trim($lines) . "\n" . trim($validated['name']);
        }

        $names = collect(explode("\n", (string) $lines))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique();

        if ($names->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Vui lòng nhập ít nhất 1 phường/xã.'], 422);
            }
            return back()
                ->withErrors(['wards' => 'Vui lòng nhập ít nhất 1 phường/xã.'], 'storeWard')
                ->withInput();
        }

        $existingNames = $province->wards()
            ->get(['name'])
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->all();

        $added   = 0;
        $created = [];

        foreach ($names as $name) {
            if (in_array(mb_strtolower($name), $existingNames, true)) {
                continue;
            }

            $ward = $province->wards()->create([
                'name' => $name,
                'code' => $this->generateUniqueCode($name, Ward::class),
                'type' => 'Phường/Xã',
            ]);

            $existingNames[] = mb_strtolower($name);
            $created[]       = ['id' => $ward->id, 'name' => $ward->name];
            $added++;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'added'   => $added,
                'wards'   => $created,
                'message' => $added > 0 ? "Đã thêm {$added} phường/xã." : 'Không có phường/xã mới (trùng tên).',
            ]);
        }

        if ($added === 0) {
            return redirect()->route('provinces.show', $province)
                ->with('success', 'Không có phường/xã mới được thêm (danh sách nhập bị trùng).');
        }

        return redirect()->route('provinces.show', $province)
            ->with('success', "Đã thêm {$added} phường/xã mới.");
    }

    public function indexWards(Request $request, Province $province)
    {
        $q     = $request->input('q', '');
        $wards = $province->wards()
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'province' => ['id' => $province->id, 'name' => $province->name],
            'wards'    => $wards,
            'total'    => $wards->count(),
        ]);
    }

    public function updateWard(Request $request, Province $province, Ward $ward)
    {
        abort_unless($ward->province_id === $province->id, 404);

        $validated = $request->validateWithBag('updateWard', [
            'name'    => 'required|string|max:255',
            'ward_id' => 'required|integer',
        ]);

        abort_unless((int) $validated['ward_id'] === (int) $ward->id, 404);

        $ward->update(['name' => $validated['name']]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'ward' => ['id' => $ward->id, 'name' => $ward->name]]);
        }

        return redirect()->route('provinces.show', $province)->with('success', 'Đã cập nhật phường/xã.');
    }

    public function destroyWard(Request $request, Province $province, Ward $ward)
    {
        abort_unless($ward->province_id === $province->id, 404);

        $ward->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('provinces.show', $province)->with('success', 'Đã xóa phường/xã.');
    }

    protected function createWardsFromLines(Province $province, string $lines): void
    {
        collect(explode("\n", $lines))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->each(function ($line) use ($province) {
                // Tách theo định dạng: type - name
                $type = null;
                $name = null;
                if (preg_match('/^([^\-]+)\s*-\s*(.+)$/u', $line, $matches)) {
                    $type = trim($matches[1]);
                    $name = trim($matches[2]);
                } else {
                    // Nếu không đúng định dạng, coi toàn bộ là name, type để null
                    $name = $line;
                }
                if ($name) {
                    $province->wards()->create([
                        'name' => $name,
                        'code' => $this->generateUniqueCode($name, Ward::class),
                        'type' => $type,
                    ]);
                }
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
