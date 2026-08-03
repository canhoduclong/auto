<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerTextImportService
{
    private const HEADER_ALIASES = [
        'customer_code' => ['ma kh', 'ma khach hang', 'customer code', 'customer_code'],
        'name' => ['khach hang', 'ten khach hang', 'ho va ten', 'name', 'customer name'],
        'sale' => ['nvkd', 'nhan vien kinh doanh', 'sale', 'salesperson'],
        'phone' => ['sdt', 'so dien thoai', 'dien thoai', 'phone'],
        'address' => ['dia chi', 'address'],
    ];

    public function preview(string $text, User $actor, array $saleMappings = []): array
    {
        $parsed = $this->parse($text);
        $saleUsers = $this->salesUsers();
        $sales = $this->salesMap($saleUsers);
        $salesById = $saleUsers->keyBy(fn (User $user): string => (string) $user->id);
        $seenNames = [];
        $seenCodes = [];
        $rows = [];
        $mappingRows = [];

        foreach ($parsed['rows'] as $index => $data) {
            $line = $index + 2;
            $errors = [];
            $warnings = [];
            $data['customer_code'] = $this->cleanText($data['customer_code'] ?? null);
            $data['name'] = $this->cleanText($data['name'] ?? null);
            $data['sale'] = $this->cleanText($data['sale'] ?? null);
            $data['phone'] = $this->normalizePhone($data['phone'] ?? null);
            $data['address'] = $this->cleanText($data['address'] ?? null);

            if (!$data['name']) {
                $errors[] = 'Thiếu tên khách hàng.';
            }

            $normalizedName = Customer::normalizeName($data['name']);
            if ($normalizedName !== '' && isset($seenNames[$normalizedName])) {
                $errors[] = 'Trùng tên với dòng '.$seenNames[$normalizedName].' trong dữ liệu nhập.';
            } elseif ($normalizedName !== '') {
                $seenNames[$normalizedName] = $line;
            }

            if ($data['customer_code']) {
                $codeKey = mb_strtolower($data['customer_code']);
                if (isset($seenCodes[$codeKey])) {
                    $errors[] = 'Trùng Mã KH với dòng '.$seenCodes[$codeKey].' trong dữ liệu nhập.';
                } else {
                    $seenCodes[$codeKey] = $line;
                }
            }

            $mappingKey = $this->mappingKey($data['sale']);
            $automaticSale = $sales[$this->key($data['sale'])] ?? null;
            $selectedSaleId = $mappingKey ? (int) ($saleMappings[$mappingKey] ?? 0) : 0;
            $selectedSale = $selectedSaleId ? $salesById->get((string) $selectedSaleId) : null;
            $saleUser = $actor->isAdmin() ? ($selectedSale ?: $automaticSale) : $actor;

            if ($actor->isAdmin() && $data['sale'] && !isset($mappingRows[$mappingKey])) {
                $mappingRows[$mappingKey] = [
                    'key' => $mappingKey,
                    'imported_name' => $data['sale'],
                    'automatic_user_id' => $automaticSale?->id,
                    'selected_user_id' => $saleUser?->id,
                    'selected_user_name' => $saleUser?->name,
                ];
            }

            if ($actor->isAdmin() && $data['sale'] && !$saleUser) {
                $errors[] = 'Chưa ánh xạ NVKD “'.$data['sale'].'” với nhân viên hệ thống.';
            }
            if ($actor->isAdmin() && $selectedSaleId && !$selectedSale) {
                $errors[] = 'Nhân viên được ánh xạ không thuộc nhóm sale hợp lệ.';
            }
            if ($actor->isAdmin() && !$data['sale']) {
                $warnings[] = 'Không có NVKD; khách sẽ ở trạng thái tự do.';
            }

            $existing = $this->findExisting($data);
            if ($existing && $existing->trashed()) {
                $errors[] = 'Khách hàng đã nằm trong thùng rác (ID '.$existing->id.').';
            }
            if ($existing && $data['customer_code'] && Customer::normalizeName($existing->name) !== $normalizedName) {
                $errors[] = 'Mã KH đã thuộc về “'.$existing->name.'”; không thể ghép với tên khác.';
            }
            if ($existing && $existing->customer_code && $data['customer_code'] && strcasecmp($existing->customer_code, $data['customer_code']) !== 0) {
                $errors[] = 'Khách này đang có Mã KH “'.$existing->customer_code.'”; không thể đổi sang mã khác.';
            }
            if ($existing && $existing->phone && $data['phone'] && $existing->phone !== $data['phone']) {
                $warnings[] = 'SĐT hiện có là '.$existing->phone.'; hệ thống sẽ giữ nguyên.';
            }
            if ($existing && $existing->assigned_to && $saleUser && (int) $existing->assigned_to !== (int) $saleUser->id) {
                $warnings[] = 'Khách đã có NVKD phụ trách; hệ thống sẽ không đổi người phụ trách.';
            }

            $action = $existing ? 'update' : 'create';
            if ($existing && !$this->hasSupplementalChanges($existing, $data, $saleUser)) {
                $action = 'skip';
            }

            $rows[] = [
                'line' => $line,
                'data' => $data,
                'sale_user_id' => $saleUser?->id,
                'sale_user_name' => $saleUser?->name,
                'existing_id' => $existing?->id,
                'action' => $errors ? 'error' : $action,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        return [
            'headers' => $parsed['headers'],
            'rows' => $rows,
            'counts' => collect($rows)->countBy('action')->all(),
            'sale_mappings' => array_values($mappingRows),
        ];
    }

    public function import(string $text, User $actor, array $saleMappings = []): array
    {
        $preview = $this->preview($text, $actor, $saleMappings);
        if (($preview['counts']['error'] ?? 0) > 0) {
            return $preview + ['imported' => false];
        }

        DB::transaction(function () use (&$preview, $actor): void {
            foreach ($preview['rows'] as &$row) {
                if ($row['action'] === 'skip') {
                    continue;
                }

                $data = $row['data'];
                $saleId = $row['sale_user_id'];
                $customer = $row['existing_id']
                    ? Customer::query()->findOrFail($row['existing_id'])
                    : new Customer();

                if (!$customer->exists) {
                    $customer->fill([
                        'user_id' => $actor->id,
                        'name' => $data['name'],
                        'customer_code' => $data['customer_code'],
                        'phone' => $data['phone'],
                        'assigned_to' => $saleId,
                        'current_owner_sale_id' => $saleId,
                        'assigned_at' => $saleId ? now() : null,
                        'customer_status' => $saleId ? 'active' : 'free',
                        'free_from_date' => $saleId ? null : now(),
                        'current_cycle_no' => 1,
                        'status' => 'active',
                    ]);
                } else {
                    foreach (['customer_code', 'phone'] as $field) {
                        if (!$customer->{$field} && $data[$field]) {
                            $customer->{$field} = $data[$field];
                        }
                    }
                    if (!$customer->assigned_to && $saleId) {
                        $customer->assigned_to = $saleId;
                        $customer->current_owner_sale_id = $saleId;
                        $customer->assigned_at = now();
                        $customer->customer_status = 'active';
                        $customer->free_from_date = null;
                    }
                }
                $customer->save();

                if ($data['address'] && !$customer->addresses()->where('note', $data['address'])->exists()) {
                    CustomerAddress::create([
                        'customer_id' => $customer->id,
                        'note' => $data['address'],
                        'is_default' => !$customer->addresses()->exists(),
                    ]);
                }

                if ($saleId) {
                    app(CustomerPriorityService::class)->attachSale(
                        $customer,
                        (int) $saleId,
                        (int) $customer->assigned_to === (int) $saleId ? 1 : 2,
                        $row['existing_id'] ? 'text_import_update' : 'text_import_created'
                    );
                }

                $row['customer_id'] = $customer->id;
            }
            unset($row);
        });

        $preview['imported'] = true;
        return $preview;
    }

    private function parse(string $text): array
    {
        $lines = preg_split('/\R/u', str_replace("\xEF\xBB\xBF", '', trim($text))) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));
        if ($lines === []) {
            throw new \InvalidArgumentException('Vui lòng dán dữ liệu khách hàng vào ô nhập.');
        }

        $rawHeaders = $this->splitLine(array_shift($lines));
        $headers = array_map(fn ($header) => $this->resolveHeader((string) $header), $rawHeaders);
        if (!in_array('name', $headers, true)) {
            throw new \InvalidArgumentException('Không tìm thấy cột “Khách Hàng” hoặc “Tên khách hàng”.');
        }

        $rows = [];
        foreach ($lines as $line) {
            $values = $this->splitLine($line);
            $row = [];
            foreach ($headers as $position => $field) {
                if ($field) {
                    $row[$field] = $values[$position] ?? null;
                }
            }
            if (collect($row)->filter(fn ($value) => $this->cleanText($value) !== null)->isNotEmpty()) {
                $rows[] = $row;
            }
        }

        if ($rows === []) {
            throw new \InvalidArgumentException('Không có dòng dữ liệu khách hàng nào bên dưới tiêu đề.');
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function splitLine(string $line): array
    {
        // Không trim đầu dòng vì tab đầu tiên biểu thị ô Mã KH đang để trống.
        $line = rtrim($line, "\r\n");
        $line = preg_replace('/^\|\s*|\s*\|$/u', '', $line) ?? $line;
        $parts = str_contains($line, "\t")
            ? explode("\t", $line)
            : (preg_split('/\s{2,}|\s*\|\s*/u', $line) ?: []);

        return array_map(fn ($value) => trim(preg_replace('/\*\*/', '', (string) $value) ?? (string) $value), $parts);
    }

    private function resolveHeader(string $header): ?string
    {
        $key = $this->key($header);
        foreach (self::HEADER_ALIASES as $field => $aliases) {
            if (in_array($key, array_map(fn ($alias) => $this->key($alias), $aliases), true)) {
                return $field;
            }
        }
        return null;
    }

    private function salesUsers()
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn(DB::raw('LOWER(name)'), ['sale', 'leader', 'leader_sale', 'sale_manager']))
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'email']);
    }

    private function salesMap($users): array
    {
        $map = [];
        $users->each(function (User $user) use (&$map): void {
                foreach ([$user->name, $user->short_name] as $name) {
                    if ($name) {
                        $key = $this->key($name);
                        // Tên ngắn có thể bị trùng giữa nhiều tài khoản; khi đó bắt buộc admin chọn thủ công.
                        if (!array_key_exists($key, $map)) {
                            $map[$key] = $user;
                        } elseif (!$map[$key] || (int) $map[$key]->id !== (int) $user->id) {
                            $map[$key] = null;
                        }
                    }
                }
            });
        return $map;
    }

    private function mappingKey(?string $importedName): string
    {
        return sha1($this->key($importedName));
    }

    private function findExisting(array $data): ?Customer
    {
        if ($data['customer_code']) {
            $byCode = Customer::withTrashed()->where('customer_code', $data['customer_code'])->first();
            if ($byCode) {
                return $byCode;
            }
        }
        return Customer::withTrashed()->where('name_normalized', Customer::normalizeName($data['name']))->first();
    }

    private function hasSupplementalChanges(Customer $customer, array $data, ?User $sale): bool
    {
        if ((!$customer->customer_code && $data['customer_code']) || (!$customer->phone && $data['phone'])) {
            return true;
        }
        if (!$customer->assigned_to && $sale) {
            return true;
        }
        return (bool) ($data['address'] && !$customer->addresses()->where('note', $data['address'])->exists());
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = $this->cleanText($phone);
        if (!$phone) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? $phone;
        if (!str_starts_with($digits, '0') && in_array(strlen($digits), [8, 9], true)) {
            $digits = '0'.$digits;
        }
        return $digits;
    }

    private function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(preg_replace('/\*\*/', '', (string) $value) ?? (string) $value);
        return $value === '' ? null : $value;
    }

    private function key(?string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', Str::ascii((string) $value)) ?? ''));
    }
}
