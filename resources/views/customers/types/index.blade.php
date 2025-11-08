@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Quản lý loại khách hàng</h2>
    <a href="{{ route('customertype.create') }}" class="btn btn-primary mb-3">+ Thêm loại khách hàng</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tên loại</th>
                <th>Chiết khấu (%)</th>
                <th>Freeship</th>
                <th>Ưu tiên</th>
                <th>Điều kiện</th>
                <th width="150">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($types as $type)
                <tr>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->discount_rate }}%</td>
                    <td>{{ $type->free_shipping ? 'Có' : 'Không' }}</td>
                    <td>{{ $type->priority_level }}</td>
                    <td>
                        ≥ {{ $type->min_orders }} đơn,
                        ≥ {{ number_format($type->min_total_spent) }} VND
                    </td>
                    <td>
                        <a href="{{ route('customertype.edit', $type) }}" class="btn btn-sm btn-warning">Sửa</a>
                        <form action="{{ route('customertype.destroy', $type) }}" method="POST" style="display:inline-block">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Bạn chắc chắn muốn xóa?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $types->links() }}
</div>
@endsection
