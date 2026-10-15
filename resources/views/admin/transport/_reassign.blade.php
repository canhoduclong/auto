@php
    $transportSlip = $transportSlip ?? null;
    $transportGroup = $transportGroup ?? null;
    $assignedSlip = $transportSlip ?: $transportGroup?->dispatchEntry?->slip;
    $transportDrivers = $shippers ?? \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'manager_shipper']))->orderBy('name')->get();
@endphp
<details class="my-2 border rounded p-2">
    <summary class="text-primary" style="cursor:pointer;">Admin: Đổi tài xế {{ $assignedSlip ? 'toàn phiếu xuất' : 'nhóm điều chuyển' }}</summary>
    <form method="POST" action="{{ $assignedSlip ? route('admin.warehouse-dispatch-slips.reassign', $assignedSlip) : route('admin.order-transfers.reassign', $transportGroup) }}" class="d-flex flex-wrap gap-2 mt-2"
          onsubmit="return confirm('Chuyển toàn bộ đơn điều chuyển chưa hoàn tất trong nhóm/phiếu này sang tài xế được chọn?');">
        @csrf
        <select class="form-select form-select-sm" name="shipper_id" required aria-label="Tài xế mới">
            <option value="">Chọn tài xế mới</option>
            @foreach($transportDrivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach
        </select>
        <input name="reason" class="form-control form-control-sm" required minlength="5" maxlength="500" placeholder="Lý do đổi tài xế">
        <button class="btn btn-warning btn-sm">Chuyển tài xế</button>
    </form>
</details>
