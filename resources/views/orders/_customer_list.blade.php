<table class="table table-bordered">
    <thead>
        <tr>
            <th>{{ __('orders.labels.actions') }}</th>
            <th>{{ __('orders.labels.customer') }}</th>
            <th>Email</th>
            <th>{{ __('orders.labels.phone') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($customers as $customer)
            <tr data-delivery-time="{{ $customer->delivery_time }}">
                <td>
                    <input class="form-check-input" type="radio" name="customer_id" id="customer_{{ $customer->id }}" value="{{ $customer->id }}" required>
                </td>
                <td>
                    @if($customer->is_pinned)
                        <span class="text-warning me-1">★</span>
                    @endif
                    @if((int) ($customer->sort_order ?? 0) > 0)
                        <span class="badge bg-light text-dark border me-1">{{ (int) $customer->sort_order }}</span>
                    @endif
                    {{ $customer->name }}
                </td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="d-flex justify-content-center">
    {{ $customers->appends(request()->query())->links() }}
</div>
