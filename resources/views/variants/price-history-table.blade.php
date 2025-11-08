<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>Giá</th>
            <th>Lý do</th>
            <th>Ngày áp dụng</th>
            <th>Người tạo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rules as $rule)
        <tr>
            <td>{{ number_format($rule->price, 0, ',', '.') }} đ</td>
            <td>{{ $rule->reason }}</td>
            <td>{{ \Carbon\Carbon::parse($rule->start_date)->format('d/m/Y H:i') }}</td>
            <td>{{ $rule->creator->name ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>