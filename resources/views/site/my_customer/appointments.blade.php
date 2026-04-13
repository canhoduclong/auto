@extends('layouts.site')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="mb-1">Cuộc hẹn khách hàng</h2>
            <p class="text-muted mb-0">Sale theo dõi lịch hẹn và chụp ảnh cho từng lần gặp khách hàng.</p>
        </div>
        <a href="{{ route('pages.my_customer') }}" class="btn btn-outline-secondary">Quay lại khách hàng</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header fw-semibold">Tạo cuộc hẹn mới</div>
        <div class="card-body">
            <form method="POST" action="{{ route('customer_appointments.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Khách hàng</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">-- Chọn khách hàng --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Thời gian hẹn</label>
                    <input type="datetime-local" name="remind_at" class="form-control" value="{{ old('remind_at') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nội dung cuộc hẹn</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Ví dụ: Chốt mẫu mới" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="Thông tin thêm...">{{ old('note') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ảnh cuộc hẹn</label>
                    <input type="file" name="image" class="form-control" accept="image/*" capture="environment">
                    <small class="text-muted">Có thể dùng camera điện thoại để chụp trực tiếp.</small>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Lưu cuộc hẹn</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tên khách, số điện thoại, nội dung hẹn...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-primary">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse($appointments as $appointment)
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h5 class="mb-1">{{ $appointment->title }}</h5>
                                <div class="small text-muted mb-1">
                                    <i class="bi bi-person me-1"></i>{{ $appointment->customer->name ?? '-' }}
                                    @if(!empty($appointment->customer->phone))
                                        - {{ $appointment->customer->phone }}
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>{{ optional($appointment->remind_at)->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <div class="text-end">
                                <form method="POST" action="{{ route('customer_appointments.destroy', $appointment) }}" onsubmit="return confirm('Xóa cuộc hẹn này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            </div>
                        </div>

                        @if(!empty($appointment->note))
                            <div class="mt-2">{{ $appointment->note }}</div>
                        @endif

                        @if(!empty($appointment->image_path))
                            <a href="{{ asset('storage/' . $appointment->image_path) }}" target="_blank" class="d-inline-flex align-items-center gap-2 mt-3 text-decoration-none">
                                <img src="{{ asset('storage/' . $appointment->image_path) }}" alt="Ảnh cuộc hẹn" style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:1px solid #dbe4ef;">
                                <span class="small text-muted">Xem ảnh</span>
                            </a>
                        @endif

                        <details class="mt-3">
                            <summary class="small text-primary" style="cursor:pointer;">Sửa cuộc hẹn</summary>
                            <form method="POST" action="{{ route('customer_appointments.update', $appointment) }}" enctype="multipart/form-data" class="row g-2 mt-1">
                                @csrf
                                @method('PUT')
                                <div class="col-12">
                                    <input type="text" name="title" class="form-control" value="{{ $appointment->title }}" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="datetime-local" name="remind_at" class="form-control" value="{{ optional($appointment->remind_at)->format('Y-m-d\\TH:i') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="file" name="image" class="form-control" accept="image/*" capture="environment">
                                </div>
                                <div class="col-12">
                                    <textarea name="note" class="form-control" rows="2">{{ $appointment->note }}</textarea>
                                </div>
                                @if(!empty($appointment->image_path))
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove-image-apt-{{ $appointment->id }}">
                                            <label class="form-check-label" for="remove-image-apt-{{ $appointment->id }}">Xóa ảnh hiện tại</label>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-12 d-grid">
                                    <button type="submit" class="btn btn-sm btn-primary">Lưu thay đổi</button>
                                </div>
                            </form>
                        </details>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border text-muted mb-0">Chưa có cuộc hẹn nào.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $appointments->links() }}
    </div>
</div>
@endsection
