<table class="table table-bordered">
    <thead>
        <tr>
            <th>Chọn</th>
            <th>Tên</th>
            <th>Email</th>
            <th>Số điện thoại</th>
        </tr>
    </thead>
    <tbody>
        @foreach($customers as $customer)
            <tr>
                <td>
                    <input class="form-check-input" type="radio" name="customer_id" id="customer_{{ $customer->id }}" value="{{ $customer->id }}" required>
                </td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="d-flex justify-content-center">
    {{ $customers->appends(request()->query())->links() }}
</div>
