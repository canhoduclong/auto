@extends($prefix === 'ceo' ? 'layouts.ceo' : 'layouts.accounting')
@section('title','Thiết lập tính thuế')
@section('subtitle','Cấu hình mức thuế dùng chung cho sản phẩm và các biến thể')
@section('accounting_content')
<div class="container-fluid py-3">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card"><div class="card-body">
<h5>Thuế sản phẩm</h5><p class="text-muted">Để trống: chưa thiết lập. Nhập 0: mức thuế 0%. Mức thuế được dùng chung cho toàn bộ biến thể của sản phẩm.</p>
<form method="GET" class="d-flex gap-2 mb-3"><input name="search" value="{{ $search }}" class="form-control" placeholder="Tìm sản phẩm" aria-label="Tìm sản phẩm"><button class="btn btn-primary">Tìm</button></form>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Sản phẩm</th><th>Số biến thể</th><th>Mức thuế (%)</th></tr></thead><tbody>
@forelse($products as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->variants_count }}</td><td><form action="{{ route($prefix.'.tax-settings.products.update',$product) }}" method="POST" class="d-flex gap-2" style="min-width:190px">@csrf @method('PATCH')<input type="number" name="tax_percent" min="0" max="100" step="0.01" value="{{ $product->tax_percent }}" placeholder="Chưa thiết lập" aria-label="Thuế của {{ $product->name }}" class="form-control" style="max-width:150px"><button class="btn btn-primary">Lưu</button></form></td></tr>
@empty<tr><td colspan="3" class="text-center text-muted">Không có sản phẩm.</td></tr>@endforelse
</tbody></table></div>{{ $products->links() }}
<p class="small text-muted mb-0">Thiết lập này lưu mức thuế theo sản phẩm; không tự tính lại VAT của các đơn hàng đã tạo.</p>
</div></div></div>
@endsection
