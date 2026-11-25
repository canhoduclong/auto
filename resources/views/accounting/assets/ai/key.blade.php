@extends(accounting_layout())
@section('title', 'Khóa kết nối ChatGPT')
@section('accounting_content')
<section class="card">
    <div class="card-body">
        <h2 class="h5">Khóa kết nối vừa tạo</h2>
        <p>Sao chép khóa này vào phần xác thực của GPT Action theo kiểu <strong>API Key → Bearer</strong>. Khóa chỉ hiển thị một lần.</p>
        <label class="d-block mb-3"><span class="form-label">Khóa kết nối</span>
            <input class="form-control font-monospace" value="{{ $secret }}" readonly autocomplete="off">
        </label>
        <a class="btn btn-outline-primary" href="{{ accounting_route('assets.ai.index') }}">Về bản nháp</a>
    </div>
</section>
@endsection
