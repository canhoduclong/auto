@extends('layouts.site')

@section('content')
<section class="py-4">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h3 class="mb-1">Chi tiet yeu cau dieu chinh #{{ $adjustment->id }}</h3>
                <div class="text-muted">Don goc: {{ $adjustment->order?->code ?: ('#' . $adjustment->order_id) }}</div>
            </div>
            <a href="{{ route('site.orders.show', $adjustment->order_id) }}" class="btn btn-outline-secondary btn-sm">Quay lai chi tiet don</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small">Trang thai</div>
                        <div class="fw-semibold">{{ strtoupper($adjustment->status) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Workflow</div>
                        <div class="fw-semibold">{{ $adjustment->workflow_code }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Nguoi yeu cau</div>
                        <div class="fw-semibold">{{ $adjustment->requester?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Kho tra hang</div>
                        <div class="fw-semibold">{{ $adjustment->returnWarehouse?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-12">
                        <div class="text-muted small">Ghi chu dieu chinh</div>
                        <div>{{ $adjustment->adjustment_note ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Du lieu dieu chinh theo tung san pham</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>San pham</th>
                                <th>SL goc</th>
                                <th>SL dieu chinh</th>
                                <th>Gia goc</th>
                                <th>Gia dieu chinh</th>
                                <th>Can goc</th>
                                <th>Can dieu chinh</th>
                                <th>Can kho xac nhan</th>
                                <th>Tinh trang hang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($adjustment->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->variant?->product?->name ?? 'San pham' }}
                                        <div class="text-muted small">{{ $item->variant?->name ?? '-' }}</div>
                                    </td>
                                    <td>{{ (int) $item->original_quantity }}</td>
                                    <td>{{ (int) $item->adjusted_quantity }}</td>
                                    <td>{{ number_format((float) $item->original_price, 0, ',', '.') }} đ</td>
                                    <td>{{ number_format((float) $item->adjusted_price, 0, ',', '.') }} đ</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $item->original_weight, 3, '.', ''), '0'), '.') }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $item->adjusted_weight, 3, '.', ''), '0'), '.') }}</td>
                                    <td>
                                        {{ is_null($item->warehouse_received_quantity) ? '-' : (int) $item->warehouse_received_quantity }}
                                        @if(!is_null($item->warehouse_received_weight))
                                            <div class="text-muted small">{{ rtrim(rtrim(number_format((float) $item->warehouse_received_weight, 3, '.', ''), '0'), '.') }} kg</div>
                                        @endif
                                    </td>
                                    <td>{{ $item->warehouse_condition ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($canApprove)
            <div class="card mb-3">
                <div class="card-header">Duyet yeu cau dieu chinh</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('site.order-adjustments.approve', $adjustment) }}">
                            @csrf
                            <input type="hidden" name="note" value="Duyet yeu cau dieu chinh tu trang chi tiet">
                            <button type="submit" class="btn btn-success">Duyet</button>
                        </form>
                        <form method="POST" action="{{ route('site.order-adjustments.reject', $adjustment) }}" class="d-flex gap-2">
                            @csrf
                            <input type="text" name="reason" class="form-control" placeholder="Ly do tu choi" required>
                            <button type="submit" class="btn btn-danger">Tu choi</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($canWarehouseConfirm)
            <div class="card mb-3">
                <div class="card-header">Xac nhan hang tra tu kho</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('site.order-adjustments.warehouse-confirm', $adjustment) }}">
                        @csrf
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>San pham</th>
                                        <th>SL giam</th>
                                        <th>SL kho nhan</th>
                                        <th>Can kho nhan (kg)</th>
                                        <th>Tinh trang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($adjustment->items as $idx => $item)
                                        @php
                                            $decrease = max((int) $item->original_quantity - (int) $item->adjusted_quantity, 0);
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ $item->variant?->product?->name ?? 'San pham' }}
                                                <div class="text-muted small">{{ $item->variant?->name ?? '-' }}</div>
                                            </td>
                                            <td>{{ $decrease }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max="{{ $decrease }}"
                                                    name="items[{{ $idx }}][warehouse_received_quantity]"
                                                    class="form-control"
                                                    value="{{ old('items.' . $idx . '.warehouse_received_quantity', $decrease) }}"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="0.001"
                                                    name="items[{{ $idx }}][warehouse_received_weight]"
                                                    class="form-control"
                                                    value="{{ old('items.' . $idx . '.warehouse_received_weight') }}"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    name="items[{{ $idx }}][warehouse_condition]"
                                                    class="form-control"
                                                    value="{{ old('items.' . $idx . '.warehouse_condition') }}"
                                                    placeholder="Du chat luong / hu hong..."
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chu kho</label>
                            <textarea name="note" class="form-control" rows="2">{{ old('note') }}</textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" name="mode" value="confirm_full" class="btn btn-success">Xac nhan du</button>
                            <button type="submit" name="mode" value="confirm_partial" class="btn btn-warning text-dark">Xac nhan thieu</button>
                            <button type="submit" name="mode" value="reject" class="btn btn-danger" onclick="return confirm('Xac nhan tu choi nhan hang tra?');">Tu choi nhan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
