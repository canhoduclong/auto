@extends(accounting_layout())

@section('title', 'Tạo tài sản / HĐ')

@section('accounting_content')
<a class="small d-inline-block mb-3" href="{{ accounting_route('assets.index') }}">← Quản lý tài sản</a>
<div class="row g-3">
    <div class="col-md-6">
        <section class="card rounded-3 h-100">
            <div class="card-body">
                <h2 class="h5">Tạo tài sản</h2>
                <p class="text-muted">Đăng ký thiết bị, khoản đầu tư hoặc chi phí và đính kèm chứng từ nguồn.</p>
                <a class="btn btn-primary" href="{{ accounting_route('assets.create', ['kind' => 'assets']) }}">Tạo tài sản</a>
            </div>
        </section>
    </div>
    <div class="col-md-6">
        <section class="card rounded-3 h-100">
            <div class="card-body">
                <h2 class="h5">Tạo hợp đồng</h2>
                <p class="text-muted">Lập hồ sơ theo đối tác và lưu bản hợp đồng, báo giá hoặc chứng từ liên quan.</p>
                <a class="btn btn-primary" href="{{ accounting_route('assets.create', ['kind' => 'contracts']) }}">Tạo hợp đồng</a>
            </div>
        </section>
    </div>
</div>
@endsection
