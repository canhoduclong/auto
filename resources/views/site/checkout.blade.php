@extends('layouts.site')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <h2>Thông tin đơn hàng</h2>
            <form action="{{ route('orders.store_from_cart') }}" method="POST">
                @csrf
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Thông tin người nhận</h5>
                        <div class="mb-3">
                            <label for="recipient_name" class="form-label">Họ tên người nhận</label>
                            <input type="text" class="form-control @error('recipient_name') is-invalid @enderror" 
                                id="recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name ?? '') }}" required>
                            @error('recipient_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="recipient_phone" class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control @error('recipient_phone') is-invalid @enderror" 
                                id="recipient_phone" name="recipient_phone" value="{{ old('recipient_phone') }}" required>
                            @error('recipient_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="recipient_address" class="form-label">Địa chỉ nhận hàng</label>
                            <textarea class="form-control @error('recipient_address') is-invalid @enderror" 
                                id="recipient_address" name="recipient_address" rows="3" required>{{ old('recipient_address') }}</textarea>
                            @error('recipient_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="note" name="note" rows="3">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Đơn hàng của bạn</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Đơn giá</th>
                                        <th>Số lượng</th>
                                        <th>Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $total = 0 @endphp
                                    @foreach(session('cart') as $id => $details)
                                        @php $total += $details['price'] * $details['quantity'] @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($details['image'])
                                                        <img src="{{ asset('storage/' . $details['image']) }}" 
                                                            alt="{{ $details['name'] }}" width="50" class="me-3">
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">{{ $details['name'] }}</h6>
                                                        <small>SKU: {{ $details['sku'] }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ number_format($details['price']) }}đ</td>
                                            <td>{{ $details['quantity'] }}</td>
                                            <td>{{ number_format($details['price'] * $details['quantity']) }}đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Tổng tiền:</strong></td>
                                        <td><strong>{{ number_format($total) }}đ</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="{{ route('cart.show') }}" class="btn btn-outline-secondary me-2">Quay lại giỏ hàng</a>
                    <button type="submit" class="btn btn-primary">Đặt hàng</button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tóm tắt đơn hàng</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tạm tính:</span>
                        <span>{{ number_format($total) }}đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Tổng cộng:</strong>
                        <strong>{{ number_format($total) }}đ</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection