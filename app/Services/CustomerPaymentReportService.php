<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerPaymentReportService
{
    public function rows(array $context, ?array $batch = null): Collection
    {
        $tracker = app(CustomerPaymentSyncService::class);
        $groups = $context['groups'];
        $customers = $context['customers']->keyBy('id');
        $byCode = $customers->filter(fn ($c) => trim((string) $c->customer_code) !== '')->groupBy(fn ($c) => mb_strtoupper(trim($c->customer_code)));
        $byName = $customers->groupBy(fn ($c) => Customer::normalizeName($c->name));
        $mappings = app(CustomerDebtSheetService::class)->mappings();
        $bySlot = [];
        foreach ($groups as $group) {
            foreach ($group['entries'] as $entry) {
                if (in_array($entry['type'], ['sale', 'payment'], true)) {
                    $bySlot[($entry['source_sheet'] ?? 'Dongtienhoatdong').'|'.$entry['row']] = $group;
                }
            }
        }
        $staffNames = [];
        foreach (User::query()->get(['id', 'name', 'short_name']) as $staff) {
            foreach ([$staff->name, $staff->short_name] as $name) {
                if (trim((string) $name) !== '') {
                    $staffNames[Customer::normalizeName($name)][$staff->id] = $staff;
                }
            }
        }
        $raw = [];
        if ($batch) {
            foreach ($batch['changes'] as $change) {
                $raw[] = ($change['after'] ?? $change['before']) + ['change_kind' => $change['kind'], 'change_before' => $change['before'], 'change_after' => $change['after'], 'change_delta' => $change['delta']];
            }
        } else {
            $raw = $tracker->records($context['snapshot'] ?? []);
        }
        $rows = [];
        foreach ($raw as $entry) {
            $sourceKey = $entry['customer_source_key'] ?? null;
            $group = $sourceKey ? ($groups[$sourceKey] ?? null) : (! $batch ? ($bySlot[$entry['source_sheet'].'|'.$entry['row']] ?? null) : null);
            $target = $group && $group['customer_id'] ? ($customers[$group['customer_id']] ?? null) : null;
            if (! $target && $sourceKey && isset($mappings[$sourceKey])) {
                $target = $customers[$mappings[$sourceKey]['customer_id']] ?? null;
            }
            if (! $target && ! $group && (! $sourceKey || ! isset($mappings[$sourceKey])) && ! empty($entry['name'])) {
                $matches = trim($entry['code'] ?? '') !== '' ? ($byCode[mb_strtoupper(trim($entry['code']))] ?? collect()) : collect();
                if ($matches->isEmpty()) {
                    $matches = $byName[Customer::normalizeName($entry['name'])] ?? collect();
                }
                if ($matches->count() === 1) {
                    $target = $matches->first();
                }
            }
            $owner = $target?->currentOwner ?? $target?->assignedTo;
            if ($owner) {
                $saleKey = 'user:'.$owner->id;
                $saleLabel = trim((string) $owner->short_name) ?: $owner->name;
            } else {
                $saleName = trim($entry['sale_name'] ?? '');
                $staff = $staffNames[Customer::normalizeName($saleName)] ?? [];
                if (count($staff) === 1) {
                    $staff = array_values($staff)[0];
                    $saleKey = 'user:'.$staff->id;
                    $saleLabel = trim((string) $staff->short_name) ?: $staff->name;
                } elseif ($saleName !== '') {
                    $saleKey = 'sheet:'.substr(hash('sha256', Customer::normalizeName($saleName)), 0, 16);
                    $saleLabel = $saleName;
                } else {
                    $saleKey = $group['sale_key'] ?? 'unassigned';
                    $saleLabel = $group['sale_label'] ?? 'Chưa xác định';
                }
            }
            $date = $entry['date'] ?? null;
            $removed = ($entry['change_kind'] ?? null) === 'removed';
            $row = array_replace($entry, ['source_key' => $sourceKey ?? ($group['key'] ?? null), 'customer_id' => $target?->id, 'customer_name' => $target?->name ?? ($entry['name'] ?: 'Chưa xác định khách'), 'customer_code' => $target?->customer_code ?: (($entry['code'] ?? '') ?: ($group['code'] ?? ($entry['customer_source_code'] ?? ''))), 'sale_key' => $saleKey, 'sale_label' => $saleLabel, 'is_recent' => ($entry['type'] ?? 'payment') === 'payment' && ! $removed && (int) $entry['amount'] > 0 && $date === now()->toDateString(), 'removed' => $removed, 'unidentified' => empty($entry['name']) && empty($entry['code'])]);
            $row['debt_key'] = $group['key'] ?? ($target ? collect($groups)->firstWhere('customer_id', $target->id)['key'] ?? null : null);
            $row['search_text'] = app(CustomerDebtSheetService::class)->normalize($row['customer_name'].' '.$row['customer_code'].' '.($entry['name'] ?? '').' '.($entry['code'] ?? '').' '.($entry['note'] ?? '').' '.($group['code'] ?? ($entry['customer_source_code'] ?? '')));
            $rows[] = $row;
        }

        return collect($rows);
    }
}
