@if(($completedAdjustments ?? collect())->isNotEmpty())
    @php
        $feeLabels = [
            'vat' => 'Phí VAT',
            'shipping' => 'Phí Ship',
            'discount' => 'Chiết khấu đơn',
            'foam_box' => 'Phí thùng xốp',
        ];
        $orderFieldLabels = [
            'recipient_name' => 'Người nhận',
            'recipient_phone' => 'Số điện thoại',
            'delivery_time' => 'Giờ giao',
        ];
        $formatState = static function (array $state, bool $percent = false): string {
            if (! (bool) ($state['enabled'] ?? false)) {
                return 'Không áp dụng';
            }

            $value = (float) ($state['value'] ?? 0);

            return $percent
                ? rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').' %'
                : number_format($value, 0, ',', '.').'đ';
        };
    @endphp
    <div class="acc-card mb-3 ds-adjustments-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <div>
                    <div class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Điều chỉnh đã duyệt và áp dụng</div>
                    <div class="small text-muted">Theo ngày nghiệp vụ của đơn; số liệu bên dưới đã được tính vào báo cáo.</div>
                </div>
                <span class="badge text-bg-success">{{ $completedAdjustments->count() }} yêu cầu</span>
            </div>

            <div class="ds-adjustment-list">
                @foreach($completedAdjustments as $adjustment)
                    @php
                        $changedItems = $adjustment->items->filter(fn ($item) =>
                            (float) $item->original_quantity !== (float) $item->adjusted_quantity
                            || (float) $item->original_price !== (float) $item->adjusted_price
                            || (float) ($item->original_weight ?? 0) !== (float) ($item->adjusted_weight ?? 0)
                        );
                        $changedFees = collect((array) ($adjustment->fee_changes ?? []))->filter(function ($change): bool {
                            $original = (array) ($change['original'] ?? []);
                            $adjusted = (array) ($change['adjusted'] ?? []);

                            return (bool) ($original['enabled'] ?? false) !== (bool) ($adjusted['enabled'] ?? false)
                                || abs((float) ($original['value'] ?? 0) - (float) ($adjusted['value'] ?? 0)) > 0.001;
                        });
                    @endphp
                    <div class="ds-adjustment-item">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div>
                                <a class="fw-bold text-decoration-none" href="{{ route('site.order-adjustments.show', $adjustment) }}" target="_blank">
                                    Yêu cầu #{{ $adjustment->id }} · {{ $adjustment->order?->code ?: 'Đơn #'.$adjustment->order_id }}
                                </a>
                                <div class="small text-muted">
                                    {{ $adjustment->order?->customer?->name ?? 'Khách hàng' }} · Sale {{ $adjustment->order?->user?->name ?? '—' }}
                                    · gửi bởi {{ $adjustment->requester?->name ?? '—' }}
                                </div>
                            </div>
                            <div class="small text-muted text-end">
                                Hoàn tất {{ optional($adjustment->completed_at)->format('d/m/Y H:i') ?? '—' }}
                            </div>
                        </div>

                        <div class="ds-adjustment-changes mt-2">
                            @if($changedItems->isNotEmpty())
                                <span class="ds-change-chip"><i class="bi bi-box-seam"></i> {{ $changedItems->count() }} dòng hàng thay đổi</span>
                            @endif
                            @foreach((array) ($adjustment->order_changes ?? []) as $field => $change)
                                <span class="ds-change-chip">
                                    {{ $orderFieldLabels[$field] ?? $field }}:
                                    {{ data_get($change, 'original') ?: '—' }} → <strong>{{ data_get($change, 'adjusted') ?: '—' }}</strong>
                                </span>
                            @endforeach
                            @foreach($changedFees as $code => $change)
                                @php
                                    $isPercent = ($change['calculation_type'] ?? null) === 'percent';
                                    $feeName = $change['name'] ?? ($feeLabels[$code] ?? ucfirst((string) $code));
                                @endphp
                                <span class="ds-change-chip ds-change-fee">
                                    {{ $feeName }}:
                                    {{ $formatState((array) ($change['original'] ?? []), $isPercent) }}
                                    → <strong>{{ $formatState((array) ($change['adjusted'] ?? []), $isPercent) }}</strong>
                                </span>
                            @endforeach
                            @if($changedItems->isEmpty() && empty($adjustment->order_changes) && $changedFees->isEmpty())
                                <span class="small text-muted">Hồ sơ không có chênh lệch số liệu.</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
