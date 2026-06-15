@extends('layouts.site')

@section('title', 'Đơn nháp')

@push('styles')
<style>
    .drafts-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.18), transparent 32%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 38%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .drafts-shell {
        max-width: 1180px;
        margin: 0 auto;
    }
    .drafts-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 9px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 28px;
        box-shadow: 0 22px 60px rgba(21, 34, 56, 0.18);
        overflow: hidden;
        position: relative;
    }
    .drafts-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        top: -60px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .drafts-kpi {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 9px;
        padding: 18px;
        min-height: 100%;
        backdrop-filter: blur(6px);
    }
    .drafts-kpi-label {
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.68);
        margin-bottom: 8px;
    }
    .drafts-kpi-value {
        font-size: 1.7rem;
        font-weight: 800;
        line-height: 1;
    }
    .drafts-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 9px;
        background: #f0f1ee;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
    }
    .drafts-form {
        padding: 24px;
    }
    .drafts-form .form-control,
    .drafts-form .form-select {
        height: 48px;
        border-radius: 9px;
        border-color: #d8deea;
    }
    .drafts-form .btn {
        height: 48px;
        border-radius: 9px;
        font-weight: 700;
    }
    .drafts-side-panel {
        position: sticky;
        top: 84px;
    }
    .drafts-side-head {
        padding: 22px 22px 12px;
        border-bottom: 1px solid #eef2f7;
    }
    .drafts-side-body {
        padding: 14px 22px 22px;
    }
    .draft-card {
        border: 1px solid #e5eaf3;
        border-radius: 12px;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        margin-bottom: 16px;
        transition: all 0.2s ease;
    }
    .draft-card:hover {
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.1);
        border-color: #d8deea;
    }
    .draft-card-head {
        padding: 18px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .draft-card-body {
        padding: 16px 24px;
    }
    .draft-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    .draft-info-group {
        border: 1px solid #e8edf5;
        border-radius: 8px;
        padding: 12px;
        background: #f8fafc;
    }
    .draft-info-label {
        font-size: .78rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: 6px;
        display: block;
    }
    .draft-info-value {
        font-size: .95rem;
        color: #0f172a;
        font-weight: 600;
    }
    .draft-item-box {
        height: 60px;
    }
    .draft-item-box:last-child {
        margin-bottom: 0;
    }
    .draft-actions {
        display: flex;
        gap: 8px;
        padding-top: 16px;
        border-top: 1px solid #eef2f7;
    }
    .draft-actions .btn {
        flex: 1;
        font-weight: 700;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.88rem;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
    }
    .status-draft { background: #fff7ed; color: #c2410c; }
    .status-confirmed { background: #ecfdf5; color: #047857; }
    .status-error { background: #fef2f2; color: #b91c1c; }
    
    @media (max-width: 991.98px) {
        .drafts-hero { padding: 22px; border-radius: 24px; }
        .drafts-side-panel { position: static; }
        .drafts-form { padding: 20px; }
    }
    @media (max-width: 767.98px) {
        .drafts-page { padding: 12px 0 48px; }
        .drafts-shell { padding: 0 10px; }
        .drafts-hero { padding: 14px; border-radius: 16px; }
        .drafts-hero h1 { font-size: 1.5rem !important; }
        .drafts-kpi-value { font-size: 1.15rem; }
        .drafts-kpi { padding: 10px 12px; border-radius: 14px; }
        .drafts-form { padding: 12px; }
        .draft-info-grid { grid-template-columns: 1fr; gap: 8px; }
        .draft-info-group { padding: 8px; }
        .draft-actions .btn { font-size: 0.75rem; padding: 6px 8px; }
    }
</style>
@endpush

@section('content')
@php
    $pageDrafts = $drafts;
    $draftCount = $pageDrafts->count();
@endphp

<section class="drafts-page">
    <div class="container drafts-shell">
        <div class="drafts-hero mb-4">
            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-5">
                    <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:.12em;color:rgba(255,255,255,.65);">Draft Center</div>
                    <h1 class="mb-3" style="font-size:2rem;font-weight:900;line-height:1.15;">Đơn nháp của bạn</h1>
                    <p class="mb-0" style="color:rgba(255,255,255,.8);max-width:520px;">
                        Dán nội dung Zalo để tạo bản nháp, kiểm tra và xác nhận thành đơn chính.
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="drafts-kpi">
                                <div class="drafts-kpi-label">Tổng nháp</div>
                                <div class="drafts-kpi-value">{{ $draftCount }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="drafts-kpi">
                                <div class="drafts-kpi-label">Chưa xác nhận</div>
                                <div class="drafts-kpi-value">{{ $pageDrafts->where('status', '!=', 'confirmed')->count() }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="drafts-kpi">
                                <div class="drafts-kpi-label">Đã tạo đơn</div>
                                <div class="drafts-kpi-value">{{ $pageDrafts->where('status', 'confirmed')->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left: Parse Form -->
            <div class="col-xl-4">
                <div class="drafts-panel drafts-side-panel">
                    <div class="drafts-side-head">
                        <h5 class="mb-0" style="font-weight:900;">📱 Nhập từ Zalo</h5>
                    </div>
                    <div class="drafts-side-body">
                        <form method="POST" action="{{ $parseRoute ?? route('pages.my_order_drafts.parse') }}" class="d-flex flex-column gap-3">
                            @csrf
                            <div>
                                <label class="form-label fw-bold" style="font-size:0.9rem;">Nội dung copy từ Zalo</label>
                                <textarea name="text" rows="12" class="form-control" required placeholder="[14/06/2026 10:45:10] Tên Zalo sale.&#10;KH: Lò Quay....&#10;SĐT: 0919622559&#10;ĐC: .123 Nguyễn Trãi,...&#10;Giờ giao: 4:00 &#10;SL: 10....&#10;Yêu cầu: .......&#10;Size: 2.6 ...&#10;Ghi chú: .....&#10;Giá: 58.000đ/kg...&#10;Gửi nhà xe: Trúc Phương&#10;Địa chỉ nhà xe: Số 107 Đường số 4, ....">{{ old('text') }}</textarea>
                                <small class="d-block mt-2 text-muted">Tin nhắn ghi chú sẽ nối vào đơn gần nhất.</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-magic me-2"></i>Phân tích thành nháp
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: Drafts Listing -->
            <div class="col-xl-8">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                        {{ session('success') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="drafts-panel">
                    <div class="drafts-form d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Bản nháp gần nhất</h5>
                            <small class="text-muted">Quản lý và xác nhận các đơn nháp</small>
                        </div>
                        @if($draftCount > 0)
                            <button type="button" class="btn btn-success" id="bulk-confirm">
                                <i class="bi bi-check-circle me-2"></i>Xác nhận đã chọn
                            </button>
                        @endif
                    </div>

                    <div style="padding: 0 24px 24px;">
                        @if($draftCount === 0)
                            <div style="text-align: center; padding: 60px 40px; color: #64748b;">
                                <div style="font-size: 3rem; margin-bottom: 16px;">📋</div>
                                <p style="margin: 0; font-size: 0.95rem;">Chưa có bản nháp nào. Dán nội dung Zalo ở bên trái để bắt đầu.</p>
                            </div>
                        @else
                            <div style="display: flex; gap: 8px; margin-bottom: 16px; padding: 12px 0; border-bottom: 1px solid #eef2f7;">
                                <input type="checkbox" id="check-all" style="width: 20px; height: 20px; cursor: pointer;">
                                <label for="check-all" style="flex: 1; cursor: pointer; padding-top: 2px;">Chọn tất cả</label>
                            </div>

                            @foreach($drafts as $draft)
                                <div class="draft-card" data-draft-id="{{ $draft->id }}">
                                    <!-- Header -->
                                    <div class="draft-card-head">
                                        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                            <input type="checkbox" class="draft-check" value="{{ $draft->id }}" style="width: 20px; height: 20px; cursor: pointer;" @disabled($draft->status === 'confirmed')>
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a;">{{ $draft->customer_name ?: '(Không có tên)' }}</div>
                                                <small style="color: #64748b;">{{ $draft->phone ?: '' }}</small>
                                            </div>
                                        </div>
                                        <span class="status-badge status-{{ $draft->status === 'confirmed' ? 'confirmed' : ($draft->status === 'error' ? 'error' : 'draft') }}">
                                            @if($draft->status === 'confirmed')
                                                ✓ Đã tạo
                                            @elseif($draft->status === 'error')
                                                ✗ Lỗi
                                            @else
                                                ◐ Nháp
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Body -->
                                    <div class="draft-card-body">
                                        <input type="hidden" name="sale_id" value="{{ auth()->id() }}">

                                        <!-- Customer Info Section -->
                                        <div class="draft-info-grid">
                                            <div class="draft-info-group">
                                                <span class="draft-info-label">Tên khách</span>
                                                <input name="customer_name" value="{{ $draft->customer_name }}" class="form-control form-control-sm" placeholder="Tên khách hàng">
                                            </div>
                                            <div class="draft-info-group">
                                                <span class="draft-info-label">Điện thoại</span>
                                                <input name="phone" value="{{ $draft->phone }}" class="form-control form-control-sm" placeholder="SĐT">
                                            </div>
                                            <div class="draft-info-group">
                                                <span class="draft-info-label">Địa chỉ</span>
                                                <input name="address" value="{{ $draft->address }}" class="form-control form-control-sm" placeholder="Địa chỉ giao">
                                            </div>
                                        </div>

                                        <!-- Customer Matching -->
                                        @if($draft->customer)
                                            <div style="padding: 8px 12px; border-radius: 8px; background: #ecfdf5; border: 1px solid #d1fae5; margin-bottom: 12px;">
                                                <small style="color: #047857;">✓ Khớp khách: <strong>{{ $draft->customer->name }}</strong></small>
                                            </div>
                                        @else
                                            <div style="padding: 8px 12px; border-radius: 8px; background: #fff7ed; border: 1px solid #fed7aa; margin-bottom: 12px;">
                                                <small style="color: #c2410c;">⚠️ Sẽ tạo khách mới</small>
                                            </div>
                                        @endif

                                        <!-- Transport Section -->
                                        <div style="border-top: 1px dashed #e2e8f0; padding-top: 12px; margin-bottom: 12px;">
                                            <div style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 8px;">🚚 Vận chuyển</div>
                                            <input type="hidden" name="truck_brand_id" value="{{ $draft->truck_brand_id }}">
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                <input type="hidden" name="truck_brand_id" value="{{ $draft->truck_brand_id }}">
                                                <input name="truck_brand_name" value="{{ $draft->truck_brand_name }}" class="form-control form-control-sm" placeholder="Tên nhà xe">
                                                <select name="truck_station_id" class="form-select form-select-sm">
                                                    <option value="">Chọn trạm</option>
                                                    @foreach($truckStations as $station)
                                                        <option
                                                            value="{{ $station->id }}"
                                                            data-brand-id="{{ $station->brand_id }}"
                                                            data-brand-name="{{ $station->brand?->name }}"
                                                            data-address="{{ $station->address }}"
                                                            @selected((int) $draft->truck_station_id === $station->id)>
                                                            {{ $station->brand?->name ? $station->brand->name.' · ' : '' }}{{ $station->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <input name="truck_station_address" value="{{ $draft->truck_station_address }}" class="form-control form-control-sm mt-2" placeholder="Địa chỉ trạm xe">
                                        </div>

                                        <!-- Products Section -->
                                        <div style="border-top: 1px dashed #e2e8f0; padding-top: 12px; margin-bottom: 12px;">
                                            <div style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 8px;">📦 Sản phẩm</div>
                                            @foreach(($draft->parsed_items ?: [['product_text' => $draft->product_text, 'product_variant_id' => $draft->product_variant_id, 'quantity' => $draft->quantity, 'size_kg' => $draft->size_kg, 'unit_price' => $draft->unit_price]]) as $itemIndex => $item)
                                                <div class="draft-item-box" data-item-index="{{ $itemIndex }}">
                                                    <input type="hidden" name="item_product_text" value="{{ $item['product_text'] ?? '' }}">
                                                    <small style="color: #64748b; display: block; margin-bottom: 6px;">{{ $item['product_text'] ?? '❌ Không đọc được' }}</small>
                                                    <select name="item_product_variant_id" class="form-select form-select-sm mb-2 {{ empty($item['product_variant_id']) ? 'is-invalid' : '' }}">
                                                        <option value="">Chọn biến thể</option>
                                                        @foreach($variants as $variant)
                                                            <option
                                                                value="{{ $variant->id }}"
                                                                data-effective-kg="{{ $variant->effective_kg }}"
                                                                @selected((int)($item['product_variant_id'] ?? 0) === $variant->id)>
                                                                {{ $variant->product?->name }} · {{ $variant->name ?: $variant->sku }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <div style="display: flex; width:100%; gap: 8px; margin-bottom: 12px;">
                                                        <div>
                                                            <small style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">Số lượng</small>
                                                            <input type="number" name="item_quantity" value="{{ $item['quantity'] ?? '' }}" min="1" class="form-control form-control-sm" placeholder="SL">
                                                        </div>
                                                        <div>
                                                            <small style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">Size</small>
                                                            <input type="number" step=".001" name="item_size_kg" value="{{ $item['size_kg'] ?? '' }}" class="form-control form-control-sm" placeholder="Kg">
                                                        </div>

                                                        <div>
                                                            <small style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">Giá bán</small>
                                                            <input type="number" step="1" name="item_unit_price" value="{{ $item['unit_price'] ?? '' }}" class="form-control form-control-sm" placeholder="Giá">
                                                        </div>
                                                    </div>
                                                </div> 
                                            @endforeach 
                                        </div>

                                        <!-- Delivery Info -->
                                        <div style="border-top: 1px dashed #e2e8f0; padding-top: 12px; margin-bottom: 12px;">
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                <div>
                                                    <small style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">Ngày giao</small>
                                                    <input type="date" name="delivery_date" value="{{ optional($draft->delivery_date)->toDateString() }}" class="form-control form-control-sm">
                                                </div>
                                                <div>
                                                    <small style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">Giờ giao</small>
                                                    <input name="delivery_time" value="{{ $draft->delivery_time }}" class="form-control form-control-sm" placeholder="VD: 14:00">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notes -->
                                        <div style="border-top: 1px dashed #e2e8f0; padding-top: 12px; margin-bottom: 0;">
                                            <small style="display: block; font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">Ghi chú</small>
                                            <textarea name="note" rows="2" class="form-control form-control-sm" style="font-size: 0.85rem;">{{ $draft->note }}</textarea>
                                        </div>

                                        @if($draft->status === 'error')
                                            <div style="padding: 8px 12px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; margin-top: 12px;">
                                                <small style="color: #b91c1c;">❌ {{ $draft->error_message }}</small>
                                            </div>
                                        @elseif($draft->status === 'confirmed')
                                            <div style="padding: 8px 12px; border-radius: 8px; background: #ecfdf5; border: 1px solid #d1fae5; margin-top: 12px;">
                                                <small style="color: #047857;">✓ Đơn: <strong>{{ $draft->order?->code }}</strong></small>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Actions -->
                                    <div class="draft-actions">
                                        <button type="button" class="btn btn-primary btn-sm js-confirm-draft" @disabled($draft->status === 'confirmed')>
                                            <i class="bi bi-check-circle me-1"></i>Xác nhận
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm js-copy-draft">
                                            <i class="bi bi-files me-1"></i>Sao chép
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm js-delete-draft">
                                            <i class="bi bi-trash me-1"></i>Xóa
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const fields = ['sale_id','customer_name','phone','address','truck_brand_id','truck_station_id','truck_brand_name','truck_station_address','delivery_date','delivery_time','note'];
    const notify = (message, error = false) => {
        const alert = document.createElement('div');
        alert.className = `alert ${error ? 'alert-danger' : 'alert-success'} alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow`;
        alert.style.zIndex = 2000; 
        alert.innerHTML = `${message}<button class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    };
    
    const cardData = card => {
        const data = Object.fromEntries(fields.map(name => [name, card.querySelector(`[name="${name}"]`)?.value || null]));
        data.items = [...card.querySelectorAll('.draft-item-box')].map(item => ({
            product_text: item.querySelector('[name="item_product_text"]').value || null,
            product_variant_id: item.querySelector('[name="item_product_variant_id"]').value || null,
            quantity: item.querySelector('[name="item_quantity"]').value || null,
            size_kg: item.querySelector('[name="item_size_kg"]').value || null,
            unit_price: item.querySelector('[name="item_unit_price"]').value || null,
        }));
        return data;
    };
    
    const requestAction = async (card, suffix, method = 'POST') => {
        const response = await fetch(`{{ $actionBaseUrl ?? url('my-order-drafts') }}/${card.dataset.draftId}${suffix}`, {
            method,
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
            body: method === 'DELETE' ? null : JSON.stringify(cardData(card))
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Không thể thực hiện thao tác');
        return payload;
    };
    
    async function confirmCard(card) {
        const button = card.querySelector('.js-confirm-draft');
        const customerName = card.querySelector('[name="customer_name"]')?.value || 'Không xác định';
        
        if (!confirm(`Xác nhận tạo đơn:\n\nKhách hàng: ${customerName}`)) {
            return;
        }
        
        button.disabled = true;
        let payload;
        try {
            payload = await requestAction(card, '/confirm');
        } catch (error) {
            button.disabled = false;
            throw error;
        }
        card.querySelector('.draft-check').disabled = true;
        const deliveryDate = card.querySelector('[name="delivery_date"]');
        if (deliveryDate) deliveryDate.value = payload.delivery_date;
        
        const statusBadge = card.querySelector('.status-badge');
        statusBadge.className = 'status-badge status-confirmed';
        statusBadge.innerHTML = '✓ Đã tạo';
        
        notify(payload.message);
    }
    
    document.addEventListener('click', async event => {
        const button = event.target.closest('.js-confirm-draft, .js-copy-draft, .js-delete-draft');
        if (!button) return;
        const card = button.closest('.draft-card');

        if (button.classList.contains('js-delete-draft')) {
            if (!confirm('Xóa dòng import này? Đơn đã tạo (nếu có) sẽ không bị ảnh hưởng.')) return;
            button.disabled = true;
            try {
                const payload = await requestAction(card, '', 'DELETE');
                card.remove();
                notify(payload.message);
            } catch (error) {
                button.disabled = false;
                notify(error.message, true);
            }
            return;
        }

        if (button.classList.contains('js-confirm-draft')) {
            try { await confirmCard(card); } catch (error) { notify(error.message, true); }
            return;
        }

        button.disabled = true;
        try {
            const payload = await requestAction(card, '/copy');
            card.after(card.cloneNode(true));
            notify(payload.message);
        } catch (error) {
            notify(error.message, true);
        } finally {
            button.disabled = false;
        }
    });
    
    document.addEventListener('change', event => {
        const select = event.target.closest('[name="item_product_variant_id"]');
        if (select) {
            const item = select.closest('.draft-item-box');
            const sizeInput = item?.querySelector('[name="item_size_kg"]');
            const effectiveKg = select.selectedOptions[0]?.dataset.effectiveKg;
            if (sizeInput && effectiveKg) sizeInput.value = effectiveKg;
            return;
        }

        const stationSelect = event.target.closest('[name="truck_station_id"]');
        if (!stationSelect) return;
        const card = stationSelect.closest('.draft-card');
        const option = stationSelect.selectedOptions[0];
        card.querySelector('[name="truck_brand_id"]').value = option?.dataset.brandId || '';
        if (option?.dataset.brandName) card.querySelector('[name="truck_brand_name"]').value = option.dataset.brandName;
        if (option?.dataset.address) card.querySelector('[name="truck_station_address"]').value = option.dataset.address;
    });
    
    document.getElementById('check-all')?.addEventListener('change', event => 
        document.querySelectorAll('.draft-check:not(:disabled)').forEach(input => input.checked = event.target.checked)
    );
    
    document.getElementById('bulk-confirm')?.addEventListener('click', async () => {
        const cards = [...document.querySelectorAll('.draft-check:checked')].map(input => input.closest('.draft-card'));
        if (!cards.length) return notify('Chưa chọn đơn nháp.', true);
        for (const card of cards) {
            try { await confirmCard(card); } catch (error) { notify(`Lỗi: ${error.message}`, true); }
        }
    });
});
</script>
@endpush
@endsection
