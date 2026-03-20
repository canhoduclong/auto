@extends('layouts.app', ['menu' => 'product'])

@section('content')
<div class="content">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">Quản lý giá sản phẩm</h4>
            <p class="text-muted mb-0">Danh sách sản phẩm và giá hiện tại theo biến thể.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('products.price-management.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="Tìm theo tên sản phẩm"
                        value="{{ request('name') }}"
                    >
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('products.price-management.index') }}" class="btn btn-light">Đặt lại</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Danh sách giá hiện tại</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Sản phẩm</th>
                            <th>Số biến thể</th>
                            <th>Giá hiện tại</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    @if($product->avatar && $product->avatar->media)
                                        <img
                                            src="{{ asset('storage/' . $product->avatar->media->file_path) }}"
                                            alt="{{ $product->name }}"
                                            width="44"
                                            height="44"
                                            class="rounded object-fit-cover"
                                        >
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $product->name }}</td>
                                <td>{{ number_format($product->variants->count()) }}</td>
                                <td>
                                    @if((float) $product->current_price_min === (float) $product->current_price_max)
                                        {{ number_format((float) $product->current_price_min, 0, ',', '.') }} đ
                                    @else
                                        {{ number_format((float) $product->current_price_min, 0, ',', '.') }} đ -
                                        {{ number_format((float) $product->current_price_max, 0, ',', '.') }} đ
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('products.price-management.show', $product) }}" class="btn btn-sm btn-primary">
                                        Cập nhật giá
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Không có sản phẩm phù hợp.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
