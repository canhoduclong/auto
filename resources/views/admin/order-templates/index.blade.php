@extends('layouts.admin')
@section('title', 'Đơn hàng mẫu toàn bộ sale')
@section('content')
<div class="container-fluid py-3">
    <h3>Đơn hàng mẫu toàn bộ sale</h3>
    <p class="text-muted">Quản lý mẫu riêng của tất cả sale, gồm mẫu tạo thủ công và mẫu tự động. Chỉnh sửa mẫu áp dụng cho các lần lên đơn tiếp theo.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="d-flex flex-wrap gap-3 mb-3"><span>Tổng: <strong>{{ $counts['all'] }}</strong> mẫu</span><span>Đang bật tự động: <strong>{{ $counts['enabled'] }}</strong></span><span>Có lỗi tự động: <strong>{{ $counts['errors'] }}</strong></span></div>
    <form method="GET" class="card card-body mb-3"><div class="row g-3 align-items-end">
        <div class="col-md-3"><label for="template-sale" class="form-label">Sale phụ trách</label><select id="template-sale" name="sale_id" class="form-select"><option value="">Toàn bộ sale</option>@foreach($sales as $sale)<option value="{{ $sale->id }}" @selected((string) request('sale_id') === (string) $sale->id)>{{ $sale->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="template-search" class="form-label">Tìm khách hàng</label><input id="template-search" name="search" class="form-control" maxlength="150" value="{{ request('search') }}" placeholder="Tên khách, mã KH, số điện thoại"></div>
        <div class="col-md-3"><label for="template-automation" class="form-label">Lên đơn tự động</label><select id="template-automation" name="automation" class="form-select"><option value="">Tất cả</option>@foreach(['on' => 'Đang bật', 'off' => 'Đang tắt', 'daily' => 'Hằng ngày', 'scheduled' => 'Theo ngày chọn', 'error' => 'Có lỗi'] as $value => $label)<option value="{{ $value }}" @selected(request('automation') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary">Lọc</button> <a href="{{ route('admin.order-templates.index') }}" class="btn btn-light">Xóa lọc</a></div>
    </div></form>
    <div class="card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Mẫu / Sale</th><th>Khách hàng</th><th>Sản phẩm</th><th>Lịch tự động</th><th>Kết quả gần nhất</th><th></th></tr></thead><tbody>
    @forelse($drafts as $draft)
        <tr>
            <td><strong>#{{ $draft->id }}</strong><div>{{ $draft->sale?->name ?? 'Sale không còn tồn tại' }}</div></td>
            <td>{{ $draft->customer?->name ?: $draft->customer_name ?: 'Chưa chọn khách' }}<div class="small text-muted">{{ $draft->customer?->customer_code }} · {{ $draft->customer?->phone ?: $draft->phone }}</div></td>
            <td>@if($draft->parsed_items){{ count($draft->parsed_items) }} dòng sản phẩm@else{{ $draft->variant?->product?->name ?: $draft->product_text ?: 'Chưa chọn sản phẩm' }} · SL {{ $draft->quantity }}@endif</td>
            <td><span class="badge {{ $draft->automation_enabled ? 'bg-success' : 'bg-secondary' }}">{{ $draft->automation_enabled ? 'Đang bật' : 'Đang tắt' }}</span><div class="small">{{ $draft->automation_mode === 'daily' ? 'Hằng ngày' : ($draft->automation_mode === 'scheduled' ? 'Theo ngày chọn' : 'Chưa có lịch') }}</div>@if($draft->automation_mode === 'scheduled')<div class="small text-muted">{{ count($draft->automation_dates ?? []) }} ngày</div>@endif</td>
            <td>@if($draft->automation_last_run_at)<div class="small">{{ $draft->automation_last_run_at->format('d/m/Y H:i') }}</div>@endif @if($draft->automation_last_error)<div class="small text-danger text-break">{{ $draft->automation_last_error }}</div>@elseif($draft->error_message)<div class="small text-danger text-break">{{ $draft->error_message }}</div>@else<span class="text-muted small">Không có lỗi</span>@endif @if($draft->order)<a href="{{ route('orders.show', $draft->order) }}" class="small">{{ $draft->order->code ?: '#'.$draft->order->id }}</a>@endif</td>
            <td><a href="{{ route('admin.order-templates.edit', $draft) }}" class="btn btn-sm btn-outline-primary">Xem / sửa / lịch</a></td>
        </tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-4">Không có đơn hàng mẫu phù hợp.</td></tr>@endforelse
    </tbody></table></div><div class="card-body">{{ $drafts->links() }}</div></div>
</div>
@endsection
