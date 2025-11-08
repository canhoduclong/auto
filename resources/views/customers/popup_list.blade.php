@if($customers->count())
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>Tên</th>
            <th>SĐT</th>
            <th>Email</th>
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
                <button class="btn btn-sm btn-primary btn-select-customer" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}">Chọn</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div>
    {!! $customers->appends(request()->except('page'))->links('customers.popup_pagination') !!}
</div>
@else
<p>Không tìm thấy khách hàng phù hợp.</p>
@endif
