<?php

namespace App\Services;

use App\Models\Account;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

class GoogleSheetCashflowService
{
    private const ACCOUNTS = [
        'Dongtienhoatdong' => ['1115129999', 'BIDV - Nguyễn Bá Tuân', 'BIDV', 'personal'],
        'TKCNMBBDUE' => ['7799131453666', 'MB - Cá nhân Duệ', 'MB', 'personal'],
        'TKCTVCB' => ['1050717555', 'Công ty VCB', 'VCB', 'company'],
        'TKCTBIDV' => ['8638596666', 'Công ty BIDV', 'BIDV', 'company'],
        'TKCTVPB' => ['39876555', 'Công ty VPBank', 'VPBank', 'company'],
    ];

    public function parse(array $tabs, CustomerDebtSheetService $sheets): array
    {
        $ledgers = [];
        foreach ($tabs as $tab => $values) {
            [$number, $label, $bank, $owner] = self::ACCOUNTS[$tab];
            $title = (string) ($values[0][0] ?? '');
            if (! preg_match('/(?<!\d)'.preg_quote($number, '/').'(?!\d)/', $title)) {
                throw new RuntimeException("Tab {$tab} không khớp số tài khoản đã cấu hình.");
            }

            $header = collect($values)->take(10)->search(fn ($row) =>
                $sheets->normalize((string) ($row[6] ?? '')) === 'thu'
                && $sheets->normalize((string) ($row[7] ?? '')) === 'chi'
                && $sheets->normalize((string) ($row[8] ?? '')) === 'con lai');
            if ($header === false) {
                throw new RuntimeException("Thiếu cột Thu, Chi hoặc Còn lại trong tab {$tab}.");
            }

            $opening = $closing = null;
            $rows = [];
            foreach (array_slice($values, $header + 1, null, true) as $index => $cells) {
                $row = $index + 1;
                try {
                    $in = $this->amount((string) ($cells[6] ?? ''), $sheets);
                    $out = $this->amount((string) ($cells[7] ?? ''), $sheets);
                    if ($in === 0 && $out === 0) {
                        continue;
                    }
                    $sourceDate = trim((string) ($cells[0] ?? ''));
                    if (! preg_match('~^(\d{1,2})/(\d{1,2})/(\d{4})$~', $sourceDate, $parts)
                        || ! checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3])
                        || trim((string) ($cells[8] ?? '')) === '') {
                        throw new RuntimeException('Thiếu ngày hoặc số dư hợp lệ.');
                    }
                    $date = CarbonImmutable::createFromDate((int) $parts[3], (int) $parts[2], (int) $parts[1])->toDateString();
                    $balance = $sheets->money((string) $cells[8]);
                    $opening ??= $balance - $in + $out;
                    $difference = $balance - (($closing ?? $opening) + $in - $out);
                    $closing = $balance;
                    $rows[] = ['row' => $row, 'date' => $date, 'in' => $in, 'out' => $out, 'balance' => $balance, 'difference' => $difference,
                        'category' => trim((string) ($cells[2] ?? '')), 'counterparty' => trim((string) ($cells[3] ?? '')), 'note' => trim((string) ($cells[5] ?? ''))];
                } catch (RuntimeException $e) {
                    throw new RuntimeException("Thu chi {$tab}, dòng {$row}: ".$e->getMessage(), 0, $e);
                }
            }
            if ($opening === null) {
                throw new RuntimeException("Tab {$tab} không có giao dịch để đối chiếu số dư.");
            }
            $ledgers[$tab] = compact('number', 'label', 'bank', 'owner', 'opening', 'closing', 'rows');
        }

        return $ledgers;
    }

    public function registerAccounts(array $ledgers): void
    {
        foreach ($ledgers as $ledger) {
            $matches = Account::query()->where('account_number', $ledger['number'])->limit(2)->get();
            if ($matches->count() > 1 || ($matches->isNotEmpty() && $matches->first()->type !== 'bank')) {
                throw new RuntimeException("Số tài khoản {$ledger['number']} bị trùng hoặc sai loại trong danh mục tài khoản.");
            }
            Account::firstOrCreate(['account_number' => $ledger['number']], [
                'name' => $ledger['label'], 'type' => 'bank', 'bank_name' => $ledger['bank'],
                'owner_type' => $ledger['owner'], 'opening_balance' => $ledger['opening'],
                'balance' => $ledger['opening'], 'is_active' => true,
            ]);
        }
    }

    public function ledgers(?array $snapshot): Collection
    {
        return collect($snapshot['cashflow_ledgers'] ?? []);
    }

    private function amount(string $value, CustomerDebtSheetService $sheets): int
    {
        return in_array(trim($value), ['', '-'], true) ? 0 : $sheets->money($value);
    }
}
