<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CustomerDebtReportService
{
    public function rows(array $context): Collection
    {
        $rows = [];
        $customers = $context['customers']->keyBy('id');
        foreach ($context['groups'] as $group) {
            $id = $group['customer_id'];
            $identity = $id ? 'customer_'.$id : 'source_'.$group['key'];
            if (isset($rows[$identity])) {
                continue;
            }
            $row = $id ? $context['customerDebts'][$id] : $group;
            $row['key'] = $group['key'];
            $row['code'] = $row['code'] ?: $group['code'];
            $row['source_keys'] = $row['source_keys'] ?? [$group['key']];
            $row['system_customer'] = $id ? $customers[$id] : null;
            $row['data_available'] = true;
            $row['display_balance'] = $row['balance'];
            $row['balance_target_name'] = $row['name'];
            $sourceGroups = collect($context['groups'])->filter(fn ($g) => in_array($g['key'], $row['source_keys'], true));
            $row['search_text'] = $row['name'].' '.$row['code'].' '.$sourceGroups->map(fn ($g) => $g['name'].' '.$g['code'])->implode(' ');
            $rows[$identity] = $row;
        }
        $recovery = app(CustomerDebtRecoveryService::class);
        $recoveryRecords = $recovery->records();
        foreach ($customers as $customer) {
            $identity = 'customer_'.$customer->id;
            if (isset($rows[$identity])) {
                continue;
            }
            $owner = $customer->currentOwner ?? $customer->assignedTo;
            $row = ['key' => null, 'source_keys' => [], 'customer_id' => $customer->id, 'system_customer' => $customer, 'name' => $customer->name, 'code' => $customer->customer_code ?? '', 'sales' => 0, 'payments' => 0, 'gifts' => 0, 'balance' => 0, 'entries' => [], 'last_purchase_date' => null, 'last_payment_date' => null, 'data_available' => false, 'sale_key' => $owner ? 'user:'.$owner->id : 'unassigned', 'sale_label' => $owner ? (trim((string) $owner->short_name) ?: $owner->name) : 'Chưa phân công', 'search_text' => $customer->name.' '.$customer->customer_code, 'alerts' => null];
            $row['recovery'] = $recovery->effective($row, $recoveryRecords);
            $rows[$identity] = $row;
        }
        $today = CarbonImmutable::today();
        foreach ($rows as $identity => &$row) {
            $row['identity'] = $identity;
            $row['report_source'] = 'sheet';
            foreach (['last_purchase_date' => 'days_without_purchase', 'last_payment_date' => 'days_without_payment'] as $field => $days) {
                $row[$days] = $row[$field] ? max(0, (int) CarbonImmutable::parse($row[$field])->diffInDays($today)) : null;
            }
        }
        unset($row);

        return collect($rows);
    }
}
