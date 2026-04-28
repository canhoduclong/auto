@extends('layouts.site')

@push('styles')
<style>
    .apt-hero {
        border-radius: 16px;
        background: linear-gradient(135deg, #0f172a 0%, #0f766e 60%, #14b8a6 100%);
        color: #f8fafc;
        padding: 18px 20px;
    }
    .apt-card {
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        background: #fff;
    }
    .apt-sticky {
        position: sticky;
        top: 74px;
    }
    .apt-customer-pill {
        border: 1px dashed #94a3b8;
        border-radius: 10px;
        padding: 10px 12px;
        background: #f8fafc;
    }
    .apt-customer-pill.active {
        border-style: solid;
        border-color: #2563eb;
        background: #eff6ff;
    }
    .customer-picker-row {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 8px;
        background: #fff;
    }
    .customer-picker-row.active {
        border-color: #2563eb;
        background: #eff6ff;
    }
</style>
@endpush

@section('content')
<div class="container py-3 px-3 px-lg-4">

    <div class="apt-hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-1 fw-bold"><i class="bi bi-calendar2-check me-2"></i>Cuộc hẹn khách hàng</h5>
            <div class="small" style="opacity:.9;">Tạo lịch hẹn nhanh và theo dõi tiến độ chăm sóc khách hàng.</div>
        </div>
        <a href="{{ route('pages.my_customer') }}" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Danh sách khách hàng
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="apt-card apt-sticky">
                <div class="card-header bg-white border-0 pt-3 px-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-1 text-primary"></i>Tạo cuộc hẹn mới</h6>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="{{ route('customer_appointments.store') }}" enctype="multipart/form-data" class="d-flex flex-column gap-3" id="createAptForm">
                        @csrf

                        <input type="hidden" name="customer_id" id="customerIdInput" value="{{ old('customer_id') }}">

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">
                                Khách hàng <span class="text-danger">*</span>
                            </label>
                            <div class="apt-customer-pill" id="formCustomerEmpty">
                                <div class="text-muted" style="font-size:.82rem;">Chưa chọn khách hàng</div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#customerPickerModal">
                                    <i class="bi bi-people me-1"></i>Chọn khách hàng
                                </button>
                            </div>
                            <div class="apt-customer-pill active d-none" id="formCustomerSelected">
                                <div class="d-flex justify-content-between gap-2 align-items-start">
                                    <div>
                                        <div class="fw-semibold text-primary" id="formCustomerName"></div>
                                        <div class="text-muted" id="formCustomerPhone" style="font-size:.78rem;"></div>
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="formClearCustomer" title="Bỏ chọn">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#customerPickerModal">
                                    <i class="bi bi-arrow-repeat me-1"></i>Đổi khách hàng
                                </button>
                            </div>
                            @error('customer_id')
                                <div class="text-danger mt-1" style="font-size:.82rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">Thời gian hẹn <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="remind_at" class="form-control form-control-sm @error('remind_at') is-invalid @enderror" value="{{ old('remind_at') }}" required>
                            @error('remind_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">Nội dung <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-sm @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="VD: Chốt mẫu, Demo sản phẩm..." required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">Ghi chú</label>
                            <textarea name="note" class="form-control form-control-sm" rows="3" placeholder="Thêm thông tin...">{{ old('note') }}</textarea>
                        </div>

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">Ảnh cuộc hẹn</label>
                            <input type="file" name="image" class="form-control form-control-sm" accept="image/*" capture="environment">
                            <div class="text-muted mt-1" style="font-size:.75rem;">
                                <i class="bi bi-camera me-1"></i>Có thể chụp trực tiếp bằng camera điện thoại.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i>Lưu cuộc hẹn
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="apt-card mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-sm-5">
                            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Tìm tên KH, SĐT, nội dung...">
                        </div>
                        <div class="col-sm-3">
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-3">
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-1 d-grid">
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Lọc">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @forelse($appointments as $apt)
            <div class="apt-card mb-2">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1" style="font-size:.9rem;">{{ $apt->title }}</div>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="badge bg-light text-dark border" style="font-size:.76rem;">
                                    <i class="bi bi-person me-1"></i>{{ $apt->customer->name ?? '–' }}{{ !empty($apt->customer->phone) ? ' · ' . $apt->customer->phone : '' }}
                                </span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:.76rem;">
                                    <i class="bi bi-calendar-event me-1"></i>{{ optional($apt->remind_at)->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            @if(!empty($apt->note))
                                <div class="text-muted mt-1" style="font-size:.82rem;">{{ $apt->note }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            <button class="btn btn-xs btn-outline-warning py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#editApt{{ $apt->id }}" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('customer_appointments.destroy', $apt) }}" onsubmit="return confirm('Xóa cuộc hẹn này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger py-0 px-2" title="Xóa">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    @if(!empty($apt->image_path))
                        <a href="{{ asset('storage/' . $apt->image_path) }}" target="_blank" class="d-inline-block mt-2">
                            <img src="{{ asset('storage/' . $apt->image_path) }}" alt="Ảnh cuộc hẹn" style="width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #dbe4ef;">
                        </a>
                    @endif

                    <div class="collapse mt-2" id="editApt{{ $apt->id }}">
                        <div class="border-top pt-2">
                            <form method="POST" action="{{ route('customer_appointments.update', $apt) }}" enctype="multipart/form-data" class="row g-2">
                                @csrf @method('PUT')
                                <div class="col-12">
                                    <input type="text" name="title" class="form-control form-control-sm" value="{{ $apt->title }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <input type="datetime-local" name="remind_at" class="form-control form-control-sm" value="{{ optional($apt->remind_at)->format('Y-m-d\\TH:i') }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <input type="file" name="image" class="form-control form-control-sm" accept="image/*" capture="environment">
                                </div>
                                <div class="col-12">
                                    <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Ghi chú...">{{ $apt->note }}</textarea>
                                </div>
                                @if(!empty($apt->image_path))
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="rm-img-{{ $apt->id }}">
                                        <label class="form-check-label text-danger" for="rm-img-{{ $apt->id }}" style="font-size:.8rem;">Xóa ảnh hiện tại</label>
                                    </div>
                                </div>
                                @endif
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-check me-1"></i>Lưu thay đổi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="apt-card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-calendar-x" style="font-size:2rem;opacity:.4;"></i>
                    <div class="mt-2">Chưa có cuộc hẹn nào
                        @if($search || request('from_date') || request('to_date'))
                            phù hợp với bộ lọc.
                            <a href="{{ route('pages.my_customer_appointments') }}" class="ms-1">Xóa lọc</a>
                        @endif
                    </div>
                </div>
            </div>
            @endforelse

            <div class="mt-2">{{ $appointments->links() }}</div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-people me-1"></i>Chọn khách hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold mb-1">Tìm kiếm</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="customerPickerSearch" placeholder="Tên khách hàng, số điện thoại..." autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" id="customerPickerClear" style="display:none;">Xóa</button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-1">Hiển thị / trang</label>
                        <select id="customerPickerPerPage" class="form-select form-select-sm">
                            <option value="10">10 khách hàng</option>
                            <option value="20">20 khách hàng</option>
                            <option value="50">50 khách hàng</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small text-muted" id="customerPickerCount">Đang tải...</div>
                    <div class="small text-muted" id="customerPickerPageInfo">Trang 1/1</div>
                </div>

                <div id="customerPickerLoading" class="text-center text-muted py-4" style="display:none;">
                    <div class="spinner-border spinner-border-sm me-1"></div> Đang tải danh sách...
                </div>
                <div id="customerPickerEmpty" class="text-center text-muted py-4" style="display:none;">Không tìm thấy khách hàng.</div>
                <div id="customerPickerList"></div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="customerPickerPrev">Trang trước</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="customerPickerNext">Trang sau</button>
                </div>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const SEARCH_URL = '{{ route("customer_appointments.search_customers") }}';
    const initialSelected = @json($selectedCustomer ? ['id' => $selectedCustomer->id, 'name' => $selectedCustomer->name, 'phone' => $selectedCustomer->phone] : null);

    const idInput = document.getElementById('customerIdInput');
    const formEmpty = document.getElementById('formCustomerEmpty');
    const formSelected = document.getElementById('formCustomerSelected');
    const formName = document.getElementById('formCustomerName');
    const formPhone = document.getElementById('formCustomerPhone');
    const formClear = document.getElementById('formClearCustomer');

    const pickerModalEl = document.getElementById('customerPickerModal');
    const pickerSearch = document.getElementById('customerPickerSearch');
    const pickerClear = document.getElementById('customerPickerClear');
    const pickerPerPage = document.getElementById('customerPickerPerPage');
    const pickerCount = document.getElementById('customerPickerCount');
    const pickerPageInfo = document.getElementById('customerPickerPageInfo');
    const pickerList = document.getElementById('customerPickerList');
    const pickerLoading = document.getElementById('customerPickerLoading');
    const pickerEmpty = document.getElementById('customerPickerEmpty');
    const pickerPrev = document.getElementById('customerPickerPrev');
    const pickerNext = document.getElementById('customerPickerNext');

    let searchTimer;
    let selectedId = idInput.value || null;
    let state = {
        q: '',
        page: 1,
        perPage: 10,
        lastPage: 1,
    };

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;');
    }

    function renderSelected(name, phone) {
        formEmpty.classList.add('d-none');
        formSelected.classList.remove('d-none');
        formName.textContent = name || '';
        formPhone.textContent = phone || '';
    }

    function clearSelected() {
        selectedId = null;
        idInput.value = '';
        formSelected.classList.add('d-none');
        formEmpty.classList.remove('d-none');
        highlightSelected();
    }

    function selectCustomer(customer) {
        selectedId = String(customer.id);
        idInput.value = selectedId;
        renderSelected(customer.name, customer.phone || '');
        highlightSelected();

        const modal = bootstrap.Modal.getInstance(pickerModalEl);
        if (modal) {
            modal.hide();
        }
    }

    function highlightSelected() {
        pickerList.querySelectorAll('[data-customer-id]').forEach(function (el) {
            const active = el.dataset.customerId === String(selectedId || '');
            el.classList.toggle('active', active);
        });
    }

    function setLoading(loading) {
        pickerLoading.style.display = loading ? '' : 'none';
        if (loading) {
            pickerEmpty.style.display = 'none';
        }
    }

    async function loadCustomers() {
        setLoading(true);
        pickerList.innerHTML = '';

        const params = new URLSearchParams({
            q: state.q,
            page: String(state.page),
            per_page: String(state.perPage),
        });

        try {
            const res = await fetch(SEARCH_URL + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            const payload = await res.json();
            const rows = Array.isArray(payload.data) ? payload.data : [];
            const meta = payload.meta || {};

            state.lastPage = Number(meta.last_page || 1);
            state.page = Number(meta.current_page || 1);

            pickerCount.textContent = meta.total
                ? ('Hiển thị ' + (meta.from || 0) + '-' + (meta.to || 0) + ' / ' + meta.total + ' khách hàng')
                : '0 khách hàng';
            pickerPageInfo.textContent = 'Trang ' + state.page + '/' + state.lastPage;

            pickerPrev.disabled = state.page <= 1;
            pickerNext.disabled = state.page >= state.lastPage;

            if (!rows.length) {
                pickerEmpty.style.display = '';
                return;
            }

            pickerEmpty.style.display = 'none';
            pickerList.innerHTML = rows.map(function (c) {
                return '<div class="customer-picker-row" data-customer-id="' + c.id + '">' +
                        '<div class="d-flex justify-content-between align-items-start gap-2">' +
                            '<div>' +
                                '<div class="fw-semibold">' + esc(c.name) + '</div>' +
                                '<div class="text-muted small">' + esc(c.phone || 'Không có SĐT') + '</div>' +
                            '</div>' +
                            '<button type="button" class="btn btn-primary btn-sm" data-select-id="' + c.id + '">Chọn</button>' +
                        '</div>' +
                    '</div>';
            }).join('');

            pickerList.querySelectorAll('[data-select-id]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.dataset.selectId;
                    const row = rows.find(function (r) { return String(r.id) === String(id); });
                    if (row) {
                        selectCustomer(row);
                    }
                });
            });

            highlightSelected();
        } catch (e) {
            pickerCount.textContent = 'Không thể tải danh sách khách hàng.';
            pickerEmpty.style.display = '';
            pickerEmpty.textContent = 'Có lỗi khi tải dữ liệu. Vui lòng thử lại.';
        } finally {
            setLoading(false);
        }
    }

    pickerSearch.addEventListener('input', function () {
        clearTimeout(searchTimer);
        state.q = this.value.trim();
        state.page = 1;
        pickerClear.style.display = state.q ? '' : 'none';

        searchTimer = setTimeout(function () {
            loadCustomers();
        }, 300);
    });

    pickerClear.addEventListener('click', function () {
        pickerSearch.value = '';
        state.q = '';
        state.page = 1;
        pickerClear.style.display = 'none';
        loadCustomers();
    });

    pickerPerPage.addEventListener('change', function () {
        state.perPage = Number(this.value || 10);
        state.page = 1;
        loadCustomers();
    });

    pickerPrev.addEventListener('click', function () {
        if (state.page > 1) {
            state.page -= 1;
            loadCustomers();
        }
    });

    pickerNext.addEventListener('click', function () {
        if (state.page < state.lastPage) {
            state.page += 1;
            loadCustomers();
        }
    });

    pickerModalEl.addEventListener('shown.bs.modal', function () {
        loadCustomers();
        pickerSearch.focus();
    });

    if (formClear) {
        formClear.addEventListener('click', clearSelected);
    }

    if (initialSelected && initialSelected.id) {
        selectedId = String(initialSelected.id);
        idInput.value = selectedId;
        renderSelected(initialSelected.name, initialSelected.phone || '');
    }
})();
</script>
@endpush
@endsection
