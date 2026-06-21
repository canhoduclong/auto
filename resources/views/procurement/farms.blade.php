@extends('layouts.procurement')
@section('title', 'Danh sách trang trại')
@section('subtitle', 'Thông tin liên hệ, quy mô, chu kỳ và đánh giá chất lượng')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">Tổng cộng <strong>{{ $farms->count() }}</strong> trang trại</div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFarmModal"><i class="bi bi-plus-circle me-1"></i>Thêm trang trại</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Trang trại</th><th>Liên hệ</th><th>Quy mô</th><th>Loại vịt</th><th>Chu kỳ</th><th>Đánh giá</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
            @forelse($farms as $farm)
                <tr>
                    <td><div class="fw-semibold">{{ $farm->name }}</div><div class="small text-muted text-truncate" style="max-width:280px">{{ $farm->address ?: 'Chưa có địa chỉ' }}</div></td>
                    <td>{{ $farm->phone ?: '—' }}<div class="small text-muted">{{ ['individual'=>'Cá nhân','household'=>'Hộ kinh doanh','company'=>'Công ty','cooperative'=>'Hợp tác xã'][$farm->business_type] ?? $farm->business_type }}</div></td>
                    <td>{{ number_format($farm->scale ?? 0) }} con</td>
                    <td>{{ $farm->duck_breed ?: '—' }}</td>
                    <td>{{ $farm->raising_days }} ngày<div class="small text-muted">Lần bắt: {{ $farm->last_purchase_at?->format('d/m/Y') ?? '—' }}</div></td>
                    <td><span class="text-warning">{{ str_repeat('★', (int) round($farm->rating)) }}</span><span class="small text-muted"> {{ number_format($farm->rating, 1) }}</span><div class="small text-muted">{{ $farm->reviews->count() }} nhận xét</div></td>
                    <td><span class="badge {{ $farm->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $farm->is_active ? 'Đang sử dụng' : 'Tạm ngưng' }}</span></td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-secondary js-view-farm" data-id="{{ $farm->id }}" title="Xem"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-sm btn-outline-primary js-edit-farm" data-id="{{ $farm->id }}" title="Sửa"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-warning js-review-farm" data-id="{{ $farm->id }}" title="Đánh giá"><i class="bi bi-star"></i></button>
                        <button class="btn btn-sm btn-outline-danger js-delete-farm" data-id="{{ $farm->id }}" title="Xóa"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5">Chưa có trang trại nào.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addFarmModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('procurement.farms.store') }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Thêm trang trại</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Tên trại *</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
        <div class="col-md-6"><label class="form-label">Điện thoại</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
        <div class="col-12"><label class="form-label">Địa chỉ</label><input name="address" class="form-control" value="{{ old('address') }}"></div>
        <div class="col-md-4"><label class="form-label">Quy mô (con)</label><input type="number" min="0" name="scale" class="form-control" value="{{ old('scale') }}"></div>
        <div class="col-md-4"><label class="form-label">Loại vịt nuôi</label><input name="duck_breed" class="form-control" value="{{ old('duck_breed') }}"></div>
        <div class="col-md-4"><label class="form-label">Số ngày nuôi</label><input type="number" name="raising_days" value="{{ old('raising_days', 45) }}" min="30" max="60" class="form-control" required></div>
        <div class="col-md-5"><label class="form-label">Loại hình</label><select name="business_type" class="form-select" required>@foreach(['individual'=>'Cá nhân','household'=>'Hộ kinh doanh','company'=>'Công ty','cooperative'=>'Hợp tác xã'] as $key=>$label)<option value="{{ $key }}" @selected(old('business_type') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-7"><label class="form-label">Ghi chú</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary">Thêm trang trại</button></div>
</form></div></div>

<div class="modal fade" id="viewFarmModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="viewFarmTitle">Thông tin trang trại</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="viewFarmBody"></div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Đóng</button></div>
</div></div></div>

<div class="modal fade" id="editFarmModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" class="modal-content" id="editFarmForm">@csrf @method('PUT')
    <div class="modal-header"><h5 class="modal-title">Sửa trang trại</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Tên trại *</label><input name="name" id="editName" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Điện thoại</label><input name="phone" id="editPhone" class="form-control"></div>
        <div class="col-12"><label class="form-label">Địa chỉ</label><input name="address" id="editAddress" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Quy mô (con)</label><input type="number" min="0" name="scale" id="editScale" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Loại vịt nuôi</label><input name="duck_breed" id="editBreed" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Số ngày nuôi</label><input type="number" min="30" max="60" name="raising_days" id="editDays" class="form-control" required></div>
        <div class="col-md-5"><label class="form-label">Loại hình</label><select name="business_type" id="editBusinessType" class="form-select">@foreach(['individual'=>'Cá nhân','household'=>'Hộ kinh doanh','company'=>'Công ty','cooperative'=>'Hợp tác xã'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-5"><label class="form-label">Ghi chú</label><textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea></div>
        <div class="col-md-2 d-flex align-items-end"><label class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" id="editActive" value="1" class="form-check-input"> Đang dùng</label></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary">Lưu thay đổi</button></div>
</form></div></div>

<div class="modal fade" id="reviewFarmModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content" id="reviewFarmForm">@csrf
    <div class="modal-header"><h5 class="modal-title">Đánh giá trang trại</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="fw-semibold mb-3" id="reviewFarmName"></div><label class="form-label">Số sao</label><select name="rating" class="form-select mb-3">@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }} sao</option>@endfor</select><label class="form-label">Nhận xét</label><textarea name="comment" class="form-control" rows="4" placeholder="Đánh giá sản phẩm hoặc trang trại"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button><button class="btn btn-warning">Gửi đánh giá</button></div>
</form></div></div>

<div class="modal fade" id="deleteFarmModal" tabindex="-1"><div class="modal-dialog"><form method="POST" class="modal-content" id="deleteFarmForm">@csrf @method('DELETE')
    <div class="modal-header"><h5 class="modal-title text-danger">Xóa trang trại</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">Bạn chắc chắn muốn xóa <strong id="deleteFarmName"></strong>? Nhật ký thu mua cũ vẫn được giữ lại nhưng sẽ không còn liên kết với trang trại này.</div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-danger">Xóa trang trại</button></div>
</form></div></div>
@endsection

@push('scripts')
<script>
(() => {
    const farms = @json($farms->mapWithKeys(fn($farm) => [$farm->id => [
        'id' => $farm->id, 'name' => $farm->name, 'phone' => $farm->phone, 'address' => $farm->address,
        'scale' => $farm->scale, 'duck_breed' => $farm->duck_breed, 'business_type' => $farm->business_type,
        'raising_days' => $farm->raising_days, 'last_purchase_at' => $farm->last_purchase_at?->format('d/m/Y'),
        'rating' => number_format($farm->rating, 1), 'notes' => $farm->notes, 'is_active' => $farm->is_active,
        'reviews' => $farm->reviews->take(5)->map(fn($review) => ['rating' => $review->rating, 'comment' => $review->comment, 'user' => $review->user?->name])->values(),
    ]]));
    const businessLabels = {individual:'Cá nhân', household:'Hộ kinh doanh', company:'Công ty', cooperative:'Hợp tác xã'};
    const escapeHtml = value => { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; };
    const route = (template, id) => template.replace('__FARM__', id);
    const updateRoute = @json(route('procurement.farms.update', '__FARM__'));
    const reviewRoute = @json(route('procurement.farms.reviews.store', '__FARM__'));
    const deleteRoute = @json(route('procurement.farms.destroy', '__FARM__'));
    document.querySelectorAll('.js-view-farm').forEach(button => button.addEventListener('click', () => {
        const farm = farms[button.dataset.id];
        document.getElementById('viewFarmTitle').textContent = farm.name;
        const reviews = farm.reviews.length ? farm.reviews.map(review => `<div class="border-top py-2"><span class="text-warning">${'★'.repeat(review.rating)}</span> ${escapeHtml(review.comment || '')}<span class="small text-muted"> — ${escapeHtml(review.user || '')}</span></div>`).join('') : '<div class="text-muted">Chưa có đánh giá.</div>';
        document.getElementById('viewFarmBody').innerHTML = `<div class="row g-3"><div class="col-md-6"><small class="text-muted">Điện thoại</small><div>${escapeHtml(farm.phone || '—')}</div></div><div class="col-md-6"><small class="text-muted">Loại hình</small><div>${escapeHtml(businessLabels[farm.business_type] || farm.business_type)}</div></div><div class="col-12"><small class="text-muted">Địa chỉ</small><div>${escapeHtml(farm.address || '—')}</div></div><div class="col-md-4"><small class="text-muted">Quy mô</small><div>${Number(farm.scale || 0).toLocaleString('vi-VN')} con</div></div><div class="col-md-4"><small class="text-muted">Loại vịt</small><div>${escapeHtml(farm.duck_breed || '—')}</div></div><div class="col-md-4"><small class="text-muted">Chu kỳ nuôi</small><div>${farm.raising_days} ngày</div></div><div class="col-md-6"><small class="text-muted">Lần bắt gần nhất</small><div>${farm.last_purchase_at || '—'}</div></div><div class="col-md-6"><small class="text-muted">Đánh giá</small><div class="text-warning">★ ${farm.rating}</div></div><div class="col-12"><small class="text-muted">Ghi chú</small><div>${escapeHtml(farm.notes || '—')}</div></div><div class="col-12"><h6 class="mt-2">Đánh giá gần đây</h6>${reviews}</div></div>`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewFarmModal')).show();
    }));
    document.querySelectorAll('.js-edit-farm').forEach(button => button.addEventListener('click', () => {
        const farm = farms[button.dataset.id];
        document.getElementById('editFarmForm').action = route(updateRoute, farm.id);
        document.getElementById('editName').value = farm.name || ''; document.getElementById('editPhone').value = farm.phone || '';
        document.getElementById('editAddress').value = farm.address || ''; document.getElementById('editScale').value = farm.scale || '';
        document.getElementById('editBreed').value = farm.duck_breed || ''; document.getElementById('editDays').value = farm.raising_days || 45;
        document.getElementById('editBusinessType').value = farm.business_type; document.getElementById('editNotes').value = farm.notes || '';
        document.getElementById('editActive').checked = !!farm.is_active;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editFarmModal')).show();
    }));
    document.querySelectorAll('.js-review-farm').forEach(button => button.addEventListener('click', () => {
        const farm = farms[button.dataset.id]; document.getElementById('reviewFarmName').textContent = farm.name;
        document.getElementById('reviewFarmForm').action = route(reviewRoute, farm.id);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewFarmModal')).show();
    }));
    document.querySelectorAll('.js-delete-farm').forEach(button => button.addEventListener('click', () => {
        const farm = farms[button.dataset.id]; document.getElementById('deleteFarmName').textContent = farm.name;
        document.getElementById('deleteFarmForm').action = route(deleteRoute, farm.id);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteFarmModal')).show();
    }));
    @if($errors->any()) bootstrap.Modal.getOrCreateInstance(document.getElementById('addFarmModal')).show(); @endif
})();
</script>
@endpush
