@extends('layouts.admin')
@section('content')
<div class="container mt-4">
    <h2>Test nhập kho & tạo đơn hàng</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <form method="POST" action="{{ route('admin.test-inventory-orders.stock') }}">
        @csrf
        <h4>Nhập kho hôm nay</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Biến thể</th>
                    <th>Số lượng nhập</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    @foreach($product->variants as $variant)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $variant->name ?? $variant->sku }}</td>
                            <td>
                                <input type="number" name="stock[{{ $variant->id }}]" value="0" min="0" class="form-control" />
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Nhập kho hôm nay</button>
    </form>
    <hr>
    <form method="POST" action="{{ route('admin.test-inventory-orders.create-orders') }}">
        @csrf
        <h4>Tạo 10 đơn hàng test cho sale</h4>
        <button type="submit" class="btn btn-success">Tạo 10 đơn hàng</button>
    </form>
</div>
@endsection
