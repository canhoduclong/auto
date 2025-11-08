@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Điều chỉnh giá - {{ $variant->product->name }} ({{ $variant->size }}, {{ $variant->quality }})</h4>

    <form action="{{ route('variants.update-price', $variant->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Giá hiện tại</label>
            <input type="text" class="form-control" 
                   value="{{ number_format($variant->final_price, 0, ',', '.') }}" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Giá mới</label>
            <input type="number" step="0.01" name="price" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Lý do điều chỉnh</label>
            <input type="text" name="reason" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Lưu</button>
        @if(request('from') === 'product-variants')
            <a href="{{ route('product-variants.index') }}" class="btn btn-secondary">Hủy</a>
        @else
            <a href="{{ route('products.edit', $variant->product_id) }}" class="btn btn-secondary">Hủy</a>
        @endif
    </form>

    <hr>

    <h5>Lịch sử điều chỉnh giá</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Ngày</th>
                <th>Giá cũ</th>
                <th>Giá mới</th>
                <th>Người thay đổi</th>
                <th>Lý do</th>
            </tr>
        </thead>
        <tbody>
            @foreach($variant->priceRules()->latest()->get() as $rule)
            <tr>
                <td>{{ $rule->start_date }}</td>
                <td>
                    @php 
                        $log = $variant->priceLogs()->where('new_price', $rule->price)->first(); 
                    @endphp
                    {{ $log ? number_format($log->old_price, 0, ',', '.') : '-' }}
                </td>
                <td>{{ number_format($rule->price, 0, ',', '.') }}</td>
                <td>{{ $log && $log->user ? $log->user->name : 'System' }}</td>
                <td>{{ $rule->reason }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
