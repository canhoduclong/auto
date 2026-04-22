@extends('layouts.site')

@push('styles')
<style>
    .mci-page {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 132, 0.16), transparent 34%),
            linear-gradient(180deg, #f8f5ef 0%, #ffffff 36%, #f6f7fb 100%);
        padding: 42px 0 72px;
    }
    .mci-hero {
        border: 1px solid rgba(41, 52, 98, 0.08);
        border-radius: 24px;
        background: linear-gradient(135deg, #152238 0%, #23385f 55%, #39598a 100%);
        color: #fff;
        padding: 24px;
        box-shadow: 0 20px 52px rgba(21, 34, 56, 0.16);
    }
    .mci-hero h1 {
        font-size: 1.6rem;
        margin-bottom: 6px;
        font-weight: 800;
    }
    .mci-hero p {
        margin: 0;
        color: rgba(255, 255, 255, 0.8);
    }
    .mci-kpi {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 14px;
        padding: 12px 14px;
    }
    .mci-kpi .label {
        display: block;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: rgba(255, 255, 255, 0.74);
        margin-bottom: 2px;
    }
    .mci-kpi .value {
        font-size: 1.25rem;
        font-weight: 800;
    }
    .mci-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
        height: 100%;
    }
    .mci-panel-head {
        padding: 16px 20px;
        border-bottom: 1px solid #edf2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }
    .mci-panel-title {
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
        color: #1e293b;
    }
    .mci-panel-body {
        padding: 18px 20px 20px;
    }
    .mci-upload-hint {
        font-size: .84rem;
        color: #64748b;
    }
    .mci-result-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .mci-result-tabs .nav-link {
        border-radius: 999px;
        border: 1px solid #dbe4ef;
        color: #334155;
        font-weight: 700;
        font-size: .82rem;
        padding: 7px 12px;
    }
    .mci-result-tabs .nav-link.active {
        background: #e0e7ff;
        border-color: #a5b4fc;
        color: #3730a3;
    }
    .mci-empty {
        text-align: center;
        padding: 30px 18px;
        color: #64748b;
    }
    .mci-empty i {
        font-size: 2.2rem;
        opacity: .7;
    }
    .mci-customer-card {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: box-shadow .2s ease;
    }
    .mci-customer-card:hover {
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.12);
    }
    .mci-meta-muted {
        font-size: .83rem;
        color: #64748b;
    }
    .mci-row-card {
        border: 1px dashed #d7dee8;
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 10px;
        background: #fcfdff;
    }
    .mci-row-card.duplicate {
        border-color: #f5c46a;
        background: #fffaf0;
    }
    .mci-row-card.failed {
        border-color: #f3a6a6;
        background: #fff5f5;
    }
    .mci-row-title {
        font-size: .9rem;
        font-weight: 700;
        margin-bottom: 6px;
        color: #1e293b;
    }
    .mci-row-error {
        font-size: .82rem;
        color: #b91c1c;
        margin: 0;
    }
    @media (max-width: 991px) {
        .mci-page {
            padding: 30px 0 50px;
        }
    }
</style>
@endpush

@section('content')
@php
    $importResult = $importResult ?? [
        'imported_count' => 0,
        'duplicate_count' => 0,
        'failed_count' => 0,
        'duplicate_rows' => [],
        'failed_rows' => [],
    ];
    $importedCustomers = $importedCustomers ?? collect();

    $extractValue = function (array $values, array $keys) {
        foreach ($keys as $key) {
            if (array_key_exists($key, $values) && !is_null($values[$key]) && $values[$key] !== '') {
                return $values[$key];
            }
        }

        return null;
    };

    $hasResult = (int) ($importResult['imported_count'] ?? 0) > 0
        || (int) ($importResult['duplicate_count'] ?? 0) > 0
        || (int) ($importResult['failed_count'] ?? 0) > 0;
@endphp

<section class="mci-page">
    <div class="container">
        <div class="mci-hero mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-7">
                    <h1>Import khách hàng</h1>
                    <p>Upload file bên trái, theo dõi khách hàng đã import, trùng và lỗi bên phải.</p>
                </div>
                <div class="col-lg-5">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="mci-kpi">
                                <span class="label">Đã import</span>
                                <span class="value">{{ (int) ($importResult['imported_count'] ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mci-kpi">
                                <span class="label">Trùng</span>
                                <span class="value">{{ (int) ($importResult['duplicate_count'] ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mci-kpi">
                                <span class="label">Lỗi khác</span>
                                <span class="value">{{ (int) ($importResult['failed_count'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-stretch">
            <div class="col-lg-4">
                <div class="mci-panel">
                    <div class="mci-panel-head">
                        <h2 class="mci-panel-title mb-0">File import</h2>
                    </div>
                    <div class="mci-panel-body">
                        <form action="{{ route('my_customer.import') }}" method="POST" enctype="multipart/form-data" class="d-grid gap-3">
                            @csrf
                            <div>
                                <label for="file" class="form-label fw-semibold">Chọn file (.xlsx, .xls, .csv)</label>
                                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                                @error('file')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-1"></i>Import ngay
                            </button>
                            <a href="{{ asset('sample/customer_import_template.xlsx') }}" class="btn btn-outline-info" download>
                                <i class="bi bi-file-earmark-arrow-down me-1"></i>Tải file mẫu
                            </a>
                            <a href="{{ route('pages.my_customer') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách
                            </a>
                        </form>

                        <hr>
                        <div class="mci-upload-hint">
                            <div class="fw-semibold mb-1">Gợi ý định dạng cột:</div>
                            <div>- Tên khách hàng / name</div>
                            <div>- Số điện thoại / phone</div>
                            <div>- Email (không bắt buộc, nhưng nếu có phải không trùng)</div>
                            <div>- Địa chỉ / address</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="mci-panel">
                    <div class="mci-panel-head">
                        <h2 class="mci-panel-title mb-0">Kết quả import</h2>
                        @if($hasResult)
                            <span class="badge bg-success">Đã cập nhật</span>
                        @endif
                    </div>
                    <div class="mci-panel-body">
                        @if(!$hasResult)
                            <div class="mci-empty">
                                <i class="bi bi-people"></i>
                                <div class="mt-2 fw-semibold">Chưa có dữ liệu import gần nhất</div>
                                <div class="small">Upload file ở cột bên trái để hiển thị danh sách khách hàng đã import và các dòng trùng/lỗi.</div>
                            </div>
                        @else
                            <ul class="nav nav-pills mci-result-tabs" id="mciResultTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="mci-imported-tab" data-bs-toggle="pill" data-bs-target="#mci-imported" type="button" role="tab" aria-controls="mci-imported" aria-selected="true">
                                        Đã import ({{ $importedCustomers->count() }})
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="mci-duplicate-tab" data-bs-toggle="pill" data-bs-target="#mci-duplicate" type="button" role="tab" aria-controls="mci-duplicate" aria-selected="false">
                                        Trùng ({{ (int) ($importResult['duplicate_count'] ?? 0) }})
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="mci-failed-tab" data-bs-toggle="pill" data-bs-target="#mci-failed" type="button" role="tab" aria-controls="mci-failed" aria-selected="false">
                                        Lỗi khác ({{ (int) ($importResult['failed_count'] ?? 0) }})
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="mciResultTabsContent">
                                <div class="tab-pane fade show active" id="mci-imported" role="tabpanel" aria-labelledby="mci-imported-tab" tabindex="0">
                                    @if($importedCustomers->isEmpty())
                                        <div class="mci-empty">
                                            <i class="bi bi-inbox"></i>
                                            <div class="mt-2">Không có khách hàng mới trong lần import này.</div>
                                        </div>
                                    @else
                                        <div class="row g-3">
                                            @foreach($importedCustomers as $customer)
                                                @php
                                                    $addressText = $customer->address ?: '';
                                                    if (!$addressText && $customer->addresses->first()) {
                                                        $address = $customer->addresses->first();
                                                        $parts = array_filter([$address->house_number, $address->street, $address->ward, $address->city]);
                                                        $addressText = implode(', ', $parts);
                                                    }
                                                @endphp
                                                <div class="col-12">
                                                    <div class="mci-customer-card border rounded p-3 bg-white">
                                                        <div class="row justify-content-between">
                                                            <div class="col-md-7">
                                                                <h6 class="mb-1 fw-bold fs-5">{{ $customer->name }}</h6>
                                                                @if($customer->phone)
                                                                    <div class="mci-meta-muted"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</div>
                                                                @endif
                                                                @if($customer->email)
                                                                    <div class="mci-meta-muted"><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</div>
                                                                @endif
                                                                @if($addressText)
                                                                    <div class="mci-meta-muted"><i class="bi bi-geo-alt me-1"></i>{{ $addressText }}</div>
                                                                @endif
                                                            </div>
                                                            <div class="col-md-5 text-md-end mt-2 mt-md-0">
                                                                <div class="mci-meta-muted">Mã KH: <strong>{{ $customer->customer_code ?: '#'.$customer->id }}</strong></div>
                                                                <div class="mci-meta-muted">Đơn: <strong>{{ $customer->orders_count }}</strong></div>
                                                                <div class="mci-meta-muted">Công nợ: <strong>{{ number_format($customer->total_debt ?? 0, 0, ',', '.') }} đ</strong></div>
                                                                <div class="mt-2">
                                                                    <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-outline-info btn-sm">
                                                                        <i class="bi bi-eye me-1"></i>Xem chi tiết
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="tab-pane fade" id="mci-duplicate" role="tabpanel" aria-labelledby="mci-duplicate-tab" tabindex="0">
                                    @if(empty($importResult['duplicate_rows']))
                                        <div class="mci-empty">
                                            <i class="bi bi-check2-circle"></i>
                                            <div class="mt-2">Không có dòng trùng.</div>
                                        </div>
                                    @else
                                        @foreach($importResult['duplicate_rows'] as $row)
                                            @php
                                                $values = (array) ($row['values'] ?? []);
                                                $name = $extractValue($values, ['tên khách hàng', 'name']) ?? 'Không rõ tên';
                                                $phone = $extractValue($values, ['số điện thoại', 'phone']);
                                                $email = $extractValue($values, ['email']);
                                                $address = $extractValue($values, ['địa chỉ', 'address']);
                                            @endphp
                                            <div class="mci-row-card duplicate">
                                                <div class="mci-row-title">Dòng {{ $row['row'] ?? '-' }}: {{ $name }}</div>
                                                <div class="mci-meta-muted">{{ $phone ?: 'Chưa có SĐT' }} @if($email) · {{ $email }} @endif</div>
                                                @if($address)
                                                    <div class="mci-meta-muted">{{ $address }}</div>
                                                @endif
                                                <p class="mci-row-error mt-2 mb-0">{{ collect($row['errors'] ?? [])->implode('; ') }}</p>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <div class="tab-pane fade" id="mci-failed" role="tabpanel" aria-labelledby="mci-failed-tab" tabindex="0">
                                    @if(empty($importResult['failed_rows']))
                                        <div class="mci-empty">
                                            <i class="bi bi-check2-all"></i>
                                            <div class="mt-2">Không có lỗi khác.</div>
                                        </div>
                                    @else
                                        @foreach($importResult['failed_rows'] as $row)
                                            @php
                                                $values = (array) ($row['values'] ?? []);
                                                $name = $extractValue($values, ['tên khách hàng', 'name']) ?? 'Không rõ tên';
                                                $phone = $extractValue($values, ['số điện thoại', 'phone']);
                                                $email = $extractValue($values, ['email']);
                                                $address = $extractValue($values, ['địa chỉ', 'address']);
                                            @endphp
                                            <div class="mci-row-card failed">
                                                <div class="mci-row-title">Dòng {{ $row['row'] ?? '-' }}: {{ $name }}</div>
                                                <div class="mci-meta-muted">{{ $phone ?: 'Chưa có SĐT' }} @if($email) · {{ $email }} @endif</div>
                                                @if($address)
                                                    <div class="mci-meta-muted">{{ $address }}</div>
                                                @endif
                                                <p class="mci-row-error mt-2 mb-0">{{ collect($row['errors'] ?? [])->implode('; ') }}</p>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
