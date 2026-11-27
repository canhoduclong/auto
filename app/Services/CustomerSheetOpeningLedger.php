<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerSheetOpeningLedger
{
    public const CUTOFF = '2026-09-15';

    private const FILE = 'customer-sheet-opening/ledger.json';

    public function __construct(private CustomerDebtSheetService $sheets) {}

    public function snapshot(): array
    {
        $disk = Storage::disk('local');

        return $disk->exists(self::FILE) ? json_decode($disk->get(self::FILE), true, 512, JSON_THROW_ON_ERROR) : [];
    }

    public function refresh(int $userId): array
    {
        return Cache::lock('customer-sheet-opening-refresh', 180)->block(5, function () use ($userId) {
            [$sales, $payments, $mb, $vcb, $bidv, $vpb] = $this->sheets->fetch();
            $snapshot = $this->sheets->parseSnapshot($sales, $payments, $userId, 'google', $mb, [
                'TKCTVCB' => $vcb, 'TKCTBIDV' => $bidv, 'TKCTVPB' => $vpb,
            ]);
            $previous = $this->snapshot();
            $snapshot['contacts'] = $previous['contacts'] ?? [];
            $contactsUnavailable = false;
            try {
                $contacts = $this->sheets->fetchCustomerContacts();
                $header = array_map(fn ($value) => $this->sheets->normalize((string) $value), $contacts[0] ?? []);
                if (($header[0] ?? '') !== 'ma kh' || ($header[1] ?? '') !== 'khach hang' || ($header[3] ?? '') !== 'sdt') {
                    throw new RuntimeException('Không nhận diện được tiêu đề danh bạ TongHop.');
                }
                $snapshot['contacts'] = array_values(array_slice($contacts, 1));
            } catch (\Throwable $e) {
                report($e);
                $contactsUnavailable = true;
            }
            $unchanged = ($previous['entries'] ?? null) === $snapshot['entries']
                && ($previous['issues'] ?? null) === $snapshot['issues']
                && ($previous['contacts'] ?? []) === $snapshot['contacts'];
            $snapshot['runs'] = array_slice(array_merge([[
                'at' => $snapshot['loaded_at'], 'user_id' => $userId, 'unchanged' => $unchanged,
                'contacts_unavailable' => $contactsUnavailable,
                'sales' => count(array_filter($snapshot['entries'], fn ($row) => $row['type'] === 'sale')),
                'payments' => count(array_filter($snapshot['entries'], fn ($row) => $row['type'] === 'payment')),
            ]], $previous['runs'] ?? []), 0, 50);

            $disk = Storage::disk('local');
            $temporary = dirname(self::FILE).'/'.Str::uuid().'.tmp';
            try {
                if (! $disk->put($temporary, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
                    || ! rename($disk->path($temporary), $disk->path(self::FILE))) {
                    throw new RuntimeException('Không lưu được dữ liệu đồng bộ.');
                }
            } finally {
                $disk->delete($temporary);
            }

            return $snapshot['runs'][0];
        });
    }

    public function groups(array $snapshot): array
    {
        $groups = $this->sheets->summarize($snapshot['entries'] ?? []);
        $byName = [];
        foreach ($snapshot['contacts'] ?? [] as $contact) {
            $name = $this->sheets->customerName((string) ($contact[1] ?? ''));
            $code = strtoupper(trim((string) ($contact[0] ?? '')));
            $rawPhone = trim((string) ($contact[3] ?? ''));
            if ($name === '' || !preg_match('/^\+?[\d .()-]+$/', $rawPhone)) {
                continue;
            }
            $phone = $this->sheets->customerPhone($rawPhone);
            // Numeric cells in TongHop can omit the leading zero of a mobile number.
            if (preg_match('/^[35789]\d{8}$/', $phone)) {
                $phone = '0'.$phone;
            }
            if (preg_match('/^0\d{9,10}$/', $phone)) {
                $byName[$name][$code][$phone] = true;
            }
        }
        foreach ($groups as &$group) {
            if ($group['phone'] || !empty($group['phone_conflict']) || !empty($group['ambiguous'])) {
                continue;
            }
            $byCode = $byName[$this->sheets->customerName($group['name'])] ?? [];
            $phones = $byCode[$group['code']] ?? [];
            if ($group['code'] === '') {
                foreach ($byCode as $phonesForCode) {
                    $phones += $phonesForCode;
                }
            }
            if (count($phones) === 1) {
                $group['phone'] = (string) array_key_first($phones);
            }
        }
        unset($group);

        return $groups;
    }

    public function allLinks(): array
    {
        $links = $this->sheets->mappings();
        $prefix = $this->linkPrefix();
        foreach (Setting::query()->where('key', 'like', $prefix.'%')->pluck('value', 'key') as $key => $value) {
            $sourceKey = substr($key, strlen($prefix));
            if ((int) $value === 0) {
                unset($links[$sourceKey]);
            } else {
                $links[$sourceKey] = ['customer_id' => (int) $value];
            }
        }

        return $links;
    }

    public function link(string $key, int $customerId): void
    {
        Setting::set($this->linkPrefix().$key, (string) $customerId);
    }

    public function unlink(string $key): void
    {
        // An explicit override also suppresses links inherited from the operational ledger.
        Setting::set($this->linkPrefix().$key, '0');
    }

    private function linkPrefix(): string
    {
        return 'customer-sheet-opening-link:'.hash('sha256', (string) config('services.google_sheets.debt_spreadsheet_id')).':';
    }
}
