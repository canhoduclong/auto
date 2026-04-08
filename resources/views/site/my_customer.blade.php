@extends('layouts.site')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Alerts -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Cảnh báo</h5>
                </div>
                <div class="card-body">
                    <!-- Upcoming Appointments -->
                    <h6 class="text-primary"><i class="bi bi-calendar-event"></i> Lịch hẹn sắp tới</h6>
                    @php
                        $upcomingAppointments = collect();
                        foreach($customers as $customer) {
                            if($customer->next_appointment && $customer->next_appointment->isFuture()) {
                                $upcomingAppointments->push($customer);
                            }
                        }
                        $upcomingAppointments = $upcomingAppointments->sortBy('next_appointment')->take(5);
                    @endphp
                    @if($upcomingAppointments->count() > 0)
                        @foreach($upcomingAppointments as $customer)
                            <div class="alert alert-info py-2 mb-2">
                                <small>
                                    <strong>{{ $customer->name }}</strong><br>
                                    {{ $customer->next_appointment->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted small">Không có lịch hẹn nào sắp tới</p>
                    @endif

                    <!-- Recent Notes -->
                    <h6 class="text-success mt-3"><i class="bi bi-sticky"></i> Ghi chú mới</h6>
                    @php
                        $recentNotes = collect();
                        foreach($customers as $customer) {
                            if($customer->note && $customer->updated_at->diffInDays(now()) <= 7) {
                                $recentNotes->push($customer);
                            }
                        }
                        $recentNotes = $recentNotes->sortByDesc('updated_at')->take(5);
                    @endphp
                    @if($recentNotes->count() > 0)
                        @foreach($recentNotes as $customer)
                            <div class="alert alert-success py-2 mb-2">
                                <small>
                                    <strong>{{ $customer->name }}</strong><br>
                                    {{ Str::limit($customer->note, 50) }}
                                </small>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted small">Không có ghi chú mới</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <h1>Khách hàng của bạn</h1>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <form id="bulkDeleteForm" action="{{ route('my_customer.bulk_delete') }}" method="POST">
                        @csrf
                        <div class="d-flex justify-content-between">
                            <span>Danh sách khách hàng</span>
                            <div>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">Thêm mới</button>
                                <button class="btn btn-danger btn-sm" id="bulkDeleteBtn">Xóa đã chọn</button>
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#importCustomerModal">Import</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Tên</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th>Công nợ</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="{{ $customer->id }}" class="customer-checkbox"></td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->email }}</td>
                                    <td>{{ $customer->phone }}</td>
                                    <td>
                                        @php
                                            $debt = $customer->orders()->where('amount_due', '>', 0)->sum('amount_due');
                                        @endphp
                                        @if($debt > 0)
                                            <span class="badge bg-danger">{{ number_format($debt, 0, ',', '.') }} đ</span>
                                        @else
                                            <span class="badge bg-success">Không nợ</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCustomerModal-{{ $customer->id }}">Sửa</button>
                                        <form action="{{ route('my_customer.destroy', $customer) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            
            {{ $customers->links() }}
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
@include('site.partials.add_customer_modal')

<!-- Edit Customer Modals -->
@foreach($customers as $customer)
    @include('site.partials.edit_customer_modal', ['customer' => $customer])
@endforeach

<!-- Import Customer Modal -->
@include('site.partials.import_customer_modal')

@endsection

@push('scripts')
<script>
    document.getElementById('selectAll').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.customer-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to delete selected customers?')) {
            document.getElementById('bulkDeleteForm').submit();
        }
    });
</script>
@endpush