@if($customers->count())
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>{{ __('customers.popup.name') }}</th>
            <th>{{ __('customers.popup.phone') }}</th>
            <th>{{ __('customers.popup.email') }}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($customers as $customer)
        <tr>
            <td>{{ $customer->name }}</td>
            <td>{{ $customer->phone }}</td>
            <td>{{ $customer->email }}</td>
            <td>
                <button class="btn btn-sm btn-primary btn-select-customer" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}">{{ __('customers.popup.select') }}</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div>
    {!! $customers->appends(request()->except('page'))->links('customers.popup_pagination') !!}
</div>
@else
<p>{{ __('customers.popup.empty') }}</p>
@endif
