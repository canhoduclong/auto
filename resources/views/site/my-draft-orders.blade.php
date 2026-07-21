@extends(($monitoringEmbedded ?? false) ? 'layouts.embedded' : 'layouts.site')

@section('title', 'Đơn hàng Mẫu')

@push('styles')
<style>
    .drafts-page { padding: 0 0 48px; background: transparent; }
    .drafts-shell { max-width: none; margin: 0; padding: 0; }
    .draft-template-title { padding: 15px 14px 10px; border-left: 7px solid #f8fafc; background: #fff; }
    .draft-template-title h1 { display: inline-block; margin: 0; padding-bottom: 6px; border-bottom: 3px solid #f59e0b; font-size: 1rem; font-weight: 900; text-transform: uppercase; }
    .draft-template-sort { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 18px 4px 12px; }
    .draft-template-sort-actions { display: flex; flex-wrap: wrap; gap: 6px; }
    .draft-template-sort .btn { border-radius: 4px; font-size: .7rem; }
    .draft-template-list { display: grid; gap: 16px; }
    .draft-template-row { display: grid; grid-template-columns: minmax(0, 1fr) 132px; gap: 14px; align-items: start; }
    .draft-template-card { min-width: 0; padding: 14px 16px; border: 1px solid #dce6f1; border-radius: 7px; background: #fff; box-shadow: 0 5px 16px rgba(15, 23, 42, .06); }
    .draft-template-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
    .draft-template-name { color: #0f172a; font-size: .82rem; font-weight: 900; text-transform: uppercase; }
    .draft-template-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 3px; color: #64748b; font-size: .68rem; }
    .draft-template-status { display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border-radius: 999px; background: #fff1f2; color: #be123c; font-size: .68rem; font-weight: 800; white-space: nowrap; }
    .draft-template-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .draft-template-status.is-confirmed { background: #ecfdf5; color: #047857; }
    .draft-template-status.is-error { background: #fef2f2; color: #b91c1c; }
    .draft-template-section { padding: 9px 0; border-bottom: 1px dashed #dce6f1; }
    .draft-template-section-title { margin-bottom: 6px; color: #334155; font-size: .67rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
    .draft-template-delivery { display: grid; gap: 4px; color: #64748b; font-size: .7rem; }
    .draft-template-products { width: 100%; margin: 0; font-size: .7rem; }
    .draft-template-products th { padding: 6px 4px; border-color: #dce6f1; color: #64748b; font-size: .61rem; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
    .draft-template-products td { padding: 7px 4px; border-color: #edf2f7; vertical-align: middle; }
    .draft-template-thumb { width: 34px; height: 34px; border-radius: 5px; border: 1px solid #e2e8f0; object-fit: cover; }
    .draft-template-product-name { color: #0f172a; font-weight: 800; }
    .draft-template-total { display: grid; grid-template-columns: 95px 105px; justify-content: end; gap: 8px; margin-top: 5px; padding-top: 7px; border-top: 1px solid #dce6f1; font-size: .8rem; font-weight: 900; text-align: right; }
    .draft-template-actions { display: grid; gap: 8px; }
    .draft-template-actions .btn { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; border-radius: 6px; font-size: .72rem; font-weight: 800; }
    .draft-template-editor { margin-top: 12px; padding-top: 12px; border-top: 1px solid #dce6f1; }
    .draft-template-editor-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .draft-template-editor-grid .is-wide { grid-column: 1 / -1; }
    .draft-template-editor label { margin-bottom: 3px; color: #64748b; font-size: .66rem; font-weight: 800; }
    .draft-template-edit-item { display: grid; grid-template-columns: minmax(0, 1fr) 72px 90px 105px; gap: 6px; margin-top: 7px; }
    .draft-template-empty { padding: 48px 20px; border: 1px solid #dce6f1; border-radius: 8px; background: #fff; color: #64748b; text-align: center; }
    @media (max-width: 767.98px) {
        .draft-template-row { grid-template-columns: 1fr; }
        .draft-template-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .draft-template-sort { align-items: flex-start; flex-direction: column; }
        .draft-template-products { min-width: 650px; }
        .draft-template-editor-grid { grid-template-columns: 1fr; }
        .draft-template-editor-grid .is-wide { grid-column: auto; }
        .draft-template-edit-item { grid-template-columns: 1fr 1fr; }
    }
</style>
@endpush

@section('content')
@php
    $variantMap = $variants->keyBy('id');
    $currentSortBy = $sortBy ?? 'created_at';
    $currentSortDir = $sortDir ?? 'desc';
    $sortDirFor = fn (string $field): string => $currentSortBy === $field && $currentSortDir === 'asc' ? 'desc' : 'asc';
    $sortIconFor = fn (string $field): string => $currentSortBy !== $field ? 'fa-sort' : ($currentSortDir === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc');
@endphp

<section class="drafts-page">
    <div class="container drafts-shell">
        <div class="draft-template-title"><h1>Đơn hàng Mẫu</h1></div>

        @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif

        <div class="draft-template-sort">
            <div class="d-flex align-items-center gap-2 small text-muted"><span class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></span><span>Sắp xếp nhanh:</span></div>
            <div class="draft-template-sort-actions">
                @foreach(['created_at' => 'Ngày tạo', 'unit_price' => 'Tổng tiền', 'customer_name' => 'Khách hàng', 'status' => 'Trạng thái'] as $field => $label)
                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => $field, 'sort_dir' => $sortDirFor($field)]) }}" class="btn btn-sm btn-outline-secondary">{{ $label }} <i class="fa {{ $sortIconFor($field) }}"></i></a>
                @endforeach
            </div>
        </div>

        <div class="draft-template-list">
            @forelse($drafts as $draft)
                @php
                    $draftItems = collect($draft->parsed_items ?: [[
                        'product_text' => $draft->product_text,
                        'product_variant_id' => $draft->product_variant_id,
                        'quantity' => $draft->quantity,
                        'size_kg' => $draft->size_kg,
                        'unit_price' => $draft->unit_price,
                    ]]);
                    $draftTotal = $draftItems->sum(function ($item) {
                        $quantity = max(0, (float) ($item['quantity'] ?? 0));
                        $weight = max(0.01, (float) ($item['size_kg'] ?? 1));
                        return $quantity * $weight * max(0, (float) ($item['unit_price'] ?? 0));
                    });
                    $statusText = $draft->status === 'confirmed' ? 'Đã lên đơn' : ($draft->status === 'error' ? 'Có lỗi' : 'Đơn mẫu');
                    $statusClass = $draft->status === 'confirmed' ? 'is-confirmed' : ($draft->status === 'error' ? 'is-error' : '');
                @endphp
                <article class="draft-template-row" data-draft-card data-draft-id="{{ $draft->id }}">
                    <div class="draft-template-card">
                        <div class="draft-template-head">
                            <div>
                                <div class="draft-template-name">{{ $draft->customer_name ?: ($draft->customer?->name ?: 'Chưa chọn khách hàng') }}</div>
                                <div class="draft-template-meta">
                                    <span>{{ $draft->created_at?->format('d/m/Y H:i') }}</span>
                                    @if($draft->phone)<span><i class="bi bi-telephone me-1"></i>{{ $draft->phone }}</span>@endif
                                    <span>MẪU-{{ $draft->id }}</span>
                                </div>
                            </div>
                            <span class="draft-template-status {{ $statusClass }}">{{ $statusText }}</span>
                        </div>

                        <div class="collapse show draft-template-details" id="draftDetails{{ $draft->id }}">
                            <div class="draft-template-section">
                                <div class="draft-template-section-title">Giao hàng</div>
                                <div class="draft-template-delivery">
                                    <span><i class="bi bi-geo-alt me-1"></i>Địa chỉ nhận hàng: {{ $draft->address ?: 'Chưa cập nhật' }}</span>
                                    <span><i class="bi bi-clock me-1"></i>Giờ giao: {{ $draft->delivery_time ?: 'Chưa cập nhật' }}</span>
                                </div>
                            </div>
                            <div class="draft-template-section border-bottom-0 pb-0">
                                <div class="draft-template-section-title">Danh sách sản phẩm</div>
                                <div class="table-responsive">
                                    <table class="table table-sm draft-template-products">
                                        <thead><tr><th>Ảnh</th><th>Sản phẩm</th><th class="text-end">SL</th><th class="text-end">Size</th><th class="text-end">Tổng</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
                                        <tbody>
                                            @foreach($draftItems as $item)
                                                @php
                                                    $itemVariant = $variantMap->get((int) ($item['product_variant_id'] ?? 0));
                                                    $quantity = max(0, (float) ($item['quantity'] ?? 0));
                                                    $weight = max(0.01, (float) ($item['size_kg'] ?? $itemVariant?->effective_kg ?? 1));
                                                    $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
                                                    $lineTotal = $quantity * $weight * $unitPrice;
                                                    $productName = $itemVariant?->product?->name ?? ($item['product_text'] ?? 'Sản phẩm');
                                                    $imagePath = $itemVariant?->product?->avatar?->media?->file_path;
                                                @endphp
                                                <tr>
                                                    <td>@if($imagePath)<img class="draft-template-thumb" src="{{ asset('storage/' . $imagePath) }}" alt="{{ $productName }}">@else<i class="bi bi-image text-muted"></i>@endif</td>
                                                    <td><span class="draft-template-product-name">{{ $productName }}</span>@if($itemVariant?->sku)<span class="d-block text-muted">{{ $itemVariant->sku }}</span>@endif</td>
                                                    <td class="text-end fw-bold">{{ number_format($quantity, 0, ',', '.') }}</td>
                                                    <td class="text-end">{{ $itemVariant?->size ?: number_format($weight, 2, '.', '') }}</td>
                                                    <td class="text-end fw-bold">{{ rtrim(rtrim(number_format($quantity * $weight, 3, ',', '.'), '0'), ',') }} kg</td>
                                                    <td class="text-end">{{ number_format($unitPrice, 0, ',', '.') }}đ</td>
                                                    <td class="text-end fw-bold">{{ number_format($lineTotal, 0, ',', '.') }}đ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="draft-template-total"><span>Tổng cộng:</span><strong>{{ number_format($draftTotal, 0, ',', '.') }}đ</strong></div>
                            </div>
                        </div>

                            <div class="collapse draft-template-editor" id="draftEdit{{ $draft->id }}">
                                <input type="hidden" name="sale_id" value="{{ auth()->id() }}">
                                <input type="hidden" name="customer_id" value="{{ $draft->customer_id }}">
                                <input type="hidden" name="truck_brand_id" value="{{ $draft->truck_brand_id }}">
                                <input type="hidden" name="truck_station_id" value="{{ $draft->truck_station_id }}">
                                <input type="hidden" name="truck_brand_name" value="{{ $draft->truck_brand_name }}">
                                <input type="hidden" name="truck_station_address" value="{{ $draft->truck_station_address }}">
                                <div class="draft-template-editor-grid">
                                    <div><label>Tên khách</label><input name="customer_name" class="form-control form-control-sm" value="{{ $draft->customer_name }}"></div>
                                    <div><label>Số điện thoại</label><input name="phone" class="form-control form-control-sm" value="{{ $draft->phone }}"></div>
                                    <div class="is-wide"><label>Địa chỉ</label><input name="address" class="form-control form-control-sm" value="{{ $draft->address }}"></div>
                                    <div><label>Giờ giao</label><input name="delivery_time" class="form-control form-control-sm" value="{{ $draft->delivery_time }}"></div>
                                    <div><label>Ghi chú</label><input name="note" class="form-control form-control-sm" value="{{ $draft->note }}"></div>
                                </div>
                                <div class="draft-template-section-title mt-3">Sản phẩm</div>
                                @foreach($draftItems as $itemIndex => $item)
                                    <div class="draft-template-edit-item" data-draft-item>
                                        <select name="item_product_variant_id" class="form-select form-select-sm" required>
                                            <option value="">Chọn biến thể</option>
                                            @foreach($variants as $variant)<option value="{{ $variant->id }}" @selected((int) ($item['product_variant_id'] ?? 0) === $variant->id)>{{ $variant->product?->name }} · {{ $variant->size ?: ($variant->name ?: $variant->sku) }}</option>@endforeach
                                        </select>
                                        <input type="number" name="item_quantity" class="form-control form-control-sm" min="1" value="{{ $item['quantity'] ?? 1 }}" placeholder="SL">
                                        <input type="number" name="item_size_kg" class="form-control form-control-sm" step=".001" min=".01" value="{{ $item['size_kg'] ?? 1 }}" placeholder="Kg">
                                        <input type="number" name="item_unit_price" class="form-control form-control-sm" min="0" value="{{ $item['unit_price'] ?? 0 }}" placeholder="Giá">
                                        <input type="hidden" name="item_product_text" value="{{ $item['product_text'] ?? '' }}">
                                    </div>
                                @endforeach
                                @if($draft->status !== 'confirmed')<div class="text-end mt-3"><button type="button" class="btn btn-sm btn-success js-save-draft"><i class="bi bi-check2 me-1"></i>Lưu thay đổi</button></div>@endif
                            </div>
                    </div>

                    <div class="draft-template-actions">
                        @if($draft->status !== 'confirmed')<button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#draftEdit{{ $draft->id }}"><i class="bi bi-pencil"></i>Sửa</button>@endif
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#draftDetails{{ $draft->id }}"><i class="bi bi-eye"></i>Chi tiết</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-copy-draft"><i class="bi bi-files"></i>Sao chép đơn</button>
                        @if($draft->status !== 'confirmed')<button type="button" class="btn btn-sm btn-success js-confirm-draft"><i class="bi bi-check2-circle"></i>Lên đơn</button>@endif
                    </div>
                </article>
            @empty
                <div class="draft-template-empty"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Chưa có đơn hàng mẫu.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const baseUrl = @json($actionBaseUrl);
    const notify = (message, type = 'success') => typeof window.showToast === 'function' ? window.showToast(message, type) : window.alert(message);
    const rowData = card => {
        const editor = card.querySelector('.draft-template-editor');
        const value = name => editor?.querySelector(`[name="${name}"]`)?.value || null;
        return {
            sale_id: value('sale_id'), customer_id: value('customer_id'), customer_name: value('customer_name'), phone: value('phone'), address: value('address'),
            truck_brand_id: value('truck_brand_id'), truck_station_id: value('truck_station_id'), truck_brand_name: value('truck_brand_name'), truck_station_address: value('truck_station_address'),
            delivery_time: value('delivery_time'), note: value('note'),
            items: Array.from(editor?.querySelectorAll('[data-draft-item]') || []).map(item => ({
                product_variant_id: item.querySelector('[name="item_product_variant_id"]')?.value || null,
                quantity: item.querySelector('[name="item_quantity"]')?.value || null,
                size_kg: item.querySelector('[name="item_size_kg"]')?.value || null,
                unit_price: item.querySelector('[name="item_unit_price"]')?.value || null,
                product_text: item.querySelector('[name="item_product_text"]')?.value || null,
            }))
        };
    };
    const request = async (card, suffix, method = 'POST') => {
        const response = await fetch(`${baseUrl}/${card.dataset.draftId}${suffix}`, {
            method,
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(rowData(card)),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Không thể thực hiện thao tác.');
        return payload;
    };
    document.addEventListener('click', async event => {
        const button = event.target.closest('.js-save-draft, .js-copy-draft, .js-confirm-draft');
        if (!button) return;
        const card = button.closest('[data-draft-card]');
        if (!card) return;
        if (button.classList.contains('js-confirm-draft') && !confirm('Lên đơn từ đơn mẫu này?')) return;
        button.disabled = true;
        try {
            const payload = button.classList.contains('js-save-draft')
                ? await request(card, '', 'PUT')
                : await request(card, button.classList.contains('js-copy-draft') ? '/copy' : '/confirm');
            notify(payload.message || 'Thao tác thành công.');
            window.location.reload();
        } catch (error) {
            notify(error.message, 'error');
            button.disabled = false;
        }
    });
});
</script>
@endpush
