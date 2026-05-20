@extends('layouts.ceo')

@section('title', 'Cập nhật giá')
@section('subtitle', 'Thiết lập giá theo ngày')

@section('content')
<div class="content">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-3">
            <div>
                @if($product->avatar && $product->avatar->media)
                    <img
                        src="{{ asset('storage/' . $product->avatar->media->file_path) }}"
                        alt="{{ $product->name }}"
                        width="64"
                        height="64"
                        class="rounded object-fit-cover"
                    >
                @else
                    <div class="border rounded d-flex align-items-center justify-content-center text-muted" style="width: 64px; height: 64px;">-</div>
                @endif
            </div>
            <div>
            <h4 class="mb-1">Cập nhật giá theo ngày: {{ $product->name }}</h4>
            <p class="text-muted mb-0">
                Giá hiện tại:
                @if((float) $currentPriceMin === (float) $currentPriceMax)
                    {{ number_format((float) $currentPriceMin, 0, ',', '.') }} đ
                @else
                    {{ number_format((float) $currentPriceMin, 0, ',', '.') }} đ -
                    {{ number_format((float) $currentPriceMax, 0, ',', '.') }} đ
                @endif
            </p>
            </div>
        </div>
        <a href="{{ route('ceo.price-management.index') }}" class="btn btn-light">Quay lại danh sách giá</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">Form cập nhật giá sản phẩm</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('ceo.price-management.update', $product) }}" method="POST" class="row g-3">
                @csrf

                <div class="col-md-3">
                    <label class="form-label">Giá mới</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price') }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Giá Min</label>
                    <input type="number" step="0.01" min="0" name="min_price" class="form-control" value="{{ old('min_price', 0) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ngày áp dụng</label>
                    <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', now()->toDateString()) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Lý do thay đổi</label>
                    <input type="text" name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" placeholder="Ví dụ: Điều chỉnh theo bảng giá mới">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Áp dụng cho toàn bộ biến thể</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Lịch sử cập nhật giá</h6>
            <form method="GET" action="{{ route('ceo.price-management.show', $product) }}" class="row g-2 mb-0">
                <div class="col-auto">
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" placeholder="Từ ngày">
                </div>
                <div class="col-auto">
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" placeholder="Đến ngày">
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Lọc</button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('ceo.price-management.show', $product) }}" class="btn btn-light">Đặt lại</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Biến thể</th>
                            <th>Giá cũ</th>
                            <th>Giá mới</th>
                            <th>Người cập nhật</th>
                            <th>Thời gian</th>
                            <th>Lý do</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($priceHistory as $log)
                            <tr>
                                <td>{{ $log->variant->name ?? ('#' . $log->product_variant_id) }}</td>
                                <td>{{ number_format((float) $log->old_price, 0, ',', '.') }} đ</td>
                                <td>{{ number_format((float) $log->new_price, 0, ',', '.') }} đ</td>
                                <td>{{ $log->appliedBy->name ?? 'N/A' }}</td>
                                <td>{{ optional($log->applied_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $log->priceRule->reason ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Chưa có dữ liệu lịch sử giá.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($priceHistory->hasPages())
            <div class="card-footer">
                {{ $priceHistory->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
