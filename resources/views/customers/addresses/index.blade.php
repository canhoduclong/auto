@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">
        <i class="bi bi-geo-alt-fill text-primary"></i> 
        Địa chỉ của khách hàng
    </h2> 

    <!-- Thông tin khách hàng -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="bi bi-person-circle"></i> {{ $customer->name }}
            </h5>
            <p class="mb-1"><strong>Mã KH:</strong> <span class="badge bg-secondary">{{ $customer->id }}</span></p>
            <p class="mb-1"><strong>Email:</strong> {{ $customer->email ?? '—' }}</p>
            <p class="mb-1"><strong>SĐT:</strong> {{ $customer->phone ?? '—' }}</p>
        </div>
    </div>

    <!-- Button thêm địa chỉ -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Danh sách địa chỉ</h5>
        <a href="{{ route('customers.addresses.create', $customer->id) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle"></i> Thêm địa chỉ
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
 <!-- Danh sách địa chỉ dạng bảng -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Số nhà/Căn hộ</th>
                        <th>Chi tiết</th> 
                        <th class="text-center">Mặc định</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>

@forelse($customer->addresses as $index => $address)
    <tr>
        <td>{{ $index + 1 }}</td>

        <td>
            @if($address->unit_number) 
                <!-- Căn hộ -->
                {{ $address->unit_number }} - {{ $address->project_name }}
                <br>
                <small class="text-muted">
                    Block: {{ $address->block ?? '—' }},
                    Khu: {{ $address->zone ?? '—' }},
                    Tầng: {{ $address->floor ?? '—' }}, 
                </small>
            @else 
                <!-- Nhà riêng -->
                {{ $address->house_number ?? ' Chưa cập nhật' }}
            @endif
        </td>

        <td>
            {{ $address->street }},
            {{ $address->ward }},
            {{ $address->district }},
            {{ $address->city }}
        </td>

        <td class="text-center">
            @if($address->is_default)
                <span class="badge bg-success">✓</span>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>

        <td class="text-end">
            <a href="{{ route('customers.addresses.edit', [$customer->id, $address->id]) }}" 
               class="btn btn-sm btn-outline-warning me-1">
               <i class="bi bi-pencil-square"></i> Edit
            </a>
            <form action="{{ route('customers.addresses.destroy', [$customer->id, $address->id]) }}" 
                  method="POST" class="d-inline"> 
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa địa chỉ này?')">
                    <i class="bi bi-trash"></i> Del
                </button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="text-center text-muted py-4">Chưa có địa chỉ nào</td>
    </tr>
@endforelse

                    
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
