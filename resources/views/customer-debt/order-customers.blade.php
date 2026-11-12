<div class="col-lg-6">
    <div class="card">
        <div class="card-header"><strong>Khách hàng & cảnh báo công nợ</strong><div class="small text-muted">{{ $debts->total() }} khách có đơn theo khoảng ngày đã chọn · Mỗi khách hiển thị một lần</div></div>
        <div class="card-body">
            @forelse($debts as $debt)
            <article class="border rounded p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <div><strong>{{ $debt['name'] }}</strong><div class="small text-muted">{{ $debt['code'] ?: 'Chưa có mã khách hàng' }} · Sale: {{ $debt['sale_label'] }}</div></div>
                    @if($debt['data_available'])<div class="text-end"><div class="small text-muted">Công nợ hiện tại</div><strong class="{{ $debt['balance'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($debt['balance'], 0, ',', '.') }} đ</strong></div>@endif
                </div>
                @if($debt['data_available'])
                    @include('customer-debt.alerts', ['alert'=>$debt['alerts']])
                    <div class="row g-2 mt-2 small">
                        <div class="col-6">Doanh thu: <strong>{{ number_format($debt['sales'], 0, ',', '.') }} đ</strong></div>
                        <div class="col-6">Đã thanh toán: <strong>{{ number_format($debt['payments'], 0, ',', '.') }} đ</strong></div>
                        <div class="col-6">Ngày mua: {{ $debt['last_purchase_date'] ? \Carbon\Carbon::parse($debt['last_purchase_date'])->format('d/m/Y') : 'Chưa có' }}</div>
                        <div class="col-6">Ngày Thanh toán: {{ $debt['last_payment_date'] ? \Carbon\Carbon::parse($debt['last_payment_date'])->format('d/m/Y') : 'Chưa có' }}</div>
                    </div>
                @else
                    <div class="alert alert-warning py-2 mb-2"><strong>Chưa xác định được công nợ</strong><br>Chưa có dữ liệu Google Sheet đã ghép với khách này. Cần cập nhật Sheet hoặc ghép khách trước khi xác định số nợ và mức cảnh báo.</div>
                    @if($canLoad)<a class="btn btn-sm btn-outline-primary mb-2" href="{{ route('customer-debt.index', ['tab'=>'all','search'=>$debt['name']]) }}">Kiểm tra & ghép dữ liệu khách</a>@endif
                @endif
                <div class="small border-top pt-2 mt-2">Cho nợ: <strong>{{ $debt['policy']['allowed_orders'] }} Bill</strong> · Trần nợ: <strong>{{ number_format($debt['policy']['credit_limit'], 0, ',', '.') }} đ</strong><br>Hạn thanh toán: {{ $debt['policy']['due_days'] }} ngày kể từ ngày giao hàng</div>
                @if($debt['data_available'])
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <a class="btn btn-sm btn-primary" href="{{ request()->fullUrlWithQuery(['customer'=>null,'system_customer'=>$debt['customer_id'],'window'=>'recent','detail_page'=>null,'purchase_page'=>1,'payment_page'=>1,'report_from'=>null,'report_to'=>null]) }}#debt-detail">Xem nhanh công nợ 14 ngày</a>
                    <button type="button" class="btn btn-sm btn-outline-primary debt-policy-button" data-key="{{ $debt['key'] }}" data-name="{{ $debt['name'] }}" data-orders="{{ $debt['policy']['allowed_orders'] }}" data-limit="{{ $debt['policy']['credit_limit'] }}" data-days="{{ $debt['policy']['due_days'] }}">Sửa nhanh tiêu chí</button>
                </div>
                <div class="small text-muted mt-2">Công nợ và cảnh báo đã gộp tất cả nguồn Sheet được ghép với khách này.</div>
                @endif
            </article>
            @empty
            <p class="text-muted text-center mb-0">Không có khách có đơn phù hợp với bộ lọc.</p>
            @endforelse
            {{ $debts->links() }}
        </div>
    </div>
</div>
