<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerDebtSheetService
{
    private const FILE = 'customer-debt/snapshot.json';

    public function snapshot(): ?array
    {
        $disk = Storage::disk('local');

        return $disk->exists(self::FILE) ? json_decode($disk->get(self::FILE), true, 512, JSON_THROW_ON_ERROR) : null;
    }

    public function save(array $sales, array $payments, ?int $userId, string $source, ?array $bankPayments = null): array
    {
        // Both tabs are validated before replacing the complete snapshot, avoiding double imports.
        $issues = [];
        $entries = array_merge($this->parse($sales, 'sale', $issues), $this->parse($payments, 'payment', $issues, 'Dongtienhoatdong'));
        if ($bankPayments !== null) {
            $entries = array_merge($entries, $this->parse($bankPayments, 'payment', $issues, 'TKCNMBBDUE'));
        }
        $data = ['entries' => $entries, 'gift_note_count' => count(array_filter($entries, fn ($entry) => ($entry['gift_note'] ?? '') !== '')), 'issues' => $issues, 'spreadsheet_id' => config('services.google_sheets.debt_spreadsheet_id'), 'loaded_at' => now()->toIso8601String(), 'loaded_by' => $userId, 'source' => $source, 'payment_tabs' => $bankPayments === null ? ['Dongtienhoatdong'] : ['Dongtienhoatdong', 'TKCNMBBDUE']];

        return Cache::lock('customer-debt-load', 60)->block(5, function () use ($data) {
            $disk = Storage::disk('local');
            $previous = $this->snapshot() ?? [];
            $previous = $this->withPaymentGroupKeys($previous);
            $data = $this->withPaymentGroupKeys($data);
            $batchId = (string) Str::uuid();
            $tracked = app(CustomerPaymentSyncService::class)->track($previous, $data, $batchId, $data['loaded_at']);
            $data = $tracked['snapshot'];
            $data['payment_syncs'] = array_merge([$tracked['batch']['summary']], $previous['payment_syncs'] ?? []);
            $folder = 'customer-debt/payment-updates';
            if (! is_dir($disk->path($folder))) {
                $disk->makeDirectory($folder);
                chmod($disk->path($folder), 02770);
            }
            $archive = $folder.'/'.$batchId.'.json';
            $archiveTemporary = $folder.'/'.Str::uuid().'.tmp';
            $temporary = 'customer-debt/'.Str::uuid().'.json';
            try {
                if (! $disk->put($archiveTemporary, json_encode($tracked['batch'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
                    throw new RuntimeException('Không lưu được nhật ký thanh toán.');
                }
                chmod($disk->path($archiveTemporary), 0660);
                if (! rename($disk->path($archiveTemporary), $disk->path($archive))) {
                    throw new RuntimeException('Không lưu được nhật ký thanh toán.');
                }
                if (! $disk->put($temporary, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
                    throw new RuntimeException('Không lưu được bản dữ liệu công nợ mới.');
                }
                chmod($disk->path($temporary), 0660);
                // The snapshot is the commit marker: only referenced archives are visible to users.
                if (! rename($disk->path($temporary), $disk->path(self::FILE))) {
                    throw new RuntimeException('Không lưu được dữ liệu công nợ.');
                }
            } catch (\Throwable $e) {
                $disk->delete([$archiveTemporary, $archive, $temporary]);
                throw $e;
            }

            return $tracked['batch']['summary'];
        });
    }

    private function withPaymentGroupKeys(array $data): array
    {
        $keys = [];
        foreach ($this->summarize($data['entries'] ?? []) as $group) {
            foreach ($group['entries'] as $entry) {
                if ($entry['type'] === 'payment') {
                    $keys[($entry['source_sheet'] ?? 'Dongtienhoatdong').'|'.$entry['row']] = ['key' => $group['key'], 'code' => $group['code']];
                }
            }
        }
        foreach ($data['entries'] ?? [] as $index => $entry) {
            if ($entry['type'] === 'payment') {
                $source = $keys[($entry['source_sheet'] ?? 'Dongtienhoatdong').'|'.$entry['row']] ?? [];
                $data['entries'][$index]['customer_source_key'] = $source['key'] ?? null;
                $data['entries'][$index]['customer_source_code'] = $source['code'] ?? '';
            }
        }

        return $data;
    }

    public function paymentBatch(string $id, ?array $snapshot = null): array
    {
        $snapshot ??= $this->snapshot();
        abort_unless(collect($snapshot['payment_syncs'] ?? [])->contains('id', $id), 404);
        $path = 'customer-debt/payment-updates/'.$id.'.json';
        abort_unless(Storage::disk('local')->exists($path), 404);

        return json_decode(Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function mappingPrefix(): string
    {
        return 'customer_debt_map_'.substr(hash('sha256', (string) config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'))), 0, 16).'_';
    }

    public function mappings(): array
    {
        $prefix = $this->mappingPrefix();
        $result = [];
        foreach (Setting::query()->where('key', 'like', $prefix.'%')->get(['key', 'value']) as $setting) {
            if (! str_starts_with($setting->key, $prefix)) {
                continue;
            }
            $data = json_decode($setting->value, true);
            if (isset($data['customer_id'])) {
                $result[substr($setting->key, strlen($prefix))] = $data;
            }
        }

        return $result;
    }

    public function mapCustomer(string $sourceKey, int $customerId, int $userId): void
    {
        Cache::lock('customer-debt-load', 60)->block(5, fn () => $this->mapCustomerWithinLock($sourceKey, $customerId, $userId));
    }

    /** Caller must hold customer-debt-load; allows customer creation and mapping in one transaction. */
    public function mapCustomerWithinLock(string $sourceKey, int $customerId, int $userId): void
    {
        $groups = $this->summarize($this->snapshot()['entries'] ?? []);
        if (! isset($groups[$sourceKey])) {
            throw new RuntimeException('Khách nguồn không còn trong bản dữ liệu hiện tại. Vui lòng tải lại trang.');
        }
        $this->mapSourceGroupWithinLock($groups[$sourceKey], $customerId, $userId);
    }

    /** Trusted source group from a server-side import preview; caller holds customer-debt-load. */
    public function mapSourceGroupWithinLock(array $group, int $customerId, int $userId): void
    {
        $existing = json_decode(Setting::get($this->mappingPrefix().$group['key'], '{}'), true) ?? [];
        $sync = array_key_exists('sync_preferences', $group) ? ['sync_fields' => $group['sync_preferences']] : array_intersect_key($existing, ['sync_fields' => true]);
        Setting::set($this->mappingPrefix().$group['key'], json_encode([
            'customer_id' => $customerId, 'mapped_by' => $userId, 'mapped_at' => now()->toIso8601String(),
            'source_name' => $group['name'], 'source_code' => $group['code'],
        ] + $sync, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function unmapCustomer(string $sourceKey): void
    {
        Cache::lock('customer-debt-load', 60)->block(5, fn () => Setting::query()->where('key', $this->mappingPrefix().$sourceKey)->delete());
    }

    public function textValues(string $text): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, str_replace("\xEF\xBB\xBF", '', $text));
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, null, "\t", '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    public function connectionEmail(): ?string
    {
        $path = trim((string) config('services.google_sheets.credentials'));
        if ($path === '') {
            return null;
        }
        $path = str_starts_with($path, '/') ? $path : base_path($path);
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }
        $credentials = json_decode(file_get_contents($path), true);
        $email = is_array($credentials) ? ($credentials['client_email'] ?? null) : null;

        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public function fetchSales(): array
    {
        return $this->fetchTabs(['nhatkybanhang'])[0];
    }

    public function fetch(): array
    {
        return $this->fetchTabs(['nhatkybanhang', 'dongtienhoatdong', 'tkcnmbbdue']);
    }

    private function fetchTabs(array $names): array
    {
        $path = trim((string) config('services.google_sheets.credentials'));
        if (! $path) {
            throw new RuntimeException('Chưa cấu hình tài khoản dịch vụ Google Sheets.');
        }
        $client = new Client;
        $client->setAuthConfig(str_starts_with($path, '/') ? $path : base_path($path));
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setHttpClient(new \GuzzleHttp\Client(['timeout' => 30, 'connect_timeout' => 8]));
        $sheets = new Sheets($client);
        $id = config('services.google_sheets.debt_spreadsheet_id');
        $titles = [];
        foreach ($sheets->spreadsheets->get($id, ['fields' => 'sheets.properties.title'])->getSheets() as $sheet) {
            $title = $sheet->getProperties()->getTitle();
            $titles[str_replace(' ', '', $this->normalize($title))] = $title;
        }
        $result = [];
        foreach ($names as $name) {
            if (! isset($titles[$name])) {
                throw new RuntimeException('Không tìm thấy tab '.$name.'.');
            }
            $result[] = $sheets->spreadsheets_values->get($id, "'".str_replace("'", "''", $titles[$name])."'!A:AZ", ['valueRenderOption' => 'FORMATTED_VALUE'])->getValues() ?? [];
        }

        return $result;
    }

    public function workbook(string $path): array
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
        // Preserve formatting so Excel dates and Vietnamese money parse like pasted cells.
        $book = $reader->load($path);
        try {
            $tabs = [];
            foreach ($book->getWorksheetIterator() as $sheet) {
                $name = str_replace(' ', '', $this->normalize($sheet->getTitle()));
                if (in_array($name, ['nhatkybanhang', 'dongtienhoatdong', 'tkcnmbbdue'], true)) {
                    $tabs[$name] = $sheet->toArray('', true, true, false);
                }
            }
            if (! isset($tabs['nhatkybanhang'], $tabs['dongtienhoatdong'])) {
                throw new RuntimeException('File Excel phải có hai tab nhatkybanhang và Dongtienhoatdong.');
            }

            return isset($tabs['tkcnmbbdue']) ? [$tabs['nhatkybanhang'], $tabs['dongtienhoatdong'], $tabs['tkcnmbbdue']] : [$tabs['nhatkybanhang'], $tabs['dongtienhoatdong']];
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower(Str::ascii($value))));
    }

    public function customerName(string $value): string
    {
        return \App\Models\Customer::normalizeName($value);
    }

    public function parse(array $values, string $type, ?array &$issues = null, ?string $sourceSheet = null): array
    {
        $aliases = [
            'date' => ['ngay', 'ngay thang', 'ngay giao dich', 'date'],
            'code' => ['ma kh', 'ma khach hang', 'customer code'],
            'name' => ['khach hang', 'ten khach hang', 'ten kh', 'ten kh/ncc', 'customer'],
            'sale_name' => ['nvkd', 'sale', 'nhan vien kinh doanh'],
            'phone' => ['sdt', 'so dien thoai', 'dien thoai', 'phone'],
            'address' => ['dia chi', 'address'],
            'amount' => $type === 'sale' ? ['tong tien', 'thanh tien', 'amount'] : ['tien thu', 'so tien thu', 'thu'],
            'gift' => ['tang', 'tien tang'],
            'category' => $sourceSheet === 'TKCNMBBDUE' ? ['loai', 'code'] : ['loai'],
            'direction' => ['loai giao dich', 'thu chi', 'thu/chi'],
            'generic_amount' => ['so tien', 'amount'],
            'note' => ['noi dung', 'dien giai', 'ghi chu', 'san pham', 'dvt'],
        ];
        $headers = null;
        $carry = [];
        $entries = [];
        foreach ($values as $index => $cells) {
            $candidate = [];
            foreach ($cells as $column => $label) {
                foreach ($aliases as $field => $names) {
                    if (! isset($candidate[$field]) && in_array($this->normalize((string) $label), $names, true)) {
                        $candidate[$field] = $column;
                    }
                }
            }
            if (isset($candidate['date'], $candidate['name']) && (isset($candidate['amount']) || ($type === 'payment' && isset($candidate['generic_amount'], $candidate['direction'])))) {
                $headers = $candidate;
                $carry = [];

                continue;
            }
            if (! $headers || ! array_filter($cells, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }
            $row = [];
            foreach ($headers as $field => $column) {
                $row[$field] = trim((string) ($cells[$column] ?? ''));
            }
            if (($row['date'] ?? '') !== '') {
                $carry['date'] = $row['date'];
            }
            if (in_array($this->normalize($row['name'] ?? ''), ['tong', 'tong cong', 'tong tien', 'cong', 'cong tien', 'total', 'grand total'], true)) {
                $carry = [];

                continue;
            }
            if ($type === 'payment' && isset($headers['category']) && $this->normalize($row['category'] ?? '') !== 'ttbh') {
                continue;
            }
            if ($type === 'payment' && ! isset($headers['amount'])) {
                $direction = $this->normalize($row['direction'] ?? '');
                if (in_array($direction, ['chi', 'tien chi', 'phieu chi'], true)) {
                    continue;
                }
                if (! in_array($direction, ['thu', 'tien thu', 'phieu thu'], true)) {
                    if (($row['generic_amount'] ?? '') === '') {
                        continue;
                    }
                    throw new RuntimeException('Dòng '.($index + 1).' thanh toán chưa xác định Thu/Chi.');
                }
            }
            $amount = $row['amount'] ?? $row['generic_amount'] ?? '';
            if ($amount === '' || $amount === '-') {
                continue;
            }
            if ($type === 'payment') {
                // Operating cash flow also includes expenses and non-customer receipts. Never inherit a previous payer.
                unset($carry['name'], $carry['code']);
                if (($row['name'] ?? '') === '' && ($row['code'] ?? '') === '') {
                    $issues[] = ['type' => 'payment', 'row' => $index + 1, 'date' => ($row['date'] ?? '') !== '' ? $row['date'] : ($carry['date'] ?? ''), 'amount' => $this->money($amount), 'note' => $row['note'] ?? '', 'reason' => 'Khoản thu TTBH chưa có tên / mã khách hàng', 'source_sheet' => $sourceSheet ?? 'Dongtienhoatdong', 'sale_name' => $row['sale_name'] ?? ''];

                    continue;
                }
            }
            if (($row['name'] ?? '') !== '') {
                $carry['name'] = $row['name'];
                $carry['code'] = $row['code'] ?? '';
            } elseif (($row['code'] ?? '') !== '') {
                $carry['code'] = $row['code'];
                unset($carry['name']);
            }
            if (($row['date'] ?? '') !== '') {
                $carry['date'] = $row['date'];
            }
            foreach (['date', 'name', 'code'] as $field) {
                if (($row[$field] ?? '') === '') {
                    $row[$field] = $carry[$field] ?? '';
                }
            }
            if ($row['name'] === '' || $row['date'] === '') {
                throw new RuntimeException('Dòng '.($index + 1).' thiếu ngày hoặc khách hàng.');
            }
            $date = null;
            foreach (['d/m/Y', 'j/n/Y', 'd/n/Y', 'j/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
                try {
                    $parsed = CarbonImmutable::createFromFormat('!'.$format, $row['date']);
                    if ($parsed && $parsed->format($format) === $row['date']) {
                        $date = $parsed->toDateString();
                        break;
                    }
                } catch (\Throwable) {
                }
            }
            if (! $date) {
                throw new RuntimeException('Ngày không hợp lệ ở dòng '.($index + 1).'.');
            }
            if (str_starts_with($row['code'], '#')) {
                $row['code'] = '';
            }
            $gift = $type === 'sale' ? trim($row['gift'] ?? '') : '';
            $giftNote = preg_match('/^\p{L}/u', $gift) ? $gift : '';
            $giftAmount = $gift === '' || $gift === '-' || $giftNote !== '' ? 0 : $this->money($gift);
            $note = $row['note'] ?? '';
            if ($giftNote !== '') {
                $note = ($note !== '' ? $note.' · ' : '').'Ghi chú cột Tặng: '.$giftNote;
            }
            $entries[] = ['date' => $date, 'code' => $row['code'], 'name' => $row['name'], 'type' => $type, 'sale_name' => $row['sale_name'] ?? '', 'phone' => $row['phone'] ?? '', 'address' => $row['address'] ?? '', 'amount' => $this->money($amount), 'gift' => $giftAmount, 'gift_note' => $giftNote, 'note' => $note, 'row' => $index + 1, 'source_sheet' => $sourceSheet ?? ($type === 'sale' ? 'nhatkybanhang' : 'Dongtienhoatdong')];
        }
        if (! $headers) {
            throw new RuntimeException('Không nhận diện tiêu đề tab '.($type === 'sale' ? 'nhatkybanhang (Ngày, Mã KH, Khách hàng, Tổng tiền).' : 'Dongtienhoatdong (Ngày, Mã KH, Khách hàng, Tiền thu hoặc Số tiền và Thu/Chi).'));
        }
        if (! $entries && $sourceSheet !== 'TKCNMBBDUE') {
            throw new RuntimeException('Tab '.($type === 'sale' ? 'lấy hàng' : 'thanh toán').' không có giao dịch hợp lệ; dữ liệu cũ được giữ lại.');
        }

        return $entries;
    }

    public function money(string $value): int
    {
        $value = preg_replace('/[\s\x{00a0}₫đ]/u', '', trim($value));
        if (! preg_match('/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,2})?$/', $value)) {
            throw new RuntimeException('Số tiền không hợp lệ: '.$value.'. Dùng định dạng 1.234.567 hoặc 1234567.');
        }

        return (int) round((float) str_replace(',', '.', str_replace('.', '', $value)));
    }

    public function movement(array $entry): int
    {
        if ($entry['type'] === 'adjustment') {
            return $entry['amount'];
        }

        return $entry['type'] === 'sale' ? $entry['amount'] - ($entry['gift'] ?? 0) : -$entry['amount'];
    }

    public function summarize(array $entries): array
    {
        $groups = [];
        // Codes are authoritative. Name fallback only links to exactly one known code.
        $codes = [];
        foreach ($entries as $entry) {
            if ($entry['code'] !== '') {
                $codes[$this->customerName($entry['name'])][mb_strtoupper(trim($entry['code']))] = true;
            }
        }
        foreach ($entries as $entry) {
            $name = $this->customerName($entry['name']);
            $code = mb_strtoupper(trim($entry['code']));
            if ($code === '' && count($codes[$name] ?? []) === 1) {
                $code = array_key_first($codes[$name]);
            }
            $key = hash('sha256', $code !== '' ? 'code:'.$code : 'name:'.$name);
            $groups[$key] ??= ['key' => $key, 'code' => $code, 'name' => $entry['name'], 'sales' => 0, 'gifts' => 0, 'payments' => 0, 'balance' => 0, 'last_purchase_date' => null, 'last_payment_date' => null, 'sale_name' => '', 'sale_rank' => '', 'entries' => [], 'ambiguous' => $code === '' && count($codes[$name] ?? []) > 1];
            $groups[$key][$entry['type'] === 'sale' ? 'sales' : 'payments'] += $entry['amount'];
            if ($entry['type'] === 'sale') {
                $groups[$key]['gifts'] += $entry['gift'] ?? 0;
            }
            if ($entry['amount'] > 0) {
                $dateField = $entry['type'] === 'sale' ? 'last_purchase_date' : 'last_payment_date';
                $groups[$key][$dateField] = max($groups[$key][$dateField] ?? '', $entry['date']);
            }
            $saleName = trim($entry['sale_name'] ?? '');
            $saleRank = $entry['date'].'|'.str_pad((string) $entry['row'], 10, '0', STR_PAD_LEFT);
            if ($entry['type'] === 'sale' && $saleName !== '' && ! str_starts_with($saleName, '#') && $saleRank > $groups[$key]['sale_rank']) {
                $groups[$key]['sale_name'] = $saleName;
                $groups[$key]['sale_rank'] = $saleRank;
            }
            $groups[$key]['entries'][] = $entry;
        }
        foreach ($groups as &$group) {
            $group['balance'] = $group['sales'] - $group['gifts'] - $group['payments'];
            usort($group['entries'], fn ($a, $b) => [$a['date'], $a['type'], $a['row']] <=> [$b['date'], $b['type'], $b['row']]);
        }
        unset($group);
        uasort($groups, fn ($a, $b) => $b['balance'] <=> $a['balance']);

        return $groups;
    }
}
