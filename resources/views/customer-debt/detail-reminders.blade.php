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
                <div class="small fw-semibold">{{ \Carbon\Carbon::parse($reminder->reminded_at)->format('d/m/Y H:i') }} · {{ $reminder->actor_name }}</div>
                <div class="small text-muted">{{ \App\Services\DebtCollectionService::CHANNELS[$reminder->channel] ?? $reminder->channel }} · {{ $log['label'] }}</div>
                <p class="small my-1 text-break">{{ \App\Services\DebtCollectionService::displayReminderNote($reminder->note) }}</p>
                <div class="small">Thu {{ number_format($log['paid'], 0, ',', '.') }} / {{ number_format($log['target'], 0, ',', '.') }} đ</div>
                @if($log['deadline'])<div class="small text-muted">Hẹn trả {{ \Carbon\Carbon::parse($log['deadline'])->format('d/m/Y') }}</div>@endif
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
