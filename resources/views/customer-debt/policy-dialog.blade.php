<dialog id="debt-policy-dialog" aria-labelledby="debt-policy-title" style="width:min(550px,95vw);max-height:90vh;overflow:auto;border:1px solid #cbd5e1;border-radius:12px;padding:24px;">
    <div class="d-flex justify-content-between align-items-center mb-3"><h5 id="debt-policy-title" class="mb-0">Sửa nhanh tiêu chí công nợ</h5><button type="button" id="debt-policy-close" class="btn btn-sm btn-light">Đóng</button></div>
    <p id="debt-policy-name" class="fw-bold"></p>
    <form method="POST" action="{{ route('customer-debt.policy') }}">@csrf @method('PATCH')
        @if($policyFromReport ?? false)<input type="hidden" name="return_to_report" value="1">@endif
        <input type="hidden" id="debt-policy-key" name="source_key"><input type="hidden" id="debt-policy-version" name="policy_version">
        <label for="debt-policy-fixed" class="form-label">Công nợ cố định (đ)</label><input id="debt-policy-fixed" name="fixed_debt" inputmode="numeric" class="form-control mb-3">
        <label for="debt-policy-limit" class="form-label">Trần công nợ (đ)</label><input id="debt-policy-limit" name="credit_limit" inputmode="numeric" class="form-control mb-3" placeholder="Mặc định: 0 đ">
        <label for="debt-policy-threshold-days" class="form-label">Số ngày cho nợ khi vượt ngưỡng</label><input id="debt-policy-threshold-days" name="threshold_days" type="number" min="0" max="3650" step="1" class="form-control mb-3" placeholder="Mặc định 0 ngày">
        <label for="debt-policy-settlement" class="form-label">Hình thức thanh toán</label>
        <select id="debt-policy-settlement" name="settlement_method" class="form-select mb-2">
            @foreach(\App\Services\CustomerDebtPolicyService::SETTLEMENT_METHODS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <p class="small text-muted mb-3">Theo Bill hoặc Hỗn hợp, ghi ngày giao và mã đơn trong nội dung thu, ví dụ <strong>Bill 13/11/2026 | Mã đơn: DH123</strong>. Với khoản gộp của khách Hỗn hợp, ghi rõ <strong>Theo đợt</strong>. Nếu khách đổi cách trả từ một ngày cụ thể, dùng mục <strong>Chuyển hình thức thanh toán từ ngày giao</strong> trên trang đối chiếu.</p>
        <label for="debt-policy-payment-mode" class="form-label">Lịch thanh toán</label>
        <select id="debt-policy-payment-mode" class="form-select mb-3" name="payment_mode">
            <option value="default">Theo tiêu chí công nợ</option>
            <option value="cycle">Thanh toán định kỳ</option>
        </select>
        <fieldset data-policy-cycle hidden disabled>
            <label for="debt-policy-cycle-type" class="form-label">Chu kỳ</label>
            <select id="debt-policy-cycle-type" class="form-select mb-3" name="cycle_type">
                <option value="weekly">Hằng tuần</option>
                <option value="monthly">Hằng tháng</option>
            </select>
            <fieldset data-policy-weekly>
                @foreach(['cycle_close_weekday' => 'Thứ chốt gửi công nợ', 'cycle_weekday' => 'Thứ thanh toán'] as $field => $label)
                    <label for="debt-policy-{{ $field }}" class="form-label">{{ $label }}</label>
                    <select id="debt-policy-{{ $field }}" class="form-select mb-3" name="{{ $field }}" required>
                        @foreach([1 => 'Thứ hai', 2 => 'Thứ ba', 3 => 'Thứ tư', 4 => 'Thứ năm', 5 => 'Thứ sáu', 6 => 'Thứ bảy', 7 => 'Chủ nhật'] as $day => $dayLabel)
                            <option value="{{ $day }}">{{ $dayLabel }}</option>
                        @endforeach
                    </select>
                @endforeach
            </fieldset>
            <fieldset data-policy-monthly hidden disabled>
                @foreach(['cycle_close_monthday' => 'Ngày chốt gửi công nợ trong tháng', 'cycle_monthday' => 'Ngày thanh toán trong tháng'] as $field => $label)
                    <label for="debt-policy-{{ $field }}" class="form-label">{{ $label }}</label>
                    <input id="debt-policy-{{ $field }}" class="form-control mb-3" name="{{ $field }}" type="number" min="1" max="31" required>
                @endforeach
            </fieldset>
            <p class="small text-muted">Nếu ngày thanh toán đứng trước ngày chốt, hạn thanh toán chuyển sang tuần hoặc tháng kế tiếp.</p>
        </fieldset>
        <p class="small text-muted">Ngưỡng cảnh báo bằng công nợ cố định cộng trần công nợ. Nhập 0 nếu công nợ cố định bằng 0 đ. Để trống nếu khách chưa có mức công nợ cố định. Nhân viên lưu một lần rồi khóa; chỉ admin được mở khóa.</p>
        <button type="submit" class="btn btn-primary">Lưu & cập nhật cảnh báo</button>
    </form>
</dialog>
@push('scripts')
<script src="{{ asset('js/customer-debt-policy.js') }}?v={{ filemtime(public_path('js/customer-debt-policy.js')) }}"></script>
@endpush
