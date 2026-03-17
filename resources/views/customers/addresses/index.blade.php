@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">
        <i class="bi bi-geo-alt-fill text-primary"></i> 
        {{ __('customers.address.title') }}
    </h2> 

    <!-- Thông tin khách hàng -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="bi bi-person-circle"></i> {{ $customer->name }}
            </h5>
            <p class="mb-1"><strong>{{ __('customers.address.customer_code') }}:</strong> <span class="badge bg-secondary">{{ $customer->id }}</span></p>
            <p class="mb-1"><strong>Email:</strong> {{ $customer->email ?? '—' }}</p>
            <p class="mb-1"><strong>{{ __('customers.address.customer_phone') }}:</strong> {{ $customer->phone ?? '—' }}</p>
        </div>
    </div>

    <!-- Button thêm địa chỉ -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ __('customers.address.list_title') }}</h5>
        <a href="{{ route('customers.addresses.create', $customer->id) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle"></i> {{ __('customers.address.add') }}
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
                        <th>{{ __('customers.address.house_or_unit') }}</th>
                        <th>{{ __('customers.address.detail') }}</th>
                        <th class="text-center">{{ __('customers.address.default') }}</th>
                        <th class="text-end">{{ __('customers.index.actions') }}</th>
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
                    {{ __('customers.address.block') }}: {{ $address->block ?? '—' }},
                    {{ __('customers.address.zone') }}: {{ $address->zone ?? '—' }},
                    {{ __('customers.address.floor') }}: {{ $address->floor ?? '—' }},
                </small>
            @else 
                <!-- Nhà riêng -->
                {{ $address->house_number ?? ' ' . __('customers.address.house_not_updated') }}
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
               <i class="bi bi-pencil-square"></i> {{ __('customers.address.edit_short') }}
            </a>
            <form action="{{ route('customers.addresses.destroy', [$customer->id, $address->id]) }}" 
                  method="POST" class="d-inline"> 
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('customers.address.delete_confirm') }}')">
                    <i class="bi bi-trash"></i> {{ __('customers.address.delete_short') }}
                </button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="text-center text-muted py-4">{{ __('customers.address.empty') }}</td>
    </tr>
@endforelse

                    
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
