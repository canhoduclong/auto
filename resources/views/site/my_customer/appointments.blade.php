@extends('layouts.site')

@section('content')
<div class="container py-3 px-3 px-lg-4">

    {{-- Page header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-0 fw-bold"><i class="bi bi-calendar2-check me-2 text-primary"></i>Cuộc hẹn khách hàng</h5>
        </div>
        <a href="{{ route('pages.my_customer') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Khách hàng
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

        {{-- COL 1: Customer list panel --}}
        <div class="col-lg-6">
            <div class="card shadow-sm" style="position:sticky;top:72px;">
                <div class="card-header py-2 fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-people me-1 text-primary"></i>Khách hàng</span>
                    <span class="badge bg-secondary bg-opacity-20 text-secondary" id="customerCountBadge">–</span>
                </div>
                <div class="px-2 pt-2 pb-1 border-bottom">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="customerListSearch" class="form-control border-start-0 ps-0"
                               placeholder="Tìm tên, SĐT..." autocomplete="off">
                        <button class="btn btn-outline-secondary border-start-0" id="customerListClear" type="button" style="display:none;" title="Xóa">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
                <div id="customerListContainer"
                     style="max-height:calc(100vh - 240px);overflow-y:auto;min-height:120px;">
                    <div class="text-center text-muted py-4" id="customerListLoading">
                        <div class="spinner-border spinner-border-sm me-1"></div> Đang tải...
                    </div>
                    <div id="customerListBody"></div>
                </div>
                {{-- Selected display --}}
                <div class="card-footer py-2 px-2 d-none" id="customerSelectedBar">
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <div class="text-muted" style="font-size:.72rem;">Đang chọn:</div>
                            <div class="fw-semibold text-primary" id="customerSelectedName" style="font-size:.86rem;"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" id="customerDeselect" title="Bỏ chọn">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
         

        {{-- COL 2: Create form --}}
         
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-semibold py-2">
                    <i class="bi bi-plus-circle me-1"></i>Tạo cuộc hẹn mới
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="{{ route('customer_appointments.store') }}"
                          enctype="multipart/form-data" class="d-flex flex-column gap-3" id="createAptForm">
                        @csrf

                        {{-- Hidden customer id --}}
                        <input type="hidden" name="customer_id" id="customerIdInput" value="{{ old('customer_id') }}">

                        {{-- Customer display (set by clicking list) --}}
                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">
                                Khách hàng <span class="text-danger">*</span>
                            </label>
                            <div id="formCustomerEmpty" class="border rounded p-2 text-muted d-flex align-items-center gap-2"
                                 style="font-size:.82rem;background:#f8f9fa;">
                                <i class="bi bi-arrow-left text-primary"></i>
                                Chọn khách hàng từ danh sách
                            </div>
                            <div id="formCustomerSelected" class="border border-primary rounded p-2 d-none"
                                 style="font-size:.84rem;background:#eef4ff;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold text-primary" id="formCustomerName"></div>
                                        <div class="text-muted" id="formCustomerPhone" style="font-size:.78rem;"></div>
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="formClearCustomer">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            </div>
                            @error('customer_id')
                                <div class="text-danger mt-1" style="font-size:.82rem;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">
                                Thời gian hẹn <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" name="remind_at"
                                   class="form-control form-control-sm @error('remind_at') is-invalid @enderror"
                                   value="{{ old('remind_at') }}" required>
                            @error('remind_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">
                                Nội dung <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title"
                                   class="form-control form-control-sm @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" placeholder="VD: Chốt mẫu, Demo sản phẩm..." required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">Ghi chú</label>
                            <textarea name="note" class="form-control form-control-sm" rows="3"
                                      placeholder="Thêm thông tin...">{{ old('note') }}</textarea>
                        </div>

                        <div>
                            <label class="form-label fw-semibold mb-1" style="font-size:.84rem;">Ảnh cuộc hẹn</label>
                            <input type="file" name="image" class="form-control form-control-sm"
                                   accept="image/*" capture="environment">
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

        {{-- COL 3: Appointments list --}}
        <div class="col-lg-6">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Filter bar --}}
            <div class="card shadow-sm mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-sm-5">
                            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm"
                                   placeholder="Tìm tên KH, SĐT, nội dung...">
                        </div>
                        <div class="col-sm-3">
                            <input type="date" name="from_date" value="{{ request('from_date') }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-3">
                            <input type="date" name="to_date" value="{{ request('to_date') }}"
                                   class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-1 d-grid">
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Lọc">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Appointment cards --}}
            @forelse($appointments as $apt)
            <div class="card shadow-sm mb-2">
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
                            <button class="btn btn-xs btn-outline-warning py-0 px-2"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#editApt{{ $apt->id }}" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('customer_appointments.destroy', $apt) }}"
                                  onsubmit="return confirm('Xóa cuộc hẹn này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger py-0 px-2" title="Xóa">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    @if(!empty($apt->image_path))
                        <a href="{{ asset('storage/' . $apt->image_path) }}" target="_blank" class="d-inline-block mt-2">
                            <img src="{{ asset('storage/' . $apt->image_path) }}" alt="Ảnh cuộc hẹn"
                                 style="width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #dbe4ef;">
                        </a>
                    @endif

                    {{-- Edit collapse --}}
                    <div class="collapse mt-2" id="editApt{{ $apt->id }}">
                        <div class="border-top pt-2">
                            <form method="POST" action="{{ route('customer_appointments.update', $apt) }}"
                                  enctype="multipart/form-data" class="row g-2">
                                @csrf @method('PUT')
                                <div class="col-12">
                                    <input type="text" name="title" class="form-control form-control-sm"
                                           value="{{ $apt->title }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <input type="datetime-local" name="remind_at" class="form-control form-control-sm"
                                           value="{{ optional($apt->remind_at)->format('Y-m-d\TH:i') }}" required>
                                </div>
                                <div class="col-sm-6">
                                    <input type="file" name="image" class="form-control form-control-sm"
                                           accept="image/*" capture="environment">
                                </div>
                                <div class="col-12">
                                    <textarea name="note" class="form-control form-control-sm" rows="2"
                                              placeholder="Ghi chú...">{{ $apt->note }}</textarea>
                                </div>
                                @if(!empty($apt->image_path))
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_image" value="1"
                                               id="rm-img-{{ $apt->id }}">
                                        <label class="form-check-label text-danger" for="rm-img-{{ $apt->id }}"
                                               style="font-size:.8rem;">Xóa ảnh hiện tại</label>
                                    </div>
                                </div>
                                @endif
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm btn-primary px-3">
                                        <i class="bi bi-check me-1"></i>Lưu thay đổi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="card shadow-sm">
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

@push('scripts')
<script>
(function () {
    const SEARCH_URL = '{{ route("customer_appointments.search_customers") }}';

    // ── Customer list ─────────────────────────────────────────────────────────
    const listBody    = document.getElementById('customerListBody');
    const listLoading = document.getElementById('customerListLoading');
    const listSearch  = document.getElementById('customerListSearch');
    const listClear   = document.getElementById('customerListClear');
    const countBadge  = document.getElementById('customerCountBadge');

    // Form elements
    const idInput          = document.getElementById('customerIdInput');
    const formEmpty        = document.getElementById('formCustomerEmpty');
    const formSelected     = document.getElementById('formCustomerSelected');
    const formName         = document.getElementById('formCustomerName');
    const formPhone        = document.getElementById('formCustomerPhone');
    const formClear        = document.getElementById('formClearCustomer');
    const selectedBar      = document.getElementById('customerSelectedBar');
    const selectedName     = document.getElementById('customerSelectedName');
    const deselect         = document.getElementById('customerDeselect');

    let searchTimer;
    let currentId = null;

    // Load on page load
    loadCustomers('');

    listSearch.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        listClear.style.display = q ? '' : 'none';
        searchTimer = setTimeout(() => loadCustomers(q), 280);
    });

    listClear.addEventListener('click', function () {
        listSearch.value = '';
        listClear.style.display = 'none';
        loadCustomers('');
        listSearch.focus();
    });

    async function loadCustomers(q) {
        listLoading.style.display = '';
        listBody.innerHTML = '';
        try {
            const res  = await fetch(SEARCH_URL + (q ? '?q=' + encodeURIComponent(q) : ''), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const list = await res.json();
            countBadge.textContent = list.length;
            renderCustomerList(list);
        } catch (e) {
            listBody.innerHTML = '<div class="text-danger p-2" style="font-size:.82rem;">Lỗi tải danh sách.</div>';
        }
        listLoading.style.display = 'none';
    }

    function renderCustomerList(list) {
        if (!list.length) {
            listBody.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.82rem;">Không tìm thấy khách hàng.</div>';
            return;
        }
        listBody.innerHTML = list.map(c => {
            const phone = c.phone
                ? `<div class="text-muted" style="font-size:.74rem;">${esc(c.phone)}</div>`
                : '';
            return `<button type="button"
                            class="customer-list-item list-group-item list-group-item-action border-0 border-bottom py-2 px-3"
                            data-id="${c.id}" data-name="${esc(c.name)}" data-phone="${esc(c.phone ?? '')}"
                            style="font-size:.84rem;text-align:left;">
                        <div class="fw-semibold">${esc(c.name)}</div>${phone}
                    </button>`;
        }).join('');

        // Bind click
        listBody.querySelectorAll('.customer-list-item').forEach(btn => {
            btn.addEventListener('click', function () {
                selectCustomer(this.dataset.id, this.dataset.name, this.dataset.phone);
            });
        });

        // Re-highlight if already selected
        if (currentId) highlightItem(currentId);
    }

    function selectCustomer(id, name, phone) {
        currentId = id;
        idInput.value = id;

        // Form display
        formEmpty.classList.add('d-none');
        formSelected.classList.remove('d-none');
        formName.textContent  = name;
        formPhone.textContent = phone || '';

        // Sidebar bar
        selectedBar.classList.remove('d-none');
        selectedName.textContent = name;

        // Highlight in list
        highlightItem(id);
    }

    function highlightItem(id) {
        listBody.querySelectorAll('.customer-list-item').forEach(btn => {
            const active = btn.dataset.id === String(id);
            btn.classList.toggle('active', active);
            btn.classList.toggle('text-white', active);
        });
    }

    function clearSelection() {
        currentId     = null;
        idInput.value = '';
        formEmpty.classList.remove('d-none');
        formSelected.classList.add('d-none');
        selectedBar.classList.add('d-none');
        listBody.querySelectorAll('.customer-list-item').forEach(btn => {
            btn.classList.remove('active', 'text-white');
        });
    }

    formClear.addEventListener('click', clearSelection);
    deselect.addEventListener('click', clearSelection);

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
@endpush
@endsection
