<article class="debt-report-ledger debt-detail-reminders">
    <header>
        <div><h3>Lịch sử nhắc nợ</h3><span>{{ count($reminderHistory['logs']) }} lần ghi nhận</span></div>
        @if($snapshot && $selected['balance'] > 0)
            <button type="button" class="btn btn-primary btn-sm collection-remind"
                data-key="{{ $detailPolicyKey ?? ($selected['key'] ?? '') }}" data-customer="{{ $selected['customer_id'] }}"
                data-name="{{ $selected['name'] }}" data-reminder-note="{{ \App\Services\DebtCollectionService::reminderNote((int) $selected['balance'], $selected['last_payment_date'], $selected['last_purchase_date']) }}" data-balance="{{ $selected['balance'] }}" data-report-source="sheet"
                data-request-id="{{ \Illuminate\Support\Str::uuid() }}">Nhắc nợ</button>
        @endif
    </header>
    <div class="debt-report-scroll px-3">
        @forelse(array_slice($reminderHistory['logs'], 0, 5) as $log)
            @php($reminder = $log['record'])
            <div class="py-2 border-bottom">
                <p class="small my-0 text-break">
                    <strong>{{ \Carbon\Carbon::parse($reminder->reminded_at)->format('d/m/Y H:i') }}</strong>
                    : {{ \App\Services\DebtCollectionService::compactReminderNote($reminder->note) }}
                </p>
                <div class="small mt-1">Thu {{ number_format($log['paid'], 0, ',', '.') }} / {{ number_format($log['target'], 0, ',', '.') }} đ</div>
            </div>
        @empty
            <p class="small text-muted my-3">Chưa ghi nhận lần nhắc nợ nào.</p>
        @endforelse
    </div>
    @if($reminderHistory['logs'])
        <footer>
            <button type="button" class="btn btn-light btn-sm collection-history" data-report-source="sheet"
                data-key="{{ $detailPolicyKey ?? ($selected['key'] ?? '') }}" data-customer="{{ $selected['customer_id'] }}"
                data-name="{{ $selected['name'] }}">Xem toàn bộ lịch sử</button>
        </footer>
    @endif
</article>
