@extends('layouts.admin')
@section('title', 'Quản lý đơn hàng mẫu')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between mb-3"><h3>Đơn hàng mẫu #{{ $draft->id }}</h3><a href="{{ route('admin.order-templates.index') }}" class="btn btn-light">Danh sách mẫu</a></div>
    <p>Sale: <strong>{{ $draft->sale?->name ?? 'Không còn tồn tại' }}</strong> · Khách: <strong>{{ $draft->customer?->name ?: $draft->customer_name ?: 'Chưa có khách' }}</strong></p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card"><div class="card-body">
        <h5>Nội dung mẫu</h5>
        <p class="small text-muted">Sửa số lượng, size, giá tham chiếu, ngày/giờ giao và ghi chú. Khi lên đơn tự động, hệ thống lấy lại giá hiện hành. Các đơn đã tạo giữ nguyên.</p>
        <form method="POST" action="{{ route('admin.order-templates.update', $draft) }}">@csrf @method('PUT')
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Sản phẩm / biến thể</th><th>Số lượng</th><th>Size (kg)</th><th>Giá tham chiếu</th></tr></thead><tbody>
                @foreach(old('items', $items) as $index => $item)
                    @php($variant = $variants->get($item['product_variant_id'] ?? 0))
                    <tr><td>{{ $variant?->product?->name ?: $item['product_text'] ?? 'Sản phẩm chưa hợp lệ' }} · {{ $variant?->name ?: $variant?->sku }}
                        <input type="hidden" name="items[{{ $index }}][product_variant_id]" value="{{ $item['product_variant_id'] ?? '' }}">
                        <input type="hidden" name="items[{{ $index }}][product_text]" value="{{ $item['product_text'] ?? '' }}">
                    </td><td><input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm" min="1" required value="{{ $item['quantity'] ?? 1 }}"></td>
                    <td><input type="number" name="items[{{ $index }}][size_kg]" class="form-control form-control-sm" min="0.01" step="0.001" value="{{ $item['size_kg'] ?? '' }}"></td>
                    <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control form-control-sm" min="0" step="0.01" value="{{ $item['unit_price'] ?? '' }}"></td></tr>
                @endforeach
            </tbody></table></div>
            <div class="row g-3"><div class="col-md-4"><label class="form-label" for="template-date">Ngày giao mặc định</label><input id="template-date" type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', $draft->delivery_date?->toDateString()) }}"></div>
                <div class="col-md-4"><label class="form-label" for="template-time">Giờ giao</label><input id="template-time" name="delivery_time" class="form-control" value="{{ old('delivery_time', $draft->delivery_time) }}" maxlength="255"></div>
                <div class="col-12"><label class="form-label" for="template-note">Ghi chú</label><textarea id="template-note" name="note" class="form-control" maxlength="10000">{{ old('note', $draft->note) }}</textarea></div>
            </div><button class="btn btn-primary mt-3">Lưu nội dung mẫu</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><h5>Lịch lên đơn tự động</h5>
        <p class="small text-muted">Tắt lịch chỉ dừng các lần tạo mới. Đơn và lịch đã sinh không bị hủy. Ngày đã chọn trong quá khứ chưa chạy có thể được hệ thống thực hiện bù.</p>
        @if($draft->automation_last_error)<div class="alert alert-warning">Lỗi gần nhất: {{ $draft->automation_last_error }}</div>@endif
        <form method="POST" action="{{ route('admin.order-templates.automation', $draft) }}">@csrf @method('PUT')
            <div class="row g-3"><div class="col-md-4"><label for="automation-enabled" class="form-label">Trạng thái</label><select id="automation-enabled" name="automation_enabled" class="form-select"><option value="0" @selected(!old('automation_enabled', $draft->automation_enabled))>Tắt</option><option value="1" @selected(old('automation_enabled', $draft->automation_enabled))>Bật</option></select></div>
                <div class="col-md-4"><label for="automation-mode" class="form-label">Chế độ</label><select id="automation-mode" name="automation_mode" class="form-select"><option value="daily" @selected(old('automation_mode', $draft->automation_mode) !== 'scheduled')>Hằng ngày</option><option value="scheduled" @selected(old('automation_mode', $draft->automation_mode) === 'scheduled')>Theo ngày chọn</option></select></div>
                <div class="col-12"><label for="automation-dates" class="form-label">Các ngày thực hiện (chế độ theo ngày chọn)</label><textarea id="automation-dates" name="automation_dates" class="form-control" rows="3" placeholder="2026-10-29&#10;2026-10-31">{{ is_array(old('automation_dates')) ? implode("\n", old('automation_dates')) : old('automation_dates', implode("\n", $draft->automation_dates ?? [])) }}</textarea><div class="form-text">Mỗi dòng một ngày theo định dạng YYYY-MM-DD. Mỗi mẫu chỉ tạo một đơn cho một ngày.</div></div>
            </div><button class="btn btn-primary mt-3">Lưu cấu hình lịch</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.order-templates.destroy', $draft) }}" onsubmit="return confirm('Xóa đơn mẫu này? Mẫu sẽ ngừng tạo đơn mới; các đơn và lịch đã tạo được giữ lại.');">@csrf @method('DELETE')<button class="btn btn-outline-danger">Xóa đơn mẫu</button></form></div></div>
</div>
@endsection
