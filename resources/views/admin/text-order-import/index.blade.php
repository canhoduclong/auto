@extends('layouts.app')

@section('title', 'Nhập đơn text')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Nhập đơn text từ Zalo</h3>
            <div class="text-muted">Dán nội dung Zalo, kiểm tra bản nháp rồi xác nhận tạo đơn.</div>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cập nhật tên Zalo sale</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.text-order-import.parse') }}">
                @csrf
                <label class="form-label fw-semibold">Nội dung copy từ Zalo</label>
                <textarea name="text" rows="12" class="form-control" required placeholder="[14/06/2026 10:45:10] Tên Zalo sale: KH: ...">{{ old('text') }}</textarea>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">Tin nhắn ghi chú cùng sale sẽ được nối vào đơn gần nhất. Danh thiếp, hình ảnh và tin thu hồi được bỏ qua.</small>
                    <button class="btn btn-primary"><i class="ph-magic-wand me-1"></i>Phân tích thành bản nháp</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Đơn nháp gần nhất</strong>
            <button type="button" class="btn btn-success btn-sm" id="bulk-confirm"><i class="ph-checks me-1"></i>Xác nhận đã chọn</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" id="check-all"></th>
                        <th>Sale / Khách hàng</th>
                        <th>Sản phẩm</th>
                        <th>SL / Size / Giá</th>
                        <th>Ngày / Giờ giao</th>
                        <th>Ghi chú gốc</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($drafts as $draft)
                    <tr data-draft-id="{{ $draft->id }}">
                        <td><input type="checkbox" class="draft-check" value="{{ $draft->id }}" @disabled($draft->status === 'confirmed')></td>
                        <td style="min-width:240px">
                            <select name="sale_id" class="form-select form-select-sm mb-1 {{ $draft->sale_id ? '' : 'is-invalid' }}">
                                <option value="">Sale: {{ $draft->zalo_name ?: 'chưa nhận diện' }}</option>
                                @foreach($sales as $sale)<option value="{{ $sale->id }}" @selected($draft->sale_id === $sale->id)>{{ $sale->name }}{{ $sale->zalo_name ? ' · '.$sale->zalo_name : '' }}</option>@endforeach
                            </select>
                            <input name="customer_name" value="{{ $draft->customer_name }}" class="form-control form-control-sm mb-1" placeholder="Tên khách hàng">
                            <input name="phone" value="{{ $draft->phone }}" class="form-control form-control-sm mb-1" placeholder="Số điện thoại">
                            <input name="address" value="{{ $draft->address }}" class="form-control form-control-sm" placeholder="Địa chỉ">
                            @if($draft->customer)<small class="text-success">Khớp KH: {{ $draft->customer->name }}</small>@else<small class="text-warning">Sẽ tạo khách mới khi xác nhận</small>@endif
                        </td>
                        <td style="min-width:230px">
                            @foreach(($draft->parsed_items ?: [['product_text' => $draft->product_text, 'product_variant_id' => $draft->product_variant_id, 'quantity' => $draft->quantity, 'size_kg' => $draft->size_kg, 'unit_price' => $draft->unit_price]]) as $itemIndex => $item)
                                <div class="border rounded p-1 mb-1 draft-item" data-item-index="{{ $itemIndex }}">
                                    <input type="hidden" name="item_product_text" value="{{ $item['product_text'] ?? '' }}">
                                    <div class="small text-muted mb-1">{{ $item['product_text'] ?? 'Không đọc được tên sản phẩm' }}</div>
                                    <select name="item_product_variant_id" class="form-select form-select-sm {{ empty($item['product_variant_id']) ? 'is-invalid' : '' }}">
                                        <option value="">Chọn biến thể</option>
                                        @foreach($variants as $variant)
                                            <option value="{{ $variant->id }}" @selected((int)($item['product_variant_id'] ?? 0) === $variant->id)>{{ $variant->product?->name }} · {{ $variant->name ?: $variant->sku }} · {{ $variant->size }}</option>
                                        @endforeach
                                    </select>
                                    <div class="row g-1 mt-1">
                                        <div class="col"><input type="number" name="item_quantity" value="{{ $item['quantity'] ?? '' }}" min="1" class="form-control form-control-sm" placeholder="SL"></div>
                                        <div class="col"><input type="number" step=".001" name="item_size_kg" value="{{ $item['size_kg'] ?? '' }}" class="form-control form-control-sm" placeholder="Kg"></div>
                                        <div class="col"><input type="number" step="1" name="item_unit_price" value="{{ $item['unit_price'] ?? '' }}" class="form-control form-control-sm" placeholder="Giá"></div>
                                    </div>
                                </div>
                            @endforeach
                        </td>
                        <td style="min-width:130px">
                            <div class="small text-muted">Thông tin SL / size / giá được chỉnh theo từng sản phẩm ở cột bên trái.</div>
                        </td>
                        <td style="min-width:150px">
                            <input type="date" name="delivery_date" value="{{ optional($draft->delivery_date)->toDateString() }}" class="form-control form-control-sm mb-1">
                            <input name="delivery_time" value="{{ $draft->delivery_time }}" class="form-control form-control-sm" placeholder="Giờ giao">
                        </td>
                        <td style="min-width:280px">
                            <textarea name="note" rows="7" class="form-control form-control-sm">{{ $draft->note }}</textarea>
                        </td>
                        <td>
                            @if($draft->status === 'confirmed')
                                <span class="badge bg-success">Đã tạo {{ $draft->order?->code }}</span>
                            @elseif($draft->status === 'error')
                                <span class="badge bg-danger">Lỗi</span><div class="small text-danger mt-1">{{ $draft->error_message }}</div>
                            @else
                                <span class="badge bg-warning text-dark">Bản nháp</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm js-confirm-draft" @disabled($draft->status === 'confirmed')>Xác nhận</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">Chưa có bản nháp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const fields = ['sale_id','customer_name','phone','address','delivery_date','delivery_time','note'];
    const notify = (message, error = false) => {
        const alert = document.createElement('div');
        alert.className = `alert ${error ? 'alert-danger' : 'alert-success'} position-fixed top-0 end-0 m-3 shadow`;
        alert.style.zIndex = 2000; alert.textContent = message; document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    };
    const rowData = row => {
        const data = Object.fromEntries(fields.map(name => [name, row.querySelector(`[name="${name}"]`)?.value || null]));
        data.items = [...row.querySelectorAll('.draft-item')].map(item => ({
            product_text: item.querySelector('[name="item_product_text"]').value || null,
            product_variant_id: item.querySelector('[name="item_product_variant_id"]').value || null,
            quantity: item.querySelector('[name="item_quantity"]').value || null,
            size_kg: item.querySelector('[name="item_size_kg"]').value || null,
            unit_price: item.querySelector('[name="item_unit_price"]').value || null,
        }));
        return data;
    };
    async function confirmRow(row) {
        const button = row.querySelector('.js-confirm-draft'); button.disabled = true;
        const response = await fetch(`{{ url('admin/text-order-import') }}/${row.dataset.draftId}/confirm`, {
            method: 'POST', headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
            body: JSON.stringify(rowData(row))
        });
        const payload = await response.json();
        if (!response.ok) { button.disabled = false; throw new Error(payload.message || 'Không thể xác nhận đơn'); }
        row.querySelector('.draft-check').disabled = true;
        row.querySelector('td:nth-last-child(2)').innerHTML = '<span class="badge bg-success">Đã tạo đơn</span>';
        notify(payload.message);
    }
    document.querySelectorAll('.js-confirm-draft').forEach(button => button.addEventListener('click', async () => {
        try { await confirmRow(button.closest('tr')); } catch (error) { notify(error.message, true); }
    }));
    document.getElementById('check-all')?.addEventListener('change', event => document.querySelectorAll('.draft-check:not(:disabled)').forEach(input => input.checked = event.target.checked));
    document.getElementById('bulk-confirm')?.addEventListener('click', async () => {
        const rows = [...document.querySelectorAll('.draft-check:checked')].map(input => input.closest('tr'));
        if (!rows.length) return notify('Chưa chọn đơn nháp.', true);
        for (const row of rows) {
            try { await confirmRow(row); } catch (error) { notify(`#${row.dataset.draftId}: ${error.message}`, true); }
        }
    });
});
</script>
@endpush
@endsection
