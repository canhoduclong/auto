
@php
    use App\Models\Setting;
    $brandName = Setting::get('brand_name', config('app.name'));
    $logoId = Setting::get('logo');
    $logoUrl = null;
    if ($logoId) {
        $media = \App\Models\Media::find($logoId);
        if ($media) {
            $logoUrl = asset('storage/' . $media->file_path);
        }
    }
@endphp

@extends('layouts.site')
 
@section('content')
<div class="py-4"> 
    <div class="  shadow-sm border-0 mb-4">

        @section('content')
        <div class="container py-4">
           
            <div class="mb-4">
                <div class="bg-white rounded-3 shadow-sm p-4 border d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="fs-5 fw-bold mb-1" style="color:#0f766e;">Tổng hợp hiệu suất kinh doanh</div>
                        <div class="text-muted">Theo dõi số lượng sản phẩm, đơn hàng, khách hàng mới/cũ trong từng giai đoạn.</div>
                    </div>
                    <form method="GET" action="{{ route('work-reports.index') }}" class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label class="form-label mb-1">Kiểu báo cáo</label>
                            <select name="type" class="form-select">
                                <option value="month" {{ $type == 'month' ? 'selected' : '' }}>Theo tháng</option>
                                <option value="week" {{ $type == 'week' ? 'selected' : '' }}>Theo tuần</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-1">Ngày</label>
                            <input type="date" name="date" class="form-control" value="{{ $date }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Lọc</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3">
                        <h5 class="card-title mb-0" style="color:#0f766e;font-weight:700;">Thống kê từ <span class="text-primary">{{ $start->format('d/m/Y') }}</span> đến <span class="text-primary">{{ $end->format('d/m/Y') }}</span></h5>
                        <div class="d-flex gap-2 mt-2 mt-md-0">
                            <button class="btn btn-outline-primary btn-sm" id="show-orders-tab"><i class="bi bi-receipt"></i> Đơn hàng mới</button>
                            <button class="btn btn-outline-primary btn-sm" id="show-customers-tab"><i class="bi bi-person-plus"></i> Khách hàng mới</button>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3 text-center border h-100">
                                <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-box-seam"></i></div>
                                <div class="fw-bold" style="font-size:1.5rem;">{{ $productCount }}</div>
                                <div class="text-muted">Sản phẩm mới</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3 text-center border h-100">
                                <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-receipt"></i></div>
                                <div class="fw-bold" style="font-size:1.5rem;">{{ $orderCount }}</div>
                                <div class="text-muted">Đơn hàng mới</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3 text-center border h-100">
                                <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-person-plus"></i></div>
                                <div class="fw-bold" style="font-size:1.5rem;">{{ $newCustomerCount }}</div>
                                <div class="text-muted">Khách hàng mới</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3 text-center border h-100">
                                <div class="mb-2" style="font-size:2.2rem;color:#0f766e;"><i class="bi bi-person-check"></i></div>
                                <div class="fw-bold" style="font-size:1.5rem;">{{ $oldCustomerCount }}</div>
                                <div class="text-muted">Khách hàng cũ phát sinh đơn</div>
                            </div>
                        </div>
                    </div>
                    <div id="tab-orders" style="display:none;">
                        <h6 class="fw-bold mt-4 mb-2"><i class="bi bi-receipt"></i> Danh sách đơn hàng mới</h6>
                        <div class="table-responsive">
                            <table class="table align-middle table-borderless table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Ngày tạo</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Tổng tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders ?? [] as $order)
                                        <tr>
                                            <td class="fw-bold text-primary">#{{ $order->code ?? $order->id }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @php
                                                        $avatar = $order->customer->avatar ?? null;
                                                        $avatarUrl = $avatar ? asset($avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($order->customer->name ?? 'U') . '&background=0f766e&color=fff&size=40&bold=true';
                                                    @endphp
                                                    <img src="{{ $avatarUrl }}" alt="avatar" class="rounded-circle" width="36" height="36">
                                                    <div>
                                                        <div class="fw-semibold">{{ $order->customer->name ?? '-' }}</div>
                                                        <div class="text-muted small">{{ $order->customer->email ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}</td>
                                            <td>
                                                @php
                                                    $status = $order->status ?? '';
                                                    $badgeClass = 'secondary';
                                                    if (str_contains($status, 'thành công') || $status === 'completed') $badgeClass = 'success';
                                                    elseif (str_contains($status, 'hủy') || $status === 'cancelled') $badgeClass = 'danger';
                                                    elseif (str_contains($status, 'chờ') || $status === 'pending') $badgeClass = 'warning';
                                                    elseif (str_contains($status, 'đang') || $status === 'processing') $badgeClass = 'info';
                                                @endphp
                                                <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($status) }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-success">{{ number_format($order->total_amount ?? 0, 0, ',', '.') }} đ</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">Không có đơn hàng mới</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="tab-customers" style="display:none;">
                        <h6 class="fw-bold mt-4 mb-2"><i class="bi bi-person-plus"></i> Danh sách khách hàng mới</h6>
                        <div class="table-responsive">
                            <table class="table align-middle table-borderless table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th>Tên khách hàng</th>
                                        <th>Email</th>
                                        <th>SĐT</th>
                                        <th>Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($newCustomers ?? [] as $customer)
                                        <tr>
                                            <td>
                                                @php
                                                    $avatar = $customer->avatar ?? null;
                                                    $avatarUrl = $avatar ? asset($avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($customer->name ?? 'U') . '&background=0f766e&color=fff&size=40&bold=true';
                                                @endphp
                                                <img src="{{ $avatarUrl }}" alt="avatar" class="rounded-circle" width="36" height="36">
                                            </td>
                                            <td class="fw-semibold">{{ $customer->name }}</td>
                                            <td>{{ $customer->email }}</td>
                                            <td>{{ $customer->phone }}</td>
                                            <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">Không có khách hàng mới</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @push('scripts')
        <script>
            document.getElementById('show-orders-tab').onclick = function() {
                document.getElementById('tab-orders').style.display = 'block';
                document.getElementById('tab-customers').style.display = 'none';
            };
            document.getElementById('show-customers-tab').onclick = function() {
                document.getElementById('tab-orders').style.display = 'none';
                document.getElementById('tab-customers').style.display = 'block';
            };
        </script>
        @endpush
        @endsection
