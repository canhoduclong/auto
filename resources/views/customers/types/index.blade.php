@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('customers.type.index_title') }}</h2>
    <a href="{{ route('customertype.create') }}" class="btn btn-primary mb-3">{{ __('customers.type.add') }}</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>{{ __('customers.type.name') }}</th>
                <th>{{ __('customers.type.discount_rate') }}</th>
                <th>{{ __('customers.type.free_shipping') }}</th>
                <th>{{ __('customers.type.priority') }}</th>
                <th>{{ __('customers.type.conditions') }}</th>
                <th width="150">{{ __('customers.index.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($types as $type)
                <tr>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->discount_rate }}%</td>
                    <td>{{ $type->free_shipping ? __('customers.form.yes') : __('customers.form.no') }}</td>
                    <td>{{ $type->priority_level }}</td>
                    <td>
                        ≥ {{ $type->min_orders }} {{ __('customers.type.order_unit') }},
                        ≥ {{ number_format($type->min_total_spent) }} VND
                    </td>
                    <td>
                        <a href="{{ route('customertype.edit', $type) }}" class="btn btn-sm btn-warning">{{ __('common.actions.edit') }}</a>
                        <form action="{{ route('customertype.destroy', $type) }}" method="POST" style="display:inline-block">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('{{ __('customers.type.delete_confirm') }}')">{{ __('common.actions.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $types->links() }}
</div>
@endsection
