<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\CarbonImmutable;

class CustomerPaymentSyncService
{
    public function records(array $snapshot): array
    {
        $rows = [];
        foreach ($snapshot['entries'] ?? [] as $index => $entry) {
            if ($entry['type'] === 'sale' && ($entry['gift'] ?? 0) != 0) {
                $gift = array_replace($entry, ['type' => 'gift', 'amount' => $entry['gift'], 'note' => 'Tặng / giảm trừ tại bán hàng'.(! empty($entry['note']) ? ' · '.$entry['note'] : '')]);
                foreach (['payment_id', 'first_seen_at', 'first_seen_batch', 'last_changed_at', 'last_changed_batch'] as $field) {
                    unset($gift[$field]);
                    if (array_key_exists('gift_'.$field, $entry)) {
                        $gift[$field] = $entry['gift_'.$field];
                    }
                }
                $rows[] = $gift + ['source_sheet' => 'nhatkybanhang', 'customer_source_key' => null, 'source_spreadsheet_id' => $snapshot['spreadsheet_id'] ?? null, 'unidentified' => false, '_container' => 'entries', '_index' => $index];
            }
            if ($entry['type'] !== 'payment') {
                continue;
            }
            $rows[] = $entry + ['source_sheet' => 'Dongtienhoatdong', 'customer_source_key' => null, 'source_spreadsheet_id' => $snapshot['spreadsheet_id'] ?? null, 'unidentified' => false, '_container' => 'entries', '_index' => $index];
        }
        foreach ($snapshot['issues'] ?? [] as $index => $issue) {
            if (($issue['type'] ?? '') !== 'payment') {
                continue;
            }
            $rawDate = (string) ($issue['date'] ?? '');
            $date = null;
            foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y'] as $format) {
                try {
                    $parsed = CarbonImmutable::createFromFormat('!'.$format, $rawDate);
                    if ($parsed && $parsed->format($format) === $rawDate) {
                        $date = $parsed->toDateString();
                        break;
                    }
                } catch (\Throwable) {
                }
            }
            $rows[] = array_replace($issue, ['date' => $date, 'raw_date' => $rawDate, 'code' => '', 'name' => '', 'type' => 'payment', 'source_sheet' => $issue['source_sheet'] ?? 'Dongtienhoatdong', 'sale_name' => $issue['sale_name'] ?? '', 'customer_source_key' => null, 'source_spreadsheet_id' => $snapshot['spreadsheet_id'] ?? null, 'unidentified' => true, '_container' => 'issues', '_index' => $index]);
        }

        return $rows;
    }

    private function fingerprint(array $row): string
    {
        return hash('sha256', json_encode([$row['source_sheet'].(($row['type'] ?? 'payment') === 'gift' ? '|gift' : ''), $row['date'] ?? ($row['raw_date'] ?? ''), mb_strtoupper(trim((string) ($row['code'] ?? ''))), Customer::normalizeName($row['name'] ?? ''), Customer::normalizeName($row['sale_name'] ?? ''), (int) $row['amount'], preg_replace('/\s+/u', ' ', trim($row['note'] ?? ''))], JSON_THROW_ON_ERROR));
    }

    private function slot(array $row): string
    {
        return $row['source_sheet'].'|'.$row['row'].(($row['type'] ?? 'payment') === 'gift' ? '|gift' : '');
    }

    private function clean(array $row): array
    {
        unset($row['_container'],$row['_index']);

        return $row;
    }

    /** Compare complete snapshots; never infer a new receipt just because its row moved. */
    public function track(array $previous, array $next, string $batchId, string $at): array
    {
        $old = $this->records($previous);
        $new = $this->records($next);
        $buckets = [];
        $counts = [];
        foreach ($old as $index => &$row) {
            $fingerprint = $this->fingerprint($row);
            $occurrence = ($counts[$fingerprint] ?? 0) + 1;
            $counts[$fingerprint] = $occurrence;
            $row['payment_id'] = $row['payment_id'] ?? hash('sha256', 'legacy|'.$fingerprint.'|'.$occurrence);
            $row['first_seen_at'] = $row['first_seen_at'] ?? ($previous['loaded_at'] ?? null);
            $row['first_seen_batch'] = $row['first_seen_batch'] ?? null;
            $buckets[$fingerprint][] = $index;
        }unset($row);
        $matches = [];
        $used = [];
        foreach ($new as $index => $row) {
            $fingerprint = $this->fingerprint($row);
            if (empty($buckets[$fingerprint])) {
                continue;
            }
            $oldIndex = array_shift($buckets[$fingerprint]);
            $matches[$index] = ['old' => $oldIndex, 'kind' => 'unchanged'];
            $used[$oldIndex] = true;
        }
        $slots = [];
        foreach ($old as $index => $row) {
            if (! isset($used[$index])) {
                $slots[$this->slot($row)][] = $index;
            }
        }
        foreach ($new as $index => $row) {
            if (isset($matches[$index])) {
                continue;
            }
            $slot = $this->slot($row);
            if (empty($slots[$slot])) {
                continue;
            }
            $oldIndex = array_shift($slots[$slot]);
            $matches[$index] = ['old' => $oldIndex, 'kind' => 'updated'];
            $used[$oldIndex] = true;
        }
        $changes = [];
        $summary = ['id' => $batchId, 'at' => $at, 'source' => $next['source'], 'spreadsheet_id' => $next['spreadsheet_id'] ?? null, 'loaded_by' => $next['loaded_by'] ?? null, 'new_count' => 0, 'updated_count' => 0, 'removed_count' => 0, 'new_amount' => 0, 'delta' => 0, 'gift_new_amount' => 0, 'gift_delta' => 0, 'initial' => empty($previous), 'payment_tabs' => $next['payment_tabs'] ?? []];
        foreach ($new as $index => $row) {
            $match = $matches[$index] ?? null;
            $before = $match ? $old[$match['old']] : null;
            $kind = $match['kind'] ?? 'new';
            $metadata = ['payment_id' => $before['payment_id'] ?? hash('sha256', $batchId.'|'.$index), 'first_seen_at' => $before['first_seen_at'] ?? $at, 'first_seen_batch' => $before['first_seen_batch'] ?? ($before ? null : $batchId), 'last_changed_at' => $kind === 'updated' ? $at : ($before['last_changed_at'] ?? null), 'last_changed_batch' => $kind === 'updated' ? $batchId : ($before['last_changed_batch'] ?? null)];
            $row = array_replace($row, $metadata);
            $storedMetadata = $metadata;
            if (($row['type'] ?? 'payment') === 'gift') {
                $storedMetadata = [];
                foreach ($metadata as $field => $value) {
                    $storedMetadata['gift_'.$field] = $value;
                }
            }
            $next[$row['_container']][$row['_index']] = array_replace($next[$row['_container']][$row['_index']], $storedMetadata);
            if ($kind === 'unchanged') {
                continue;
            }
            $delta = (int) $row['amount'] - (int) ($before['amount'] ?? 0);
            $changes[] = ['kind' => $kind, 'payment_id' => $row['payment_id'], 'before' => $before ? $this->clean($before) : null, 'after' => $this->clean($row), 'delta' => $delta];
            $summary[$kind.'_count']++;
            $summary[($row['type'] ?? 'payment') === 'gift' ? 'gift_delta' : 'delta'] += $delta;
            if ($kind === 'new') {
                $summary[($row['type'] ?? 'payment') === 'gift' ? 'gift_new_amount' : 'new_amount'] += (int) $row['amount'];
            }
        }
        foreach ($old as $index => $row) {
            if (isset($used[$index])) {
                continue;
            }
            $changes[] = ['kind' => 'removed', 'payment_id' => $row['payment_id'], 'before' => $this->clean($row), 'after' => null, 'delta' => -(int) $row['amount']];
            $summary['removed_count']++;
            $summary[($row['type'] ?? 'payment') === 'gift' ? 'gift_delta' : 'delta'] -= (int) $row['amount'];
        }

        return ['snapshot' => $next, 'batch' => ['summary' => $summary, 'changes' => $changes]];
    }
}
