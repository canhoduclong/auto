@extends('layouts.app')

@section('content')
<div class="container-fluid settings-page py-3 py-lg-4">
    <div class="settings-hero card border-0 mb-3">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
            <div>
                <h2 class="mb-1">Website Settings</h2>
                <p class="mb-0">Quản trị cấu hình thương hiệu, hình ảnh hiển thị và thông tin liên hệ từ một màn hình tập trung.</p>
            </div>
            <span class="badge text-bg-light px-3 py-2">Admin Panel</span>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    @if(session('deploy_output'))
        <div class="card border-0 shadow-sm mb-3 border-{{ session('deploy_status') === 'error' ? 'danger' : 'success' }}">
            <div class="card-header bg-{{ session('deploy_status') === 'error' ? 'danger' : 'success' }} bg-opacity-10 border-0">
                <strong>
                    <i class="bi {{ session('deploy_status') === 'error' ? 'bi-exclamation-triangle' : 'bi-terminal' }} me-1"></i>
                    Deploy Notification
                </strong>
            </div>
            <div class="card-body">
                <pre class="settings-deploy-log mb-0">{{ session('deploy_output') }}</pre>
            </div>
        </div>
    @endif

    @if(session('push_output'))
        <div class="card border-0 shadow-sm mb-3 border-{{ session('push_status') === 'error' ? 'danger' : 'success' }}">
            <div class="card-header bg-{{ session('push_status') === 'error' ? 'danger' : 'success' }} bg-opacity-10 border-0">
                <strong>
                    <i class="bi {{ session('push_status') === 'error' ? 'bi-exclamation-triangle' : 'bi-git' }} me-1"></i>
                    Push Notification
                </strong>
            </div>
            <div class="card-body">
                <pre class="settings-deploy-log mb-0">{{ session('push_output') }}</pre>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div>
                <h5 class="mb-1">Deploy hệ thống</h5>
                <p class="text-muted small mb-0">Pull code mới nhất từ branch hoanglong và chạy các bước migrate/cache.</p>
            </div>
            <form method="POST" action="{{ route('admin.settings.deploy') }}" class="d-inline" onsubmit="return confirm('Xác nhận deploy code mới nhất?');">
                @csrf
                <input type="hidden" name="key" value="huy2024">
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="bi bi-cloud-arrow-down me-1"></i>Deploy
                </button>
            </form>
        </div>
        <div class="card-body pt-2">
            <small class="text-muted">Kết quả deploy sẽ hiển thị ngay tại khối Deploy Notification phía trên.</small>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 pb-0">
            <h5 class="mb-1">Push code lên GitHub</h5>
            <p class="text-muted small mb-0">Commit message lấy từ ô nhập liệu bên dưới, source local: /var/www/auto.com.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.push') }}" onsubmit="return confirm('Xác nhận commit và push code lên GitHub?');">
                @csrf
                <input type="hidden" name="key" value="huy2024">
                <div class="mb-2">
                    <label for="commit_message" class="form-label">Commit message</label>
                    <textarea id="commit_message" name="commit_message" class="form-control" rows="3" placeholder="Nhập nội dung commit..." required>{{ old('commit_message') }}</textarea>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <button type="submit" class="btn btn-dark btn-sm">
                        <i class="bi bi-git me-1"></i>Commit & Push
                    </button>
                    <small class="text-muted">Kết quả push sẽ hiển thị tại khối Push Notification phía trên.</small>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 pb-0">
            <h5 class="mb-1">Lịch sử Push gần đây</h5>
            <p class="text-muted small mb-0">Theo dõi các lần thay đổi code local đã đẩy lên GitHub.</p>
        </div>
        <div class="card-body">
            @if(!empty($pushHistory))
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Branch</th>
                                <th>Commit</th>
                                <th>Trạng thái</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pushHistory as $log)
                                <tr>
                                    <td>{{ $log['time'] ?? '-' }}</td>
                                    <td>{{ $log['branch'] ?? '-' }}</td>
                                    <td>{{ $log['commit_message'] ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ ($log['status'] ?? '') === 'success' ? 'bg-success' : 'bg-danger' }}">
                                            {{ strtoupper($log['status'] ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td>{{ $log['user'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted small">Chưa có lịch sử push nào.</div>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settings-form">
        @csrf

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="mb-1">Thông tin thương hiệu</h5>
                <p class="text-muted small mb-0">Các thông tin văn bản cơ bản hiển thị ở trang chủ và footer.</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <label for="brand_name" class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="brand_name" name="brand_name" value="{{ $settings['brand_name']->value ?? '' }}" placeholder="VD: Auto.com">
                    </div>
                    <div class="col-lg-6">
                        <label for="slogan" class="form-label">Slogan</label>
                        <input type="text" class="form-control" id="slogan" name="slogan" value="{{ $settings['slogan']->value ?? '' }}" placeholder="VD: Chất lượng tạo niềm tin">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Policy Page URL</label>
                        <input type="text" class="form-control" id="policy_page" name="policy_page" value="{{ $settings['policy_page']->value ?? '' }}" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="mb-1">Hình ảnh thương hiệu</h5>
                <p class="text-muted small mb-0">Chọn ảnh từ thư viện media. Có thể thay đổi bất kỳ lúc nào.</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-xl-4 col-md-6">
                        @php $logoMedia = isset($settings['logo']) ? App\Models\Media::find($settings['logo']->value) : null; @endphp
                        <div class="media-field">
                            <div class="media-field__label">Logo</div>
                            <div class="media-preview" id="logo-preview">
                                @if($logoMedia)
                                    <img src="{{ asset('storage/' . $logoMedia->file_path) }}" class="media-preview__img" alt="Logo">
                                @else
                                    <span class="media-preview__empty">Chưa chọn ảnh</span>
                                @endif
                            </div>
                            <input type="hidden" name="logo" id="logo-media-id" value="{{ $settings['logo']->value ?? '' }}">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btnSelectLogo">Chọn ảnh</button>
                                <button type="button" class="btn btn-light btn-sm border" data-clear-target="logo">Bỏ chọn</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        @php $bannerMedia = isset($settings['banner']) ? App\Models\Media::find($settings['banner']->value) : null; @endphp
                        <div class="media-field">
                            <div class="media-field__label">Banner</div>
                            <div class="media-preview" id="banner-preview">
                                @if($bannerMedia)
                                    <img src="{{ asset('storage/' . $bannerMedia->file_path) }}" class="media-preview__img" alt="Banner">
                                @else
                                    <span class="media-preview__empty">Chưa chọn ảnh</span>
                                @endif
                            </div>
                            <input type="hidden" name="banner" id="banner-media-id" value="{{ $settings['banner']->value ?? '' }}">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btnSelectBanner">Chọn ảnh</button>
                                <button type="button" class="btn btn-light btn-sm border" data-clear-target="banner">Bỏ chọn</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        @php $footerLogoMedia = isset($settings['footer_logo']) ? App\Models\Media::find($settings['footer_logo']->value) : null; @endphp
                        <div class="media-field">
                            <div class="media-field__label">Footer Logo</div>
                            <div class="media-preview" id="footer-logo-preview">
                                @if($footerLogoMedia)
                                    <img src="{{ asset('storage/' . $footerLogoMedia->file_path) }}" class="media-preview__img" alt="Footer Logo">
                                @else
                                    <span class="media-preview__empty">Chưa chọn ảnh</span>
                                @endif
                            </div>
                            <input type="hidden" name="footer_logo" id="footer-logo-media-id" value="{{ $settings['footer_logo']->value ?? '' }}">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btnSelectFooterLogo">Chọn ảnh</button>
                                <button type="button" class="btn btn-light btn-sm border" data-clear-target="footer-logo">Bỏ chọn</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="mb-1">Thông tin liên hệ doanh nghiệp</h5>
                <p class="text-muted small mb-0">Thông tin hiển thị tại footer, hóa đơn hoặc trang liên hệ.</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3">{{ $settings['address']->value ?? '' }}</textarea>
                    </div>
                    <div class="col-lg-4">
                        <label for="hotline" class="form-label">Hotline</label>
                        <input type="text" class="form-control" id="hotline" name="hotline" value="{{ $settings['hotline']->value ?? '' }}">
                    </div>
                    <div class="col-lg-4">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $settings['email']->value ?? '' }}">
                    </div>
                    <div class="col-lg-4">
                        <label for="tax_number" class="form-label">Tax Number</label>
                        <input type="text" class="form-control" id="tax_number" name="tax_number" value="{{ $settings['tax_number']->value ?? '' }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h5 class="mb-1">Slider trang chủ</h5>
                <p class="text-muted small mb-0">Nên dùng ảnh cùng tỉ lệ để hiển thị đẹp và đồng nhất.</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @for ($i = 1; $i <= 5; $i++)
                        @php $sliderMedia = isset($settings['slider_' . $i]) ? App\Models\Media::find($settings['slider_' . $i]->value) : null; @endphp
                        <div class="col-xl-4 col-md-6">
                            <div class="media-field">
                                <div class="media-field__label">Slider Image {{ $i }}</div>
                                <div class="media-preview" id="slider_{{ $i }}-preview">
                                    @if($sliderMedia)
                                        <img src="{{ asset('storage/' . $sliderMedia->file_path) }}" class="media-preview__img" alt="Slider {{ $i }}">
                                    @else
                                        <span class="media-preview__empty">Chưa chọn ảnh</span>
                                    @endif
                                </div>
                                <input type="hidden" name="slider_{{ $i }}" id="slider_{{ $i }}-media-id" value="{{ $settings['slider_' . $i]->value ?? '' }}">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" id="btnSelectSlider{{ $i }}">Chọn ảnh</button>
                                    <button type="button" class="btn btn-light btn-sm border" data-clear-target="slider_{{ $i }}">Bỏ chọn</button>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <div class="settings-actions card border-0 shadow-sm">
            <div class="card-body d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
                <p class="mb-0 text-muted small">Lưu ý: Thay đổi sẽ áp dụng ngay sau khi bấm lưu.</p>
                <button type="submit" class="btn btn-primary px-4">Save Settings</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.settings-page {
    background: linear-gradient(180deg, #f4f7fb 0%, #eef3f9 100%);
}

.settings-hero {
    background: linear-gradient(125deg, #163d63 0%, #0f7f77 100%);
    color: #f8fbff;
}

.settings-hero p {
    color: #d8e8f7;
}

.media-field {
    border: 1px solid #d8e2ee;
    border-radius: 12px;
    padding: 12px;
    background: #fff;
}

.media-field__label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #516176;
    font-weight: 700;
    margin-bottom: 8px;
}

.media-preview {
    width: 100%;
    min-height: 145px;
    border: 1px dashed #cfd9e6;
    background: #f8fbff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 10px;
}

.media-preview__img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    display: block;
}

.media-preview__empty {
    font-size: 13px;
    color: #7a8ea8;
}

.settings-actions {
    position: sticky;
    bottom: 12px;
    z-index: 10;
}

.settings-deploy-log {
    white-space: pre-wrap;
    word-break: break-word;
    background: #0f172a;
    color: #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    max-height: 420px;
    overflow: auto;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var currentTarget = null;
    var popupUrl = '{{ route('media.library.popup') }}';

    function ensureMediaModal() {
        var existing = document.getElementById('settingsMediaModal');
        if (existing) {
            return existing;
        }

        var modalHtml = `
            <div class="modal fade" id="settingsMediaModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 1180px;">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <h5 class="modal-title">Thư viện hình ảnh</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0" style="height: min(82vh, 860px);">
                            <iframe id="settingsMediaIframe" src="about:blank" frameborder="0" style="width:100%; height:100%;"></iframe>
                        </div>
                    </div>
                </div>
            </div>`;

        var wrapper = document.createElement('div');
        wrapper.innerHTML = modalHtml;
        document.body.appendChild(wrapper.firstElementChild);

        return document.getElementById('settingsMediaModal');
    }

    function openMediaModal(previewId, inputId) {
        currentTarget = { previewId: previewId, inputId: inputId };

        var modalEl = ensureMediaModal();
        var iframe = document.getElementById('settingsMediaIframe');
        iframe.src = popupUrl + '?picker=settings&t=' + Date.now();

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    window.addEventListener('message', function(event) {
        if (!event.data || !event.data.type) {
            return;
        }

        if (event.data.type === 'mediaSelected' && currentTarget) {
            var input = document.getElementById(currentTarget.inputId);
            var preview = document.getElementById(currentTarget.previewId);

            if (input) {
                input.value = event.data.mediaId;
            }

            if (preview) {
                preview.innerHTML = `<img src="${event.data.url}" class="media-preview__img">`;
            }

            var modalEl = document.getElementById('settingsMediaModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
        }

        if (event.data.type === 'closeMediaPopup') {
            var modalEl = document.getElementById('settingsMediaModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
        }
    });

    function setupMediaSelector(buttonId, previewId, inputId) {
        var btn = document.getElementById(buttonId);
        if (!btn) {
            return;
        }

        btn.addEventListener('click', function() {
            openMediaModal(previewId, inputId);
        });
    }

    setupMediaSelector('btnSelectLogo', 'logo-preview', 'logo-media-id');
    setupMediaSelector('btnSelectBanner', 'banner-preview', 'banner-media-id');
    setupMediaSelector('btnSelectFooterLogo', 'footer-logo-preview', 'footer-logo-media-id');
    @for ($i = 1; $i <= 5; $i++)
        setupMediaSelector('btnSelectSlider{{ $i }}', 'slider_{{ $i }}-preview', 'slider_{{ $i }}-media-id');
    @endfor

    var clearMap = {
        'logo': { previewId: 'logo-preview', inputId: 'logo-media-id' },
        'banner': { previewId: 'banner-preview', inputId: 'banner-media-id' },
        'footer-logo': { previewId: 'footer-logo-preview', inputId: 'footer-logo-media-id' },
        @for ($i = 1; $i <= 5; $i++)
        'slider_{{ $i }}': { previewId: 'slider_{{ $i }}-preview', inputId: 'slider_{{ $i }}-media-id' },
        @endfor
    };

    function clearMediaField(targetKey) {
        var target = clearMap[targetKey];
        if (!target) {
            return;
        }

        var input = document.getElementById(target.inputId);
        var preview = document.getElementById(target.previewId);

        if (input) {
            input.value = '';
        }

        if (preview) {
            preview.innerHTML = '<span class="media-preview__empty">Chưa chọn ảnh</span>';
        }
    }

    document.querySelectorAll('[data-clear-target]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            clearMediaField(btn.getAttribute('data-clear-target'));
        });
    });
});
</script>
@endpush