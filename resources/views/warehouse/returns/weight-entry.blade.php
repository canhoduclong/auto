@extends('layouts.warehouse')

@section('title', 'Cân nặng lại hàng trả về')
@section('subtitle', 'Nhập khối lượng được cân lại để tính hao hụt')

@section('content')
@php
    $returnItems = $orderReturn?->returnItems ?? collect();
    $totalOriginalWeight = (float) $returnItems->sum(function ($returnItem) {
        return (float) ($returnItem->original_weight ?? 0);
    });
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="badge bg-warning rounded-pill"><i class="bi bi-speedometer2 me-1"></i>{{ $returnItems->count() }} sản phẩm cần cân</span>
        <span class="badge bg-info rounded-pill ms-2">Đơn: <strong>{{ $order->code }}</strong></span>
    </div>
    <a href="{{ route('warehouse.returns') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Quay lại
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning bg-opacity-10 border-bottom-1 py-3">
                <h6 class="mb-0">
                    <i class="bi bi-box-seam me-2"></i>
                    Cân nặng lại sản phẩm trả về
                </h6>
            </div>

            <div class="card-body p-0">
                <form action="{{ route('warehouse.returns.save-weights', $order) }}" method="POST" id="weight-form">
                    @csrf
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Sản phẩm</th>
                                    <th style="width:80px">SL</th>
                                    <th style="width:120px" class="text-center">KL gốc (kg)</th>
                                    <th style="width:120px" class="text-center">
                                        <span class="text-danger">*</span>KL thực tế (kg)
                                    </th>
                                    <th style="width:120px" class="text-center">Hao hụt (kg)</th>
                                    <th style="width:80px" class="text-center">% Hao</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returnItems as $idx => $returnItem)
                                @php
                                    $variant = $returnItem->productVariant;
                                    $product = $variant?->product;
                                    $originalWeight = (float) ($returnItem->original_weight ?? 0);
                                    $receivedWeight = $returnItem?->received_weight;
                                    $hasRecordedWeight = $receivedWeight !== null;
                                @endphp
                                <tr id="item-row-{{ $returnItem->id }}" class="@if($hasRecordedWeight) table-success table-success-opacity-10 @endif">
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $product?->name ?? '—' }}</div>
                                        @if($variant && $variant->name)
                                            <div class="text-muted" style="font-size:.75rem;">{{ $variant->name }}</div>
                                        @endif
                                        <div class="text-muted" style="font-size:.7rem;">
                                            SKU: <span class="font-monospace">{{ $variant?->sku ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-semibold">{{ (int) $returnItem->quantity }}</td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ number_format($originalWeight, 3) }}</span>
                                    </td>
                                    <td>
                                        <input type="hidden" name="item_weights[{{ $idx }}][item_id]" value="{{ $returnItem->id }}">
                                        <input 
                                            type="number" 
                                            step="0.001"
                                            min="0"
                                            name="item_weights[{{ $idx }}][received_weight]"
                                            class="form-control form-control-sm weight-input text-center"
                                            data-original="{{ $originalWeight }}"
                                            data-idx="{{ $idx }}"
                                            placeholder="0.000"
                                            value="{{ $receivedWeight ?? '' }}"
                                            required
                                        >
                                        @error("item_weights.$idx.received_weight")
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-center">
                                        <span class="weight-loss-display fw-semibold text-danger" data-idx="{{ $idx }}">
                                            {{ $returnItem->weight_loss ? number_format((float) $returnItem->weight_loss, 3) : '—' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="weight-loss-percent badge bg-danger bg-opacity-10 text-danger" data-idx="{{ $idx }}">
                                            {{ $returnItem->weight_loss ? number_format((float) $returnItem->weight_loss_percentage, 1) : '—' }}%
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Không có sản phẩm trong đơn trả
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Tổng cộng</th>
                                    <th class="text-center">
                                        <span class="fw-semibold" id="total-original">{{ number_format($totalOriginalWeight, 3) }}</span> kg
                                    </th>
                                    <th class="text-center">
                                        <span class="fw-semibold text-primary" id="total-received">0.000</span> kg
                                    </th>
                                    <th class="text-center">
                                        <span class="fw-semibold text-danger" id="total-loss">0.000</span> kg
                                    </th>
                                    <th class="text-center">
                                        <span class="badge bg-danger bg-opacity-10 text-danger" id="total-loss-percent">0.0%</span>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="p-3 border-top d-flex gap-2 justify-content-between">
                        <a href="{{ route('warehouse.returns') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Hủy
                        </a>
                        <div>
                            <button type="button" class="btn btn-outline-info me-2" onclick="resetForm()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Làm lại
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i>Xác nhận Trả Hàng
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Thông tin chi tiết đơn hàng --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Thông tin đơn hàng</h6>
            </div>
            <div class="card-body small">
                <div class="mb-2">
                    <span class="text-muted">Mã đơn:</span>
                    <strong class="font-monospace">{{ $order->code }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted">Khách hàng:</span>
                    <strong>{{ $order->customer?->name ?? '—' }}</strong>
                </div>
                @if($order->customer?->phone)
                <div class="mb-2">
                    <span class="text-muted">SĐT:</span>
                    <strong>{{ $order->customer->phone }}</strong>
                </div>
                @endif
                <div class="mb-2">
                    <span class="text-muted">Shipper:</span>
                    <strong>{{ $order->shipper?->name ?? '—' }}</strong>
                </div>
                <hr>
                <div class="mb-2">
                    <span class="text-muted">Kho nhận:</span>
                    <strong>{{ $resolvedReturnWarehouse?->name ?? 'Chưa xác định' }}</strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted">Lý do trả:</span>
                    <div class="mt-1">
                        @php
                            $reasons = [
                                'customer_refused'   => ['Khách từ chối', 'danger'],
                                'no_contact'         => ['Không liên lạc được', 'warning'],
                                'wrong_address'      => ['Sai địa chỉ', 'info'],
                                'damaged'            => ['Hàng bị hỏng', 'danger'],
                            ];
                            $reason = $reasons[$order->return_reason] ?? [ucfirst($order->return_reason ?? 'Không xác định'), 'secondary'];
                        @endphp
                        <span class="badge bg-{{ $reason[1] }} bg-opacity-10 text-{{ $reason[1] }} border border-{{ $reason[1] }} border-opacity-25">
                            {{ $reason[0] }}
                        </span>
                    </div>
                </div>
                @if($order->shipper_note)
                <div class="mb-2">
                    <span class="text-muted d-block mb-1">Ghi chú:</span>
                    <em class="text-muted">{{ $order->shipper_note }}</em>
                </div>
                @endif
                <hr>
                <div class="mb-2">
                    <span class="text-muted">Ngày cập nhật:</span>
                    <strong>{{ $order->updated_at->format('d/m/Y H:i') }}</strong>
                </div>
                <div class="mb-0">
                    <span class="text-muted">Tổng giá trị:</span>
                    <strong class="text-danger">{{ number_format($order->total) }}đ</strong>
                </div>
            </div>
        </div>

        {{-- Hướng dẫn --}}
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-header bg-info bg-opacity-10 py-2">
                <h6 class="mb-0"><i class="bi bi-question-circle me-2"></i>Hướng dẫn</h6>
            </div>
            <div class="card-body small">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Cân từng sản phẩm trả về</li>
                    <li class="mb-2">Nhập khối lượng thực tế vào cột "KL thực tế"</li>
                    <li class="mb-2">Hệ thống sẽ tự động tính hao hụt</li>
                    <li>Nhấn "Xác nhận Trả Hàng" để hoàn tất nhập kho hàng trả</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
const weightInputs = document.querySelectorAll('.weight-input');
const totalOriginal = parseFloat(document.getElementById('total-original').textContent.replace(/[.,]/g, match => match === ',' ? '.' : ''));

function calculateWeightLoss(idx, receivedWeight) {
    const originalStr = weightInputs[idx].dataset.original;
    const original = parseFloat(originalStr);
    const received = parseFloat(receivedWeight) || 0;
    
    const loss = Math.max(0, original - received);
    const lossPercent = original > 0 ? (loss / original) * 100 : 0;
    
    return { loss, lossPercent };
}

function updateTotals() {
    let totalReceived = 0;
    let totalLoss = 0;
    
    weightInputs.forEach((input, idx) => {
        const receivedWeight = parseFloat(input.value) || 0;
        const { loss } = calculateWeightLoss(idx, receivedWeight);
        const { lossPercent } = calculateWeightLoss(idx, receivedWeight);
        
        // Update loss display
        document.querySelector(`[data-idx="${idx}"].weight-loss-display`).textContent = 
            loss > 0 ? loss.toFixed(3) : '—';
        document.querySelector(`[data-idx="${idx}"].weight-loss-percent`).textContent = 
            loss > 0 ? lossPercent.toFixed(1) + '%' : '—';
        
        totalReceived += receivedWeight;
        totalLoss += loss;
    });
    
    const totalLossPercent = totalOriginal > 0 ? (totalLoss / totalOriginal) * 100 : 0;
    
    document.getElementById('total-received').textContent = totalReceived.toFixed(3);
    document.getElementById('total-loss').textContent = totalLoss.toFixed(3);
    document.getElementById('total-loss-percent').textContent = totalLossPercent.toFixed(1) + '%';
}

weightInputs.forEach((input) => {
    input.addEventListener('input', updateTotals);
});

function resetForm() {
    if (confirm('Xóa tất cả dữ liệu đã nhập?')) {
        weightInputs.forEach(input => {
            if (!input.hasAttribute('readonly')) {
                input.value = '';
            }
        });
        updateTotals();
    }
}

// Initialize totals on page load
updateTotals();

// Form submission with validation
document.getElementById('weight-form').addEventListener('submit', function(e) {
    let hasValues = false;
    weightInputs.forEach(input => {
        if (input.value) {
            hasValues = true;
        }
    });
    
    if (!hasValues) {
        e.preventDefault();
        alert('Vui lòng nhập ít nhất một khối lượng sản phẩm');
        return false;
    }
    
    return confirm('Xác nhận trả hàng và nhập kho với dữ liệu cân nặng này?');
});
</script>

<style>
.table-success-opacity-10 {
    background-color: rgba(198, 255, 219, 0.2);
}

.weight-input:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}
</style>
@endsection
