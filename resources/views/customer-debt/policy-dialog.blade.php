<dialog id="debt-policy-dialog" aria-labelledby="debt-policy-title" style="width:min(550px,95vw);max-height:90vh;overflow:auto;border:1px solid #cbd5e1;border-radius:12px;padding:24px;">
    <div class="d-flex justify-content-between align-items-center mb-3"><h5 id="debt-policy-title" class="mb-0">Sửa nhanh tiêu chí công nợ</h5><button type="button" id="debt-policy-close" class="btn btn-sm btn-light">Đóng</button></div>
    <p id="debt-policy-name" class="fw-bold"></p>
    <form method="POST" action="{{ route('customer-debt.policy') }}">@csrf @method('PATCH')
        @if($policyFromReport ?? false)<input type="hidden" name="return_to_report" value="1">@endif
        <input type="hidden" id="debt-policy-key" name="source_key"><input type="hidden" id="debt-policy-version" name="policy_version">
        <label for="debt-policy-orders" class="form-label">Số Bill được phép nợ</label><input id="debt-policy-orders" name="allowed_orders" type="number" min="0" max="100000" step="1" class="form-control mb-3" placeholder="Mặc định: 1 Bill">
        <label for="debt-policy-limit" class="form-label">Trần công nợ (đ)</label><input id="debt-policy-limit" name="credit_limit" inputmode="numeric" class="form-control mb-3" placeholder="Mặc định: 0 đ">
        <label for="debt-policy-days" class="form-label">Ngày nợ (từ ngày giao hàng gần nhất)</label><input id="debt-policy-days" name="due_days" type="number" min="0" max="3650" step="1" class="form-control mb-3" placeholder="Mặc định: 1 ngày">
        <p class="small text-muted">Để trống để dùng mặc định: cho nợ 1 Bill, trần nợ 0 đ, hạn thanh toán 1 ngày kể từ ngày giao hàng gần nhất. Nhập hạn thanh toán 0 ngày nếu cần thanh toán trong ngày. Tiêu chí dùng chung cho mọi nguồn đã ghép. Nhân viên lưu một lần rồi khóa; chỉ admin được mở khóa.</p>
        <button type="submit" class="btn btn-primary">Lưu & cập nhật cảnh báo</button>
    </form>
</dialog>
@push('scripts')
<script src="{{ asset('js/customer-debt-policy.js') }}?v={{ filemtime(public_path('js/customer-debt-policy.js')) }}"></script>
@endpush
