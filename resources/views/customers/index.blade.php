@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.index.title') }}</h2>
    <div class="d-flex gap-2 mb-3 align-items-end">
        <form id="bulkDeleteForm" action="{{ route('customers.bulkDelete') }}" method="POST" class="d-inline-block ms-2">
            @csrf
            <input type="hidden" name="ids" id="bulkDeleteIds">
            <button type="submit" class="btn btn-danger" onclick="return confirm('{{ __('customers.index.bulk_delete_confirm') }}')">{{ __('customers.index.bulk_delete') }}</button>
        </form>
        <div class="d-inline-block">
            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" class="d-inline-block">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" style="display:inline-block;width:auto" required>
                <button class="btn btn-warning">{{ __('customers.index.import_excel') }}</button>
            </form>
            <a href="{{ route('customers.export') }}" class="btn btn-info">{{ __('customers.index.export_excel') }}</a>
            <a href="{{ route('customers.create') }}" class="btn btn-success">{{ __('customers.index.add') }}</a>
        </div>
    </div> 
    <div class="mb-3 d-flex justify-content-between align-items-end">
        <form class="row g-2" method="GET" action="{{ route('customers.index') }}">
            <div class="col-auto">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('customers.index.search_placeholder') }}">
            </div>
            <div class="col-auto">
                <select name="type_id" class="form-select">
                    <option value="">{{ __('customers.index.all_types') }}</option>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}" {{ (string)$t->id === request('type_id') ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($users)
            <div class="col-auto">
                <select name="assigned_to" class="form-select" onchange="this.form.submit()">
                    <option value="">{{ __('customers.index.all_staff') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string)$user->id === request('assigned_to') ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-auto">
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach([10,15,25,50,100] as $pp)
                        <option value="{{ $pp }}" {{ request('per_page',15)==$pp?'selected':'' }}>{{ $pp }} {{ __('customers.index.per_page_suffix') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary">{{ __('common.actions.filter') }}</button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">{{ __('common.actions.reset') }}</a>
            </div>
        </form>

       
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>#</th>
                <th>{{ __('customers.index.name') }}</th>
                <th>{{ __('customers.index.phone') }}</th>
                <th>{{ __('customers.index.email') }}</th>
                <th>{{ __('customers.index.type') }}</th>
                <th>Sale phụ trách</th>
                <th>Người tạo</th>
                <th>{{ __('customers.index.default_address') }}</th>
                <th>{{ __('customers.index.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><input type="checkbox" class="row-check" value="{{ $customer->id }}"></td>
                    <td>{{ $customer->id }}</td>
                    <td>
                        {{ $customer->name }} <br>
                         @if($customer->dob)
                            <small class="text-muted">
                                {{ $customer->dob->format('d/m/Y') }} - 
                                {{ $customer->dob->age }} {{ __('customers.index.years_old') }}
                            </small>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ optional($customer->type)->name ?? '-' }}</td>
                    <td>{{ optional($customer->assignedTo)->name ?? '-' }}</td>
                    <td>{{ optional($customer->user)->name ?? '-' }}</td>
                    <td>
                         @if($customer->addresses->isNotEmpty()) 
                            @php
                                $default = $customer->addresses->firstWhere('is_default', 1);
                            @endphp
                            @if($default)
                                {{ $default->note }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-nowrap"> 
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">{{ __('common.actions.edit') }}</a>
                        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-sm btn-info">{{ __('customers.index.addresses') }}</a>
                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-primary">{{ __('customers.index.report') }}</a>
                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('customers.index.delete_confirm') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">{{ __('common.actions.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">{{ __('customers.index.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

    </div>

    <div class="mt-3">
        {{ $customers->appends(request()->except('page'))->links() }}
    </div>
    <script>
    // Checkbox chọn tất cả
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('checkAll');
        const rowChecks = document.querySelectorAll('.row-check');
        checkAll && checkAll.addEventListener('change', function() {
            rowChecks.forEach(cb => cb.checked = checkAll.checked);
        });
        // Gửi danh sách id đã chọn khi submit xóa nhiều
        const bulkForm = document.getElementById('bulkDeleteForm');
        if(bulkForm) {
            bulkForm.addEventListener('submit', function(e) {
                const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
                if(ids.length === 0) {
                    alert(@json(__('customers.index.choose_one_for_bulk_delete')));
                    e.preventDefault();
                    return false;
                }
                document.getElementById('bulkDeleteIds').value = ids.join(',');
            });
        }
    });
    </script>
</div>
@endsection
