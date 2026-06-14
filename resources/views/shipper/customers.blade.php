@extends('layouts.shipper')

@section('title', 'Khách hàng')
@section('subtitle', 'Khách hàng trong lịch giao theo ngày')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Ngày giao</label>
                <input type="date" name="date" value="{{ $selectedDate }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sắp xếp theo</label>
                <select name="sort" class="form-select">
                    <option value="delivery_time" @selected($sort === 'delivery_time')>Giờ giao</option>
                    <option value="name" @selected($sort === 'name')>Tên khách hàng</option>
                    <option value="orders_count" @selected($sort === 'orders_count')>Số đơn</option>
                    <option value="total" @selected($sort === 'total')>Tổng tiền</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Thứ tự</label>
                <select name="direction" class="form-select">
                    <option value="asc" @selected($direction === 'asc')>Tăng dần</option>
                    <option value="desc" @selected($direction === 'desc')>Giảm dần</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Lọc</button>
            </div>
        </form>
    </div>
</div>

@foreach([
    ['title' => 'Khách hàng được gán cố định', 'customers' => $fixedCustomers, 'class' => 'success'],
    ['title' => 'Khách hàng chưa được gán cố định', 'customers' => $unassignedCustomers, 'class' => 'secondary'],
] as $section)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>{{ $section['title'] }}</strong>
            <span class="badge bg-{{ $section['class'] }}">{{ $section['customers']->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Khách hàng</th>
                        <th>Liên hệ</th>
                        <th>Giờ giao</th>
                        <th class="text-center">Số đơn</th>
                        <th class="text-end">Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($section['customers'] as $customer)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $customer->name }}</div>
                                <div class="small text-muted">{{ $customer->address ?: 'Chưa có địa chỉ' }}</div>
                            </td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->delivery_time ?: '—' }}</td>
                            <td class="text-center">{{ $customer->orders_count }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $customer->orders_total) }}đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Không có khách hàng trong ngày này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection
