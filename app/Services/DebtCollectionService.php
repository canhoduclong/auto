<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebtCollectionService
{
    public const STATUSES = ['not_reminded' => 'Chưa nhắc', 'waiting' => 'Đã nhắc · Chờ thanh toán', 'response' => 'Đã phản hồi', 'partial' => 'Đã thu một phần', 'met' => 'Đạt mục tiêu', 'met_on_time' => 'Đạt đúng hẹn', 'met_late' => 'Đạt trễ hẹn', 'overdue' => 'Quá hẹn · Chưa đạt', 'needs_review' => 'Có khoản thu cần đối chiếu'];

    public const CHANNELS = ['phone' => 'Điện thoại', 'zalo' => 'Zalo', 'email' => 'Email', 'in_person' => 'Trực tiếp', 'other' => 'Khác'];

    public function namespace(): string
    {
        return (string) config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'));
    }

    public function records(): array
    {
        $reminders = DB::table('debt_collection_reminders')->where('spreadsheet_id', $this->namespace())->orderBy('reminded_at')->orderBy('id')->get();
        $events = $reminders->isEmpty() ? collect() : DB::table('debt_collection_events')->whereIn('reminder_id', $reminders->pluck('id'))->orderBy('id')->get();

        return ['reminders' => $reminders, 'events' => $events];
    }

    public function receipts(array $row): array
    {
        $entries = collect($row['entries'])->where('type', 'payment')->filter(fn ($e) => $e['amount'] != 0 && $e['date'] <= now()->toDateString())->sortBy(fn ($e) => [$e['date'], $e['row']])->values();
        $counts = [];
        $receipts = [];
        foreach ($entries as $entry) {
            $identity = mb_strtoupper(trim((string) ($entry['code'] ?? ''))) ?: mb_strtolower(trim((string) ($entry['name'] ?? '')));
            $fingerprint = hash('sha256', json_encode([$entry['date'], $identity, (int) $entry['amount'], trim($entry['note'] ?? '')], JSON_THROW_ON_ERROR));
            $occurrence = ($counts[$fingerprint] ?? 0) + 1;
            $counts[$fingerprint] = $occurrence;
            $token = hash('sha256', $this->namespace().'|'.$fingerprint.'|'.$occurrence);
            $receipts[$token] = ['token' => $token, 'slot' => hash('sha256', $identity.'|'.$entry['row']), 'date' => $entry['date'], 'amount' => (int) $entry['amount'], 'note' => $entry['note'] ?? '', 'row' => $entry['row']];
        }

        return $receipts;
    }

    public function belongs(object $reminder, array $row): bool
    {
        if ($reminder->customer_id) {
            return (int) $reminder->customer_id === (int) ($row['customer_id'] ?? 0);
        }

        return count(array_intersect(json_decode($reminder->source_keys, true) ?? [], $row['source_keys'] ?? [$row['key']])) > 0;
    }

    public function evaluate(array $row, array $records): array
    {
        $reminders = $records['reminders']->filter(fn ($r) => $this->belongs($r, $row))->values();
        if ($reminders->isEmpty()) {
            return ['logs' => [], 'latest' => null, 'status' => 'not_reminded', 'label' => self::STATUSES['not_reminded'], 'received_total' => 0];
        }
        $events = $records['events']->whereIn('reminder_id', $reminders->pluck('id'));
        $receipts = $this->receipts($row);
        $logs = [];
        foreach ($reminders as $reminder) {
            $ownEvents = $events->where('reminder_id', $reminder->id)->values();
            $promiseAmount = $ownEvents->whereNotNull('promised_amount')->last()?->promised_amount;
            $promiseDate = $ownEvents->whereNotNull('promised_date')->last()?->promised_date;
            $baseline = collect(json_decode($reminder->baseline_receipts, true) ?? []);
            $logs[$reminder->id] = ['record' => $reminder, 'events' => $ownEvents, 'target' => (int) ($promiseAmount ?? $reminder->target_amount), 'deadline' => $promiseDate ?? $reminder->promised_date, 'baseline_tokens' => $baseline->pluck('token')->flip()->all(), 'baseline_slots' => $baseline->pluck('slot')->flip()->all(), 'receipts' => [], 'pending' => []];
        }
        $verified = $events->where('type', 'payment_verified')->keyBy('receipt_token');
        foreach ($receipts as $token => $receipt) {
            $confirmation = $verified[$token] ?? null;
            if ($confirmation && isset($logs[$confirmation->reminder_id])) {
                $logs[$confirmation->reminder_id]['receipts'][] = $receipt + ['verified' => true];

                continue;
            }
            foreach (array_reverse(array_keys($logs)) as $id) {
                $log = $logs[$id];
                $day = CarbonImmutable::parse($log['record']->reminded_at)->toDateString();
                if ($receipt['date'] < $day || isset($log['baseline_tokens'][$token])) {
                    continue;
                }
                if ($receipt['date'] === $day || isset($log['baseline_slots'][$receipt['slot']])) {
                    $logs[$id]['pending'][] = $receipt + ['reason' => $receipt['date'] === $day ? 'Cùng ngày nhắc; cần xác nhận thu sau khi nhắc.' : 'Dòng thu đã thay đổi; cần đối chiếu.'];
                } else {
                    $logs[$id]['receipts'][] = $receipt + ['verified' => false];
                }
                break;
            }
        }
        foreach ($logs as &$log) {
            $paid = collect($log['receipts'])->sum('amount');
            $deadline = $log['deadline'];
            $atDeadline = $deadline ? collect($log['receipts'])->where('date', '<=', $deadline)->sum('amount') : $paid;
            if ($paid >= $log['target']) {
                $status = $deadline ? ($atDeadline >= $log['target'] ? 'met_on_time' : 'met_late') : 'met';
            } elseif ($deadline && $deadline < now()->toDateString()) {
                $status = 'overdue';
            } elseif (count($log['pending'])) {
                $status = 'needs_review';
            } elseif ($paid > 0) {
                $status = 'partial';
            } elseif ($log['events']->where('type', 'response')->isNotEmpty()) {
                $status = 'response';
            } else {
                $status = 'waiting';
            }
            $log['paid'] = $paid;
            $log['status'] = $status;
            $log['label'] = self::STATUSES[$status];
            $log['attainment'] = min(100, max(0, round($paid * 100 / max(1, $log['target']), 1)));
            $log['follow_up_due'] = $log['record']->follow_up_date && $log['record']->follow_up_date <= now()->toDateString() && ! in_array($status, ['met', 'met_on_time', 'met_late'], true);
            unset($log['baseline_tokens'],$log['baseline_slots']);
        }
        unset($log);
        $latest = $logs ? $logs[array_key_last($logs)] : null;

        return ['logs' => array_values(array_reverse($logs, true)), 'latest' => $latest, 'status' => $latest['status'] ?? 'not_reminded', 'label' => $latest['label'] ?? self::STATUSES['not_reminded'], 'received_total' => array_sum(array_column($logs, 'paid'))];
    }

    public function remind(array $row, ?array $snapshot, array $data, User $actor): int
    {
        $existing = DB::table('debt_collection_reminders')->where('request_id', $data['request_id'])->first();
        if ($existing) {
            if ($existing->spreadsheet_id !== $this->namespace() || ! $this->belongs($existing, $row) || (int) $existing->created_by !== (int) $actor->id || (int) $existing->target_amount !== (int) $data['target_amount'] || $existing->note !== $data['note'] || $existing->channel !== $data['channel'] || $existing->promised_date !== ($data['promised_date'] ?? null) || $existing->follow_up_date !== ($data['follow_up_date'] ?? null)) {
                throw ValidationException::withMessages(['request_id' => 'Yêu cầu đã được dùng. Tải lại trang trước khi ghi nhận.']);
            }

            return $existing->id;
        }
        if (! $snapshot || $row['balance'] <= 0) {
            throw ValidationException::withMessages(['customer' => 'Chưa có dữ liệu hoặc khách không còn nợ.']);
        }
        if ($data['target_amount'] > $row['balance']) {
            throw ValidationException::withMessages(['target_amount' => 'Mục tiêu thu vượt công nợ hiện tại. Vui lòng kiểm tra số dư mới nhất.']);
        }

        return DB::table('debt_collection_reminders')->insertGetId([
            'request_id' => $data['request_id'], 'spreadsheet_id' => $this->namespace(), 'customer_id' => $row['customer_id'] ?? null, 'source_key' => $row['key'] ?? null, 'source_keys' => json_encode($row['source_keys'] ?? [$row['key']]), 'customer_name' => $row['name'],
            'reminded_at' => now(), 'balance_at_reminder' => $row['balance'], 'target_amount' => $data['target_amount'], 'promised_date' => $data['promised_date'] ?? null, 'follow_up_date' => $data['follow_up_date'] ?? null, 'channel' => $data['channel'], 'note' => $data['note'],
            'baseline_receipts' => json_encode(array_values($this->receipts($row)), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'snapshot_loaded_at' => $snapshot['loaded_at'] ?? null, 'created_by' => $actor->id, 'actor_name' => $actor->name ?: 'Admin #'.$actor->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function respond(array $row, array $data, User $actor): int
    {
        $records = $this->records();
        $evaluation = $this->evaluate($row, $records);
        $log = collect($evaluation['logs'])->first(fn ($log) => (int) $log['record']->id === (int) $data['reminder_id']);
        abort_unless($log, 404);
        $type = $data['type'];
        $receipt = null;
        $existing = DB::table('debt_collection_events')->where('request_id', $data['request_id'])->first();
        if ($existing) {
            if ((int) $existing->reminder_id !== (int) $data['reminder_id'] || (int) $existing->created_by !== (int) $actor->id || $existing->type !== $type || $existing->note !== $data['note'] || $existing->receipt_token !== ($data['receipt_token'] ?? null) || (string) $existing->promised_amount !== (string) ($data['promised_amount'] ?? '') || $existing->promised_date !== ($data['promised_date'] ?? null)) {
                throw ValidationException::withMessages(['request_id' => 'Yêu cầu này đã được xử lý. Tải lại lịch sử để tiếp tục.']);
            }

            return $existing->id;
        }
        if ($type === 'payment_verified') {
            $receipt = collect($log['pending'])->firstWhere('token', $data['receipt_token']);
            if (! $receipt) {
                throw ValidationException::withMessages(['receipt_token' => 'Khoản thu không còn chờ xác nhận. Tải lại lịch sử để kiểm tra.']);
            }
        }

        return DB::table('debt_collection_events')->insertGetId(['request_id' => $data['request_id'], 'reminder_id' => $data['reminder_id'], 'type' => $type, 'note' => $data['note'], 'promised_amount' => $type === 'response' ? ($data['promised_amount'] ?? null) : null, 'promised_date' => $type === 'response' ? ($data['promised_date'] ?? null) : null, 'receipt_token' => $receipt['token'] ?? null, 'receipt_amount' => $receipt['amount'] ?? null, 'created_by' => $actor->id, 'actor_name' => $actor->name ?: 'Admin #'.$actor->id, 'created_at' => now(), 'updated_at' => now()]);
    }
}
