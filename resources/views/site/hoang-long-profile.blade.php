@extends('layouts.site')

@section('title', 'Hoàng Long TNT Profile')

@push('styles')
<style>
    .hlt-profile {
        background: linear-gradient(180deg, #f8fafc 0%, #eef4f3 100%);
        padding: 28px 0 44px;
        min-height: 60vh;
    }
    .hlt-profile-shell {
        max-width: 1040px;
    }
    .hlt-profile-hero {
        border: 1px solid #dbe4ea;
        border-radius: 8px;
        background: #fff;
        padding: 24px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .hlt-profile-title {
        color: #0f172a;
        font-size: 1.85rem;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .hlt-profile-body {
        color: #334155;
        font-size: 15px;
        line-height: 1.75;
        white-space: pre-line;
    }
    .hlt-doc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px;
    }
    .hlt-doc-link {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 72px;
        padding: 14px;
        border: 1px solid #dbe4ea;
        border-radius: 8px;
        background: #fff;
        color: #0f172a;
        text-decoration: none;
    }
    .hlt-doc-link:hover {
        color: #0f766e;
        border-color: #0f766e;
    }
    .hlt-doc-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: #ccfbf1;
        color: #0f766e;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: 18px;
    }
</style>
@endpush

@section('content')
<main class="hlt-profile">
    <div class="container hlt-profile-shell">
        <section class="hlt-profile-hero mb-3">
            <div class="text-muted small text-uppercase fw-semibold mb-1">Company Profile</div>
            <h1 class="hlt-profile-title">Hoàng Long TNT Profile</h1>
            @if(filled($profileInfo))
                <div class="hlt-profile-body">{{ $profileInfo }}</div>
            @else
                <div class="text-muted">Thông tin profile đang được cập nhật.</div>
            @endif
        </section>

        <section class="mt-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="mb-0">Tài liệu đính kèm</h5>
            </div>
            @if(count($documents) > 0)
                <div class="hlt-doc-grid">
                    @foreach($documents as $document)
                        @php
                            $href = $document['url'] ?: $document['file_url'];
                        @endphp
                        @if($href)
                            <a href="{{ $href }}" class="hlt-doc-link" target="_blank" rel="noopener">
                                <span class="hlt-doc-icon"><i class="bi bi-file-earmark-text"></i></span>
                                <span class="fw-semibold">{{ $document['title'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="hlt-profile-hero text-muted">Chưa có tài liệu đính kèm.</div>
            @endif
        </section>
    </div>
</main>
@endsection
