@extends('layouts.app', ['menu' => 'product'])
@section('content')
<div class="content">
    <h2>Bảng tỷ lệ pha lóc</h2>
    <p class="text-muted">Tỷ lệ chung của từng sản phẩm thành phần phụ. Thành phần chính được tính bằng 100% trừ tổng tỷ lệ phụ trong cấu hình nguyên con.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('products.cutting-ratios.update') }}">
        @csrf @method('PUT')
        <div class="card table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Sản phẩm pha lóc</th><th>Số biến thể</th><th>Tỷ lệ khối lượng (%)</th><th></th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td class="fw-semibold">{{ $product->name }}</td>
                        <td>{{ $product->variants_count }}</td>
                        <td><input type="number" name="rates[{{ $product->id }}]" class="form-control" style="max-width:180px" min="0" max="100" step="0.001" value="{{ old('rates.'.$product->id, $product->cutting_percentage) }}" placeholder="Chưa cấu hình" aria-label="Tỷ lệ {{ $product->name }}" @cannot('update', $product) readonly @endcannot></td>
                        <td>@can('update', $product)<a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Sửa sản phẩm</a>@endcan</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Chưa có sản phẩm pha lóc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @can('update', \App\Models\Product::class)
        <button class="btn btn-primary" @disabled($products->isEmpty())>Lưu bảng tỷ lệ</button>
        @endcan
    </form>
</div>
@endsection
