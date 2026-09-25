@extends('layouts.admin')

@section('title', 'Quản lý Nhà cung cấp')

@push('styles')
<style>
.sup-header { background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); color: #fff; border-radius: 14px; padding: 22px 28px; margin-bottom: 24px; }
.sup-header h1 { font-size: 1.5rem; font-weight: 800; margin: 0; }
.sup-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 14px rgba(15,23,42,.07); overflow: hidden; margin-bottom: 24px; }
.sup-card .table { margin: 0; }
.sup-card thead { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.sup-card th { padding: 11px 14px; font-size: .73rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #475569; }
.sup-card td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.sup-card tbody tr:hover { background: #fafafa; }
</style>
@endpush

@section('content')
<div class="container-fluid">

<div class="sup-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-building me-2"></i>Nhà Cung Cấp</h1>
        <p class="mb-0 mt-1" style="opacity:.85;font-size:.88rem;">Quản lý danh sách nhà cung cấp hàng hoá nhập kho</p>
    </div>
    <button class="btn btn-light fw-700 btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="bi bi-plus-circle me-1"></i> Thêm mới
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show rounded-3">
    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filter --}}
<div class="sup-card mb-3">
    <div style="padding:14px 20px;">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm tên, SĐT…" value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active"   {{ $status === 'active'   ? 'selected' : '' }}>Đang hoạt động</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Ngưng hoạt động</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Lọc</button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary btn-sm ms-1"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
@if($suppliers->count())
<div class="sup-card">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Tên nhà cung cấp</th>
                <th>Số điện thoại</th>
                <th>Địa chỉ</th>
                <th>Ghi chú</th>
                <th class="text-center">Trạng thái</th>
                <th class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($suppliers as $sup)
            <tr>
                <td class="text-muted" style="font-size:.8rem;">{{ $sup->id }}</td>
                <td><span class="fw-700">{{ $sup->name }}</span></td>
                <td>{{ $sup->phone ?? '—' }}</td>
                <td><small class="text-muted">{{ Str::limit($sup->address, 40) ?? '—' }}</small></td>
                <td><small class="text-muted">{{ Str::limit($sup->notes, 35) ?? '—' }}</small></td>
                <td class="text-center">
                    @if($sup->is_active)
                        <span class="badge bg-success">Hoạt động</span>
                    @else
                        <span class="badge bg-secondary">Ngưng</span>
                    @endif
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-warning btn-edit-sup"
                            data-id="{{ $sup->id }}"
                            data-name="{{ $sup->name }}"
                            data-phone="{{ $sup->phone }}"
                            data-address="{{ $sup->address }}"
                            data-notes="{{ $sup->notes }}"
                            data-active="{{ $sup->is_active ? '1' : '0' }}"
                            data-url="{{ route('admin.suppliers.update', $sup) }}"
                            title="Chỉnh sửa">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <form action="{{ route('admin.suppliers.destroy', $sup) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Xóa nhà cung cấp {{ addslashes($sup->name) }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Xóa">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-center mt-3">{{ $suppliers->appends(request()->query())->links() }}</div>
@else
<div class="sup-card" style="padding:3rem;text-align:center;color:#94a3b8;">
    <i class="bi bi-building" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>
    <p class="mb-0">Chưa có nhà cung cấp nào.</p>
    <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#modalCreate">
        <i class="bi bi-plus-circle me-1"></i> Thêm nhà cung cấp đầu tiên
    </button>
</div>
@endif

</div>

{{-- Modal Thêm mới --}}
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;">
                <h5 class="modal-title fw-700"><i class="bi bi-plus-circle me-2"></i>Thêm Nhà Cung Cấp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.suppliers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Tên nhà cung cấp <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="200" placeholder="VD: Công ty TNHH ABC">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" maxlength="30" placeholder="0909 xxx xxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Địa chỉ</label>
                        <input type="text" name="address" class="form-control" maxlength="500" placeholder="Địa chỉ…">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createActive" checked>
                        <label class="form-check-label fw-600 small" for="createActive">Đang hoạt động</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Chỉnh sửa --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;">
                <h5 class="modal-title fw-700"><i class="bi bi-pencil-square me-2"></i>Chỉnh Sửa Nhà Cung Cấp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEdit" action="" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Tên nhà cung cấp <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control" required maxlength="200">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Số điện thoại</label>
                        <input type="text" name="phone" id="editPhone" class="form-control" maxlength="30">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Địa chỉ</label>
                        <input type="text" name="address" id="editAddress" class="form-control" maxlength="500">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600 small">Ghi chú</label>
                        <textarea name="notes" id="editNotes" class="form-control" rows="2" maxlength="2000"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive">
                        <label class="form-check-label fw-600 small" for="editActive">Đang hoạt động</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning btn-sm text-dark fw-700"><i class="bi bi-save me-1"></i>Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.btn-edit-sup').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('formEdit').action  = this.dataset.url;
        document.getElementById('editName').value   = this.dataset.name;
        document.getElementById('editPhone').value  = this.dataset.phone  || '';
        document.getElementById('editAddress').value= this.dataset.address|| '';
        document.getElementById('editNotes').value  = this.dataset.notes  || '';
        document.getElementById('editActive').checked = this.dataset.active === '1';
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    });
});
</script>
@endpush
@endsection
