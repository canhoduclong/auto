@extends('layouts.site')
@section('title', 'Quy định nghiệp vụ nội bộ')

@push('styles')
<style>
.policy-page{--policy-primary:#0f766e;--policy-dark:#0f2f35;--policy-line:#dbe5e7;background:linear-gradient(180deg,#edf5f6 0,#f8fafc 340px);padding:42px 0 64px;color:#1e293b}.policy-shell{max-width:1120px}.policy-hero{position:relative;overflow:hidden;padding:32px 36px;border-radius:20px 20px 0 0;background:linear-gradient(130deg,#0d3d43,#115e59);color:#fff;box-shadow:0 22px 55px rgba(15,47,53,.15)}.policy-hero:after{content:"";position:absolute;right:-55px;top:-85px;width:260px;height:260px;border:55px solid rgba(255,255,255,.06);border-radius:50%}.policy-brand{position:relative;z-index:1;display:flex;align-items:center;gap:14px;margin-bottom:30px}.policy-brand__logo{display:grid;place-items:center;width:58px;height:58px;padding:7px;border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.14)}.policy-brand__logo img{max-width:100%;max-height:100%;object-fit:contain}.policy-brand__fallback{font-size:20px;font-weight:900;color:var(--policy-primary)}.policy-brand__name{font-size:17px;font-weight:800;letter-spacing:.03em}.policy-brand__caption{margin-top:2px;color:#a7f3d0;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.policy-hero__eyebrow{position:relative;z-index:1;margin-bottom:8px;color:#99f6e4;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.policy-hero h1{position:relative;z-index:1;max-width:780px;margin:0;font-size:clamp(27px,4vw,40px);font-weight:900;line-height:1.16}.policy-hero__meta{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:10px;margin-top:20px}.policy-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 11px;border:1px solid rgba(255,255,255,.2);border-radius:999px;background:rgba(255,255,255,.1);font-size:12px}.policy-chip.is-active{background:#dcfce7;color:#166534;border-color:#bbf7d0}.policy-chip.is-paused{background:#e2e8f0;color:#475569;border-color:#cbd5e1}.policy-layout{display:grid;grid-template-columns:255px minmax(0,1fr);gap:0;background:#fff;border:1px solid var(--policy-line);border-top:0;border-radius:0 0 20px 20px;box-shadow:0 22px 55px rgba(15,47,53,.09)}.policy-aside{padding:30px 24px;border-right:1px solid var(--policy-line);background:#f8fbfb;border-radius:0 0 0 20px}.policy-aside__inner{position:sticky;top:20px}.policy-aside__title{margin-bottom:14px;color:#64748b;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.policy-fact{display:flex;gap:10px;padding:12px 0;border-bottom:1px solid #e7eef0}.policy-fact i{color:var(--policy-primary);font-size:17px}.policy-fact small{display:block;color:#64748b;font-size:11px}.policy-fact strong{display:block;margin-top:2px;color:#0f172a;font-size:13px;overflow-wrap:anywhere}.policy-note{margin-top:20px;padding:13px;border-radius:11px;background:#e6fffb;color:#115e59;font-size:12px;line-height:1.55}.policy-document{padding:38px 44px 44px;min-width:0}.policy-document__intro{margin:0 0 28px;padding-bottom:24px;border-bottom:1px solid var(--policy-line);color:#475569;font-size:15px;line-height:1.7}.policy-document__content h2{display:flex;align-items:center;gap:10px;margin:32px 0 14px;color:var(--policy-dark);font-size:19px;font-weight:850;line-height:1.35}.policy-document__content h2:first-child{margin-top:0}.policy-document__content h2:before{content:"";flex:0 0 5px;width:5px;height:22px;border-radius:5px;background:#14b8a6}.policy-document__content h3{margin:25px 0 12px;color:var(--policy-dark);font-size:16px;font-weight:800}.policy-document__content p{color:#475569;line-height:1.75}.policy-document__content ul,.policy-document__content ol{margin:0;padding-left:24px;color:#334155;line-height:1.72}.policy-document__content li{padding-left:5px}.policy-document__content li+li{margin-top:9px}.policy-document__content li::marker{color:var(--policy-primary);font-weight:800}.policy-confirm{margin-top:36px;padding:22px;border:1px solid #b8d9d6;border-radius:14px;background:#f0fdfa}.policy-confirm__heading{display:flex;align-items:center;gap:10px;margin-bottom:8px;color:#134e4a;font-size:17px;font-weight:800}.policy-consent{display:flex;align-items:flex-start;gap:11px;margin:18px 0 0;padding:14px;border:1px solid #b8d9d6;border-radius:10px;background:#fff;cursor:pointer}.policy-consent input{flex:0 0 auto;width:19px;height:19px;margin-top:2px;accent-color:var(--policy-primary)}.policy-submit{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:16px}.policy-submit small{max-width:440px;color:#64748b;line-height:1.45}.policy-accepted{margin-top:34px;padding:20px;border:1px solid #bbf7d0;border-radius:14px;background:#f0fdf4;color:#166534}.policy-accepted i{font-size:22px}.policy-paused{margin-top:34px;padding:18px;border:1px solid #cbd5e1;border-radius:14px;background:#f8fafc;color:#475569}@media(max-width:767px){.policy-page{padding:18px 10px 34px}.policy-hero{padding:22px 20px;border-radius:15px 15px 0 0}.policy-brand{margin-bottom:22px}.policy-brand__logo{width:48px;height:48px}.policy-layout{display:block;border-radius:0 0 15px 15px}.policy-aside{padding:17px 20px;border-right:0;border-bottom:1px solid var(--policy-line);border-radius:0}.policy-aside__inner{position:static}.policy-facts{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}.policy-note{display:none}.policy-document{padding:25px 20px 28px}.policy-document__content h2{font-size:17px}.policy-submit{align-items:stretch;flex-direction:column}.policy-submit .btn{width:100%}.policy-confirm{padding:17px}.policy-chip{font-size:11px}}
</style>
@endpush

@section('content')
@php
    $brandName = $settings['brand_name']->value ?? 'Hoàng Long TNT';
    $user = auth()->user();
@endphp
<main class="policy-page">
    <div class="container policy-shell">
        <header class="policy-hero">
            <div class="policy-brand">
                <span class="policy-brand__logo">
                    @if($logoMedia)<img src="{{ asset('storage/'.$logoMedia->file_path) }}" alt="{{ $brandName }}">@else<span class="policy-brand__fallback">HL</span>@endif
                </span>
                <span><span class="policy-brand__name">{{ $brandName }}</span><span class="policy-brand__caption d-block">Quy định nội bộ doanh nghiệp</span></span>
            </div>
            <div class="policy-hero__eyebrow">Tài liệu áp dụng nội bộ</div>
            <h1>{{ $policyTitle }}</h1>
            <div class="policy-hero__meta">
                <span class="policy-chip"><i class="bi bi-file-earmark-check"></i>Phiên bản {{ $policyVersion }}</span>
                <span class="policy-chip {{ $policyEnabled ? 'is-active' : 'is-paused' }}"><i class="bi {{ $policyEnabled ? 'bi-check-circle' : 'bi-pause-circle' }}"></i>{{ $policyEnabled ? 'Đang áp dụng' : 'Tạm ngừng xác nhận' }}</span>
            </div>
        </header>

        <div class="policy-layout">
            <aside class="policy-aside">
                <div class="policy-aside__inner">
                    <div class="policy-aside__title">Thông tin xác nhận</div>
                    <div class="policy-facts">
                        <div class="policy-fact"><i class="bi bi-person"></i><div><small>Người đọc</small><strong>{{ $user?->name }}</strong></div></div>
                        <div class="policy-fact"><i class="bi bi-shield-check"></i><div><small>Trạng thái</small><strong>{{ $accepted ? 'Đã xác nhận' : 'Chưa xác nhận' }}</strong></div></div>
                        <div class="policy-fact"><i class="bi bi-file-text"></i><div><small>Phiên bản</small><strong>{{ $policyVersion }}</strong></div></div>
                        <div class="policy-fact"><i class="bi bi-building"></i><div><small>Đơn vị ban hành</small><strong>{{ $brandName }}</strong></div></div>
                    </div>
                    <div class="policy-note"><i class="bi bi-info-circle me-1"></i>Vui lòng đọc toàn bộ nội dung trước khi xác nhận. Hệ thống lưu tài khoản, phiên bản, thời điểm và địa chỉ IP.</div>
                </div>
            </aside>

            <article class="policy-document">
                <p class="policy-document__intro">Tài liệu quy định trách nhiệm, quyền hạn và nguyên tắc phối hợp của người dùng khi xử lý khách hàng, đơn hàng, thuế VAT, dữ liệu bán hàng và kết quả công việc trên hệ thống.</p>
                <div class="policy-document__content">{!! $policyContent !!}</div>

                @if(!$policyEnabled)
                    <div class="policy-paused"><i class="bi bi-info-circle me-2"></i>CEO đang tạm ngừng yêu cầu xác nhận tài liệu này.</div>
                @elseif(!$accepted)
                    <form method="POST" action="{{ route('sales-tax-policy.accept') }}" class="policy-confirm">@csrf
                        <div class="policy-confirm__heading"><i class="bi bi-pen"></i>Xác nhận cam kết của người dùng</div>
                        <div class="text-muted small">Vui lòng xác nhận riêng từng nội dung dưới đây cho phiên bản đang hiển thị.</div>
                        @include('site.partials.sales-tax-policy-commitments')
                        <div class="policy-submit"><small>Sau khi xác nhận, bạn có thể tiếp tục sử dụng các chức năng nghiệp vụ của tài khoản.</small><button class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>Xác nhận và tiếp tục</button></div>
                    </form>
                @else
                    <div class="policy-accepted d-flex gap-3 align-items-center"><i class="bi bi-patch-check-fill"></i><div><strong class="d-block">Bạn đã xác nhận phiên bản hiện hành</strong><span class="small">Tài khoản được phép tiếp tục thực hiện các hoạt động nghiệp vụ.</span></div></div>
                @endif
            </article>
        </div>
    </div>
</main>
@endsection
