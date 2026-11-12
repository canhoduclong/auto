<div class="col-xl-6">
    <div class="card debt-customers-panel">
        <div class="card-header debt-customers-panel-header"><strong>Khách hàng & cảnh báo công nợ</strong><div class="small text-muted">{{ $debts->total() }} khách có đơn theo khoảng ngày đã chọn</div></div>
        <div class="card-body debt-customers-panel-body">
            @forelse($debts as $debt)
            <article class="debt-customer-card">
                <div class="debt-customer-card-header">
                    <div class="debt-customer-card-identity"><h3>{{ $debt['name'] }}</h3><div class="debt-customer-card-meta">{{ $debt['code'] ?: 'Chưa có mã KH' }} · Sale: {{ $debt['sale_label'] }}</div></div>
                    @if($debt['data_available'])<div class="debt-customer-card-balance" title="Công nợ đã gộp mọi nguồn Sheet đã ghép"><span>Công nợ</span><strong class="{{ $debt['balance'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($debt['balance'], 0, ',', '.') }} đ</strong></div>@endif
                </div>
                <div class="debt-customer-compact-toolbar">
                    <div class="debt-customer-card-alerts">
                        @if($debt['data_available'])
                            @include('customer-debt.alerts', ['alert'=>$debt['alerts'], 'badgesOnly'=>true])
                        @else
                            <span class="badge bg-warning text-dark" title="Cần cập nhật Sheet hoặc ghép dữ liệu trước khi xác định công nợ">Chưa có dữ liệu công nợ</span>
                        @endif
                    </div>
                    <div class="debt-customer-card-actions">
                        @if($debt['data_available'])
                            <a class="btn btn-sm btn-outline-primary" aria-label="Xem công nợ của {{ $debt['name'] }}" href="{{ request()->fullUrlWithQuery(['customer'=>null,'system_customer'=>$debt['customer_id'],'window'=>'recent','detail_page'=>null,'purchase_page'=>1,'payment_page'=>1,'report_from'=>null,'report_to'=>null]) }}#debt-detail">Chi tiết</a>
                            @if($canAdjust) @include('customer-debt.balance-button') @endif
                            <button type="button" class="btn btn-sm btn-light debt-policy-button" data-key="{{ $debt['key'] }}" data-name="{{ $debt['name'] }}" data-orders="{{ $debt['policy']['allowed_orders'] }}" data-limit="{{ $debt['policy']['credit_limit'] }}" data-days="{{ $debt['policy']['due_days'] }}" data-version="{{ $debt['policy']['edit_version'] }}" @disabled($debt['policy']['staff_edit_locked'] && !auth()->user()->isAdmin())>Sửa tiêu chí</button>
                        @elseif($canLoad)
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('customer-debt.index', ['tab'=>'all','search'=>$debt['name']]) }}">Ghép dữ liệu</a>
                        @endif
                    </div>
                </div>
                <details class="debt-customer-extra">
                    <summary>Thông tin thêm</summary>
                    @if($debt['data_available'])
                        <div class="debt-customer-card-metrics">
                            <div><span>Doanh thu</span><strong>{{ number_format($debt['sales'], 0, ',', '.') }} đ</strong></div>
                            <div><span>Đã thanh toán</span><strong>{{ number_format($debt['payments'], 0, ',', '.') }} đ</strong></div>
                            <div><span>Ngày mua</span><strong>{{ $debt['last_purchase_date'] ? \Carbon\Carbon::parse($debt['last_purchase_date'])->format('d/m/Y') : 'Chưa có' }}</strong></div>
                            <div><span>Ngày thanh toán</span><strong>{{ $debt['last_payment_date'] ? \Carbon\Carbon::parse($debt['last_payment_date'])->format('d/m/Y') : 'Chưa có' }}</strong></div>
                        </div>
                        <div class="debt-customer-extra-alerts">
                            <span>{{ $debt['alerts']['unpaid_orders'] }} Bill còn nợ · Tuổi nợ {{ $debt['alerts']['age_days'] }} ngày</span>
                            @if($debt['alerts']['due_date'])<span>Hạn: {{ \Carbon\Carbon::parse($debt['alerts']['due_date'])->format('d/m/Y') }}</span>@endif
                            @foreach($debt['alerts']['reasons'] as $reason)<span>{{ $reason }}</span>@endforeach
                        </div>
                    @else
                        <p class="small text-muted mt-2 mb-0">Chưa có dữ liệu Google Sheet đã ghép với khách này. Cần cập nhật hoặc ghép dữ liệu trước khi xác định số nợ và mức cảnh báo.</p>
                    @endif
                    <div class="debt-customer-card-terms" title="Số Bill được phép nợ · Trần công nợ · Hạn từ ngày giao hàng gần nhất">Cho nợ: <strong>{{ $debt['policy']['allowed_orders'] }} Bill</strong> · Trần nợ: <strong>{{ number_format($debt['policy']['credit_limit'], 0, ',', '.') }} đ</strong> · Ngày nợ: <strong>{{ $debt['policy']['due_days'] }} ngày</strong></div>
                </details>
            </article>
            @empty
            <p class="text-muted text-center mb-0">Không có khách có đơn phù hợp với bộ lọc.</p>
            @endforelse
            {{ $debts->links() }}
        </div>
    </div>
</div>
