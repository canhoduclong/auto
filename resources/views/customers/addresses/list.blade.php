@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.address.list_all_title') }}</h2>

    <form method="GET" action="{{ route('customers.addresses.list') }}" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">{{ __('customers.address.search') }}</label>
            <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="{{ __('customers.address.search_placeholder') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label">{{ __('customers.address.customer') }}</label>
            <input type="text" name="customer_name" class="form-control" value="{{ request('customer_name') }}" placeholder="{{ __('customers.address.customer') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label">{{ __('customers.address.customer_phone') }}</label>
            <input type="text" name="customer_phone" class="form-control" value="{{ request('customer_phone') }}" placeholder="{{ __('customers.address.customer_phone') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label">{{ __('customers.address.city') }}</label>
            <select name="city" class="form-select">
                <option value="">{{ __('customers.address.all') }}</option>
                @foreach($cities as $c)
                    <option value="{{ $c }}" {{ request('city') === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-1">
            <label class="form-label">{{ __('customers.address.per_page') }}</label>
            <select name="perPage" class="form-select">
                @foreach([10,15,25,50,100] as $n)
                    <option value="{{ $n }}" {{ (int)request('perPage',15) === $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary">{{ __('common.actions.filter') }}</button>
            <a href="{{ route('customers.addresses.list') }}" class="btn btn-outline-secondary">{{ __('common.actions.reset') }}</a>
        </div>
    </form>

    <div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>ID</th> 
                <th>{{ __('customers.address.customer') }}</th>
                <th>{{ __('customers.index.phone') }}</th>
                <th>{{ __('customers.address.project_zone_block') }}</th>
                <th>{{ __('customers.address.floor_unit') }}</th>
                <th>{{ __('customers.address.full_address') }}</th>
                <th>{{ __('customers.address.default') }}</th>
                <th>{{ __('customers.index.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($addresses as $addr)
                <tr>
                    <td>{{ $addr->id }}</td>
                     
                    <td>
                        {{ optional($addr->customer)->name ?? __('dashboard.admin.na') }}

                        {{ $addr->customer_id }}
                        
                    </td>
                    <td>{{ optional($addr->customer)->phone ?? '-' }}</td>
                    <td>
                        {{ $addr->project_name ? $addr->project_name . ' / ' : '' }}
                        {{ $addr->zone ? $addr->zone . ' / ' : '' }}
                        {{ $addr->block ?? '' }}
                    </td>
                    <td>{{ $addr->floor ? 'T' . $addr->floor . ' / ' : '' }}{{ $addr->unit_number }}</td>
                    <td>
                        {{ $addr->street ?? '' }}
                        {{ $addr->ward ? ', ' . $addr->ward : '' }}
                        {{ $addr->district ? ', ' . $addr->district : '' }}
                        {{ $addr->city ? ', ' . $addr->city : '' }}
                    </td>
                    <td>
                        @if($addr->is_default)
                            <span class="badge bg-success">{{ __('customers.address.default') }}</span>
                        @endif
                    </td>
                    <td>
                        {{-- Link edit nested route: /customers/{customer}/addresses/{address}/edit --}}
                        <a href="{{ route('customers.addresses.edit', [$addr->customer_id, $addr->id]) }}" class="btn btn-sm btn-warning">{{ __('customers.address.edit_short') }}</a>

                        <form action="{{ route('customers.addresses.destroy', [$addr->customer_id, $addr->id]) }}" method="POST" style="display:inline-block" onsubmit="return confirm('{{ __('customers.address.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">{{ __('customers.address.delete_short') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">{{ __('customers.address.empty_search') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div>{{ __('customers.address.showing', ['from' => $addresses->firstItem() ?? 0, 'to' => $addresses->lastItem() ?? 0, 'total' => $addresses->total()]) }}</div>
        <div>{{ $addresses->links() }}</div>
    </div>
</div>
@endsection
