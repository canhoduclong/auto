@extends('layouts.site')

@section('content')
<div class="p-5 mb-4 bg-light rounded-3">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold">Welcome to our website!</h1>
        <p class="col-md-8 fs-4">This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>
    </div>
</div>

<section class="container my-5">
    <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <img src="https://static.sapo.vn/app/uploads/2023/03/linh-vuc-kinh-doanh-sapo.png" alt="Thiết kế chuyên biệt cho từng lĩnh vực kinh doanh" class="img-fluid rounded shadow">
        </div>
        <div class="col-lg-6">
            <h2 class="fw-bold mb-3 text-primary">Thiết kế chuyên biệt cho từng lĩnh vực kinh doanh</h2>
            <p class="fs-5 mb-3">Sapo cung cấp giải pháp website tối ưu cho từng ngành nghề: bán lẻ, thời trang, mỹ phẩm, thực phẩm, điện máy, nội thất, nhà hàng, dịch vụ... Giao diện hiện đại, chuẩn SEO, dễ dàng tùy biến và tích hợp nhiều tính năng hỗ trợ kinh doanh hiệu quả.</p>
            <ul class="list-unstyled mb-4">
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Giao diện đẹp, đa dạng ngành nghề</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Tối ưu trải nghiệm khách hàng</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Chuẩn SEO, dễ lên top Google</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Tích hợp bán hàng đa kênh</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Hỗ trợ kỹ thuật tận tâm</li>
            </ul>
            <a href="https://www.sapo.vn/thiet-ke-website" target="_blank" class="btn btn-primary btn-lg px-4">Khám phá mẫu website Sapo</a>
        </div>
    </div>
</section>

@endsection
