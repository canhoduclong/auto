<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderFeeType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderFeeTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(): View
    {
        return view('admin.order-fee-types.index', [
            'feeTypes' => OrderFeeType::query()->withCount('orderFees')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $code = Str::slug($data['code'] ?: $data['name'], '_');
        if ($code === '' || OrderFeeType::query()->where('code', $code)->exists()) {
            return back()->withErrors(['code' => 'Mã phí đã tồn tại hoặc không hợp lệ.'])->withInput();
        }
        if ($data['calculation_type'] === OrderFeeType::CALCULATION_PERCENT && (float) $data['default_value'] > 100) {
            return back()->withErrors(['default_value' => 'Giá trị phần trăm không được vượt quá 100%.'])->withInput();
        }

        OrderFeeType::query()->create(array_merge($data, [
            'code' => $code,
            'is_active' => $request->boolean('is_active'),
            'is_system' => false,
        ]));

        return back()->with('success', 'Đã thêm loại phí mới.');
    }

    public function update(Request $request, OrderFeeType $orderFeeType): RedirectResponse
    {
        $data = $this->validated($request, $orderFeeType);
        $hasBeenUsed = $orderFeeType->orderFees()->exists();
        $code = ($orderFeeType->is_system || $hasBeenUsed)
            ? $orderFeeType->code
            : Str::slug($data['code'] ?: $data['name'], '_');
        if ($code === '' || OrderFeeType::query()->where('code', $code)->where('id', '!=', $orderFeeType->id)->exists()) {
            return back()->withErrors(['code' => 'Mã phí đã tồn tại hoặc không hợp lệ.'])->withInput();
        }
        if ($data['calculation_type'] === OrderFeeType::CALCULATION_PERCENT && (float) $data['default_value'] > 100) {
            return back()->withErrors(['default_value' => 'Giá trị phần trăm không được vượt quá 100%.'])->withInput();
        }
        if ($orderFeeType->is_system) {
            $data['calculation_type'] = $orderFeeType->calculation_type;
            $data['direction'] = $orderFeeType->direction;
        }

        $orderFeeType->update(array_merge($data, [
            'code' => $code,
            'is_active' => $request->boolean('is_active'),
        ]));

        return back()->with('success', 'Đã cập nhật loại phí.');
    }

    public function toggle(OrderFeeType $orderFeeType): RedirectResponse
    {
        $orderFeeType->update(['is_active' => ! $orderFeeType->is_active]);

        return back()->with('success', $orderFeeType->is_active ? 'Đã bật loại phí.' : 'Đã ngừng sử dụng loại phí.');
    }

    public function destroy(OrderFeeType $orderFeeType): RedirectResponse
    {
        if ($orderFeeType->is_system || $orderFeeType->orderFees()->exists()) {
            $orderFeeType->update(['is_active' => false]);

            return back()->with('success', 'Loại phí đã phát sinh dữ liệu nên được chuyển sang ngừng sử dụng để bảo toàn lịch sử.');
        }

        $orderFeeType->delete();

        return back()->with('success', 'Đã xóa loại phí.');
    }

    private function validated(Request $request, ?OrderFeeType $feeType = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:80', Rule::unique('order_fee_types', 'code')->ignore($feeType?->id)],
            'calculation_type' => ['required', Rule::in([OrderFeeType::CALCULATION_FIXED, OrderFeeType::CALCULATION_PERCENT])],
            'direction' => ['required', Rule::in([OrderFeeType::DIRECTION_CHARGE, OrderFeeType::DIRECTION_DISCOUNT])],
            'default_value' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
