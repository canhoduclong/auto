@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-3">
        <div>
            <h3 class="mb-1">Chi tiet khach hang</h3>
            <div class="text-muted">
                {{ $customer->name }}
                @if($customer->phone)
                    - {{ $customer->phone }}
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary">Chinh sua thong tin</a>
            @if($customer->phone)
                <a href="tel:{{ preg_replace('/\D+/', '', $customer->phone) }}" class="btn btn-outline-success">Goi nhanh</a>
            @endif
            <a href="{{ route('orders.create_new', ['customer_id' => $customer->id]) }}" class="btn btn-primary">Tao don hang moi</a>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Quay lai</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $defaultAddress = $customer->addresses->firstWhere('is_default', 1) ?? $customer->addresses->first();
        $region = collect([
            optional($defaultAddress)->district,
            optional($defaultAddress)->city,
            optional($defaultAddress)->province,
        ])->filter()->implode(', ');

        $customerDebtStatus = 'Da thanh toan du';
        $customerDebtClass = 'success';
        $hasOverdueDebt = false;

        foreach ($debtOrders as $order) {
            $orderPaid = (float) $order->transactions->where('type', 'payment')->sum('amount') - (float) $order->transactions->where('type', 'refund')->sum('amount');
            $orderOutstanding = max((float) $order->total - $orderPaid, 0);
            $isOverdue = $orderOutstanding > 0 && optional($order->created_at)->copy()->addDays(30)->isPast();
            if ($isOverdue) {
                $hasOverdueDebt = true;
                break;
            }
        }

        if ($totalOutstandingAmount > 0) {
            if ($hasOverdueDebt) {
                $customerDebtStatus = 'No qua han';
                $customerDebtClass = 'danger';
            } else {
                $customerDebtStatus = 'Con no';
                $customerDebtClass = 'warning';
            }
        }

        $periodLabel = match($period) {
            'today' => 'Hom nay',
            'week' => 'Tuan nay',
            'custom' => 'Tuy chon',
            default => 'Thang nay',
        };

        $queryParams = request()->except('orders_page', 'debt_page', 'payments_page');
    @endphp

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('customers.show', $customer) }}" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="col-md-2">
                    <label class="form-label">Thoi gian</label>
                    <select name="period" class="form-select" onchange="toggleDateRange(this.value)">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hom nay</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Tuan nay</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Thang nay</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Khoang tuy chon</option>
                    </select>
                </div>
                <div class="col-md-2 date-range-input" style="display: {{ $period === 'custom' ? 'block' : 'none' }};">
                    <label class="form-label">Tu ngay</label>
                    <input type="date" name="from_date" value="{{ $fromDate->toDateString() }}" class="form-control">
                </div>
                <div class="col-md-2 date-range-input" style="display: {{ $period === 'custom' ? 'block' : 'none' }};">
                    <label class="form-label">Den ngay</label>
                    <input type="date" name="to_date" value="{{ $toDate->toDateString() }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trang thai don</label>
                    <select name="order_status" class="form-select">
                        <option value="">Tat ca</option>
                        @foreach($orderStatuses as $status)
                            <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Loc du lieu</button>
                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">Reset</a>
                    <div class="ms-auto text-muted align-self-center small">Bo loc: {{ $periodLabel }} ({{ $fromDate->format('d/m/Y') }} - {{ $toDate->format('d/m/Y') }})</div>
                </div>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'info' ? 'active' : '' }}" href="{{ route('customers.show', array_merge(['customer' => $customer, 'tab' => 'info'], $queryParams)) }}">Thong tin</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'debt' ? 'active' : '' }}" href="{{ route('customers.show', array_merge(['customer' => $customer, 'tab' => 'debt'], $queryParams)) }}">Cong no</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'orders' ? 'active' : '' }}" href="{{ route('customers.show', array_merge(['customer' => $customer, 'tab' => 'orders'], $queryParams)) }}">Don hang</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'payments' ? 'active' : '' }}" href="{{ route('customers.show', array_merge(['customer' => $customer, 'tab' => 'payments'], $queryParams)) }}">Thanh toan</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'reports' ? 'active' : '' }}" href="{{ route('customers.show', array_merge(['customer' => $customer, 'tab' => 'reports'], $queryParams)) }}">Bao cao</a>
        </li>
    </ul>

    @if($activeTab === 'info')
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Thong tin khach hang</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Ten khach hang</div>
                                <div class="fw-semibold">{{ $customer->name }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">So dien thoai</div>
                                <div class="fw-semibold">{{ $customer->phone ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Email</div>
                                <div class="fw-semibold">{{ $customer->email ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Loai khach hang</div>
                                <div class="fw-semibold">{{ optional($customer->type)->name ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Khu vuc / tinh thanh</div>
                                <div class="fw-semibold">{{ $region ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Nguoi phu trach</div>
                                <div class="fw-semibold">{{ optional($customer->assignedTo)->name ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Ngay tao khach</div>
                                <div class="fw-semibold">{{ optional($customer->created_at)->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Dia chi</div>
                                <div class="fw-semibold">{{ optional($defaultAddress)->note ?: ($customer->address ?: '-') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Tong quan cong no</div>
                    <div class="card-body d-grid gap-3">
                        <div class="p-3 rounded border bg-light">
                            <div class="text-muted small">Tong tien don hang</div>
                            <div class="fs-5 fw-bold">{{ number_format($totalOrderAmount, 0, ',', '.') }} đ</div>
                        </div>
                        <div class="p-3 rounded border bg-light">
                            <div class="text-muted small">Tong da thanh toan</div>
                            <div class="fs-5 fw-bold text-success">{{ number_format($totalPaidAmount, 0, ',', '.') }} đ</div>
                        </div>
                        <div class="p-3 rounded border bg-light">
                            <div class="text-muted small">Cong no hien tai</div>
                            <div class="fs-5 fw-bold {{ $totalOutstandingAmount > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($totalOutstandingAmount, 0, ',', '.') }} đ</div>
                            <span class="badge bg-{{ $customerDebtClass }} mt-2">{{ $customerDebtStatus }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'debt')
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Tong tien don hang</div>
                        <div class="fs-5 fw-bold">{{ number_format($totalOrderAmount, 0, ',', '.') }} đ</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Tong da thanh toan</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format($totalPaidAmount, 0, ',', '.') }} đ</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Cong no hien tai</div>
                        <div class="fs-5 fw-bold {{ $totalOutstandingAmount > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($totalOutstandingAmount, 0, ',', '.') }} đ</div>
                        <span class="badge bg-{{ $customerDebtClass }} mt-1">{{ $customerDebtStatus }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Chi tiet cong no theo don hang</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ma don</th>
                                <th>Gia tri don</th>
                                <th>Da thanh toan</th>
                                <th>Con lai</th>
                                <th>Han thanh toan</th>
                                <th>Trang thai cong no</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($debtOrders as $order)
                                @php
                                    $paid = (float) $order->transactions->where('type', 'payment')->sum('amount') - (float) $order->transactions->where('type', 'refund')->sum('amount');
                                    $remaining = max((float) $order->total - $paid, 0);
                                    $dueDate = optional($order->created_at)->copy()->addDays(30);
                                    $isOverdue = $remaining > 0 && $dueDate && $dueDate->isPast();
                                    $debtLabel = $remaining <= 0 ? 'Da thanh toan du' : ($isOverdue ? 'No qua han' : 'Con no');
                                    $debtClass = $remaining <= 0 ? 'success' : ($isOverdue ? 'danger' : 'warning');
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}" class="fw-semibold">{{ $order->code ?: ('#' . $order->id) }}</a>
                                    </td>
                                    <td>{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                                    <td class="text-success">{{ number_format($paid, 0, ',', '.') }} đ</td>
                                    <td class="{{ $remaining > 0 ? 'text-danger' : 'text-success' }} fw-semibold">{{ number_format($remaining, 0, ',', '.') }} đ</td>
                                    <td>{{ $dueDate ? $dueDate->format('d/m/Y') : '-' }}</td>
                                    <td><span class="badge bg-{{ $debtClass }}">{{ $debtLabel }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Khong co du lieu cong no trong khoang thoi gian da loc.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                {{ $debtOrders->links() }}
            </div>
        </div>
    @endif

    @if($activeTab === 'orders')
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Tong so don (bo loc hien tai)</div>
                        <div class="fs-5 fw-bold">{{ number_format($filteredOrderCount, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Tong gia tri don (bo loc hien tai)</div>
                        <div class="fs-5 fw-bold">{{ number_format((float) $filteredOrderTotal, 0, ',', '.') }} đ</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Danh sach don hang cua khach</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ma don hang</th>
                                <th>Ngay tao</th>
                                <th>Tong tien</th>
                                <th>Trang thai</th>
                                <th>Xem chi tiet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $statusClass = 'secondary';
                                    $status = (string) $order->status;
                                    if (in_array($status, ['completed', 'delivered'], true)) {
                                        $statusClass = 'success';
                                    } elseif (in_array($status, ['shipping', 'delivering', 'in_delivery'], true)) {
                                        $statusClass = 'info';
                                    } elseif (in_array($status, ['returned', 'returning', 'returned_completed'], true)) {
                                        $statusClass = 'dark';
                                    } elseif (in_array($status, ['cancelled', 'rejected'], true)) {
                                        $statusClass = 'danger';
                                    } elseif (in_array($status, ['pending', 'order_placed', 'approved', 'packing', 'ready_to_pack'], true)) {
                                        $statusClass = 'warning';
                                    }
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $order->code ?: ('#' . $order->id) }}</td>
                                    <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
                                    <td><span class="badge bg-{{ $statusClass }}">{{ $status }}</span></td>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Xem don</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Khong co don hang trong khoang loc.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                {{ $orders->links() }}
            </div>
        </div>
    @endif

    @if($activeTab === 'payments')
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Them thanh toan</div>
                    <div class="card-body">
                        <form action="{{ route('customers.payments.store', $customer) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="tab" value="payments">
                            <input type="hidden" name="period" value="{{ $period }}">
                            <input type="hidden" name="from_date" value="{{ $fromDate->toDateString() }}">
                            <input type="hidden" name="to_date" value="{{ $toDate->toDateString() }}">
                            <input type="hidden" name="order_status" value="{{ request('order_status') }}">
                            <input type="hidden" name="orders_per_page" value="{{ request('orders_per_page', 10) }}">
                            <input type="hidden" name="debt_per_page" value="{{ request('debt_per_page', 10) }}">
                            <input type="hidden" name="payments_per_page" value="{{ request('payments_per_page', 10) }}">

                            <div class="mb-3">
                                <label class="form-label">So tien</label>
                                <input type="number" name="amount" min="0.01" step="0.01" max="{{ max($totalOutstandingAmount, 0) }}" class="form-control" value="{{ old('amount') }}" required>
                                <div class="form-text">Gia tri > 0 va khong vuot qua cong no hien tai ({{ number_format($totalOutstandingAmount, 0, ',', '.') }} đ).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phuong thuc</label>
                                <select name="method" class="form-select" required>
                                    <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Tien mat</option>
                                    <option value="bank_transfer" {{ old('method') === 'bank_transfer' ? 'selected' : '' }}>Chuyen khoan</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chu</label>
                                <textarea name="note" rows="3" class="form-control">{{ old('note') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload chung tu (tuy chon)</label>
                                <input type="file" name="receipt_image" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Them thanh toan</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Lich su thanh toan</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ngay thanh toan</th>
                                        <th>Don hang</th>
                                        <th>So tien</th>
                                        <th>Phuong thuc</th>
                                        <th>Nguoi xac nhan</th>
                                        <th>Ghi chu</th>
                                        <th>Chung tu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments as $payment)
                                        @php
                                            $methodLabel = match($payment->method) {
                                                'cash' => 'Tien mat',
                                                'bank_transfer' => 'Chuyen khoan',
                                                default => $payment->method ?: '-',
                                            };
                                            $actorId = $transactionActorIds[$payment->id] ?? null;
                                            $actorName = $actorId ? ($actorNames[$actorId] ?? '-') : '-';
                                        @endphp
                                        <tr>
                                            <td>{{ optional($payment->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if($payment->order)
                                                    <a href="{{ route('orders.show', $payment->order) }}">{{ $payment->order->code ?: ('#' . $payment->order->id) }}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-success fw-semibold">{{ number_format((float) $payment->amount, 0, ',', '.') }} đ</td>
                                            <td>{{ $methodLabel }}</td>
                                            <td>{{ $actorName }}</td>
                                            <td>{{ $payment->note ?: '-' }}</td>
                                            <td>
                                                @if($payment->receipt_image_path)
                                                    <a target="_blank" href="{{ asset('storage/' . $payment->receipt_image_path) }}" class="btn btn-sm btn-outline-secondary">Xem</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">Chua co lich su thanh toan trong khoang loc.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'reports')
        @php
            $reportOrderCount = (int) $reportByMonth->sum('order_count');
            $reportSubtotalAmount = (float) $reportByMonth->sum('subtotal_amount');
            $reportItemDiscountAmount = (float) $reportByMonth->sum('item_discount_total');
            $reportExtraDiscountAmount = (float) $reportByMonth->sum('extra_discount_total');
            $reportOrderTotal = (float) $reportByMonth->sum('order_total');
            $reportOutstandingTotal = (float) $reportByMonth->sum('outstanding_total');
        @endphp

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Tien hang</div>
                        <div class="fs-5 fw-bold">{{ number_format($reportSubtotalAmount, 0, ',', '.') }} đ</div>
                        <div class="text-muted small">Tong truoc khi giam gia</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Tien giam</div>
                        <div class="fs-5 fw-bold">{{ number_format($reportItemDiscountAmount + $reportExtraDiscountAmount, 0, ',', '.') }} đ</div>
                        <div class="text-muted small">Discount + discount ngoai</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Tong tien cuoi</div>
                        <div class="fs-5 fw-bold">{{ number_format($reportOrderTotal, 0, ',', '.') }} đ</div>
                        <div class="text-muted small">Gia tri don sau giam gia</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Cong no theo thoi gian</div>
                        <div class="fs-5 fw-bold {{ $reportOutstandingTotal > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($reportOutstandingTotal, 0, ',', '.') }} đ</div>
                        <div class="text-muted small">Tong cong no cua cac thang</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Tong hop theo thang</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Thang</th>
                                <th>So don</th>
                                <th>Tien hang</th>
                                <th>Tien giam</th>
                                <th>Giam them</th>
                                <th>Tong gia tri don</th>
                                <th>Tong da thanh toan</th>
                                <th>Cong no</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportByMonth as $row)
                                <tr>
                                    <td>{{ $row['period'] ?: '-' }}</td>
                                    <td>{{ number_format((int) $row['order_count'], 0, ',', '.') }}</td>
                                    <td>{{ number_format((float) ($row['subtotal_amount'] ?? 0), 0, ',', '.') }} đ</td>
                                    <td>{{ number_format((float) ($row['item_discount_total'] ?? 0), 0, ',', '.') }} đ</td>
                                    <td>{{ number_format((float) ($row['extra_discount_total'] ?? 0), 0, ',', '.') }} đ</td>
                                    <td>{{ number_format((float) $row['order_total'], 0, ',', '.') }} đ</td>
                                    <td class="text-success">{{ number_format((float) $row['paid_total'], 0, ',', '.') }} đ</td>
                                    <td class="{{ (float) $row['outstanding_total'] > 0 ? 'text-danger' : 'text-success' }} fw-semibold">{{ number_format((float) $row['outstanding_total'], 0, ',', '.') }} đ</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Khong co du lieu bao cao.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function toggleDateRange(period) {
    const wrappers = document.querySelectorAll('.date-range-input');
    wrappers.forEach((item) => {
        item.style.display = period === 'custom' ? 'block' : 'none';
    });
}
</script>
@endsection
