@extends(accounting_layout())

@section('title', 'Quản Trị Danh Mục Giao Dịch')
@section('subtitle', 'Khai báo danh mục và xác định chiều tiền vào/ra tài khoản')

@section('accounting_content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="acc-card">
            <div class="card-body">
                <div class="fw-bold mb-3"><i class="bi bi-plus-circle me-1 text-primary"></i>Thêm danh mục</div>
                <form method="POST" action="{{ accounting_route('transaction-categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Mã</label>
                        <input type="text" name="code" class="form-control" maxlength="20" required placeholder="VD: TTM">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tên danh mục</label>
                        <input type="text" name="name" class="form-control" maxlength="100" required placeholder="VD: Thu tiền mặt">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chiều tiền</label>
                        <select name="flow_direction" class="form-select" required>
                            <option value="in">Thu vào tài khoản</option>
                            <option value="out">Chi từ tài khoản</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Lưu danh mục</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="acc-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã</th>
                                <th>Tên danh mục</th>
                                <th>Chiều tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($categories as $cat)
                            <tr>
                                <td><span class="badge bg-primary">{{ $cat->code }}</span></td>
                                <td class="fw-semibold">{{ $cat->name }}</td>
                                <td>
                                    @if($cat->flow_direction === 'in')
                                        <span class="badge bg-success">Thu vào tài khoản</span>
                                    @else
                                        <span class="badge bg-danger">Chi từ tài khoản</span>
                                    @endif
                                </td>
                                <td>
                                    @if($cat->is_active)
                                        <span class="badge bg-success">Đang dùng</span>
                                    @else
                                        <span class="badge bg-secondary">Ngừng dùng</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#editCat{{ $cat->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ accounting_route('transaction-categories.toggle-active', $cat) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $cat->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                            <i class="bi {{ $cat->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <div class="modal fade" id="editCat{{ $cat->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form class="modal-content" method="POST" action="{{ accounting_route('transaction-categories.update', $cat) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Sửa danh mục {{ $cat->code }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Mã</label>
                                                <input type="text" name="code" class="form-control" value="{{ $cat->code }}" maxlength="20" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tên danh mục</label>
                                                <input type="text" name="name" class="form-control" value="{{ $cat->name }}" maxlength="100" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Chiều tiền</label>
                                                <select name="flow_direction" class="form-select" required>
                                                    <option value="in" {{ $cat->flow_direction === 'in' ? 'selected' : '' }}>Thu vào tài khoản</option>
                                                    <option value="out" {{ $cat->flow_direction === 'out' ? 'selected' : '' }}>Chi từ tài khoản</option>
                                                </select>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act{{ $cat->id }}" {{ $cat->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="act{{ $cat->id }}">Đang dùng</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                            <button type="submit" class="btn btn-primary">Lưu</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Chưa có danh mục giao dịch.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
