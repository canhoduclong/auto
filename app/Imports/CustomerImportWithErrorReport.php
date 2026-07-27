<?php
namespace App\Imports;

use App\Models\Customer;
use App\Services\CustomerPriorityService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CustomerImportWithErrorReport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;
    public $imported = [];
    protected $userId;

    private const FIELD_ALIASES = [
        'name' => ['ten khach hang', 'name', 'customer name'],
        'email' => ['email'],
        'phone' => ['so dien thoai', 'phone', 'dien thoai', 'sdt'],
        'address' => ['dia chi', 'address'],
        'delivery_time' => ['thoi gian giao hang', 'delivery time', 'delivery_time'],
        'size' => ['size'],
        'production' => ['san luong', 'production'],
    ];

    public function __construct()
    {
        $this->userId = auth()->id();
    }

    public function model(array $row)
    {
        try {
            $data = $this->extractDataFromRow($row);

            // Validate required fields
            if (empty($data['name'])) {
                throw new \Exception('Trường "Tên khách hàng" (name) là bắt buộc.');
            }
            if (empty($data['address'])) {
                throw new \Exception('Trường "Địa chỉ" (address) là bắt buộc.');
            }

            $nameDuplicate = Customer::query()
                ->where('name_normalized', Customer::normalizeName($data['name']))
                ->first();
            if ($nameDuplicate) {
                if ($this->userId) {
                    app(CustomerPriorityService::class)->attachSale($nameDuplicate, (int) $this->userId, 2, 'duplicate_join');
                }
                throw new \Exception('Tên khách hàng đã tồn tại. Đã thêm sale import vào Priority 2 của khách trùng.');
            }

            if (!empty($data['email'])) {
                $emailExists = Customer::query()->where('email', $data['email'])->exists();
                if ($emailExists) {
                    $existing = Customer::query()->where('email', $data['email'])->first();
                    if ($existing && $this->userId) {
                        app(CustomerPriorityService::class)->attachSale($existing, (int) $this->userId, 2, 'duplicate_join');
                    }
                    throw new \Exception('Email đã tồn tại. Đã thêm sale import vào Priority 2 của khách trùng.');
                }
            }

            if (!empty($data['phone'])) {
                $phoneDuplicate = Customer::query()->where('phone', $data['phone'])->first();
                if ($phoneDuplicate && $this->userId) {
                    app(CustomerPriorityService::class)->attachSale($phoneDuplicate, (int) $this->userId, 2, 'duplicate_join');
                    throw new \Exception('Số điện thoại đã tồn tại. Đã thêm sale import vào Priority 2 của khách trùng.');
                }
            }

            $customer = new Customer([
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'delivery_time' => $data['delivery_time'] ?? null,
                'size' => $data['size'] ?? null,
                'production' => $data['production'] ?? null,
                'assigned_to' => $this->userId,
                'assigned_at' => $this->userId ? now() : null,
                'current_owner_sale_id' => $this->userId,
                'customer_status' => $this->userId ? 'active' : 'free',
                'free_from_date' => $this->userId ? null : now(),
                'current_cycle_no' => 1,
            ]);
            $customer->save();
            if ($this->userId) {
                app(CustomerPriorityService::class)->attachSale($customer, (int) $this->userId, 1, 'created');
            }
            \App\Models\CustomerAddress::create([
                'customer_id' => $customer->id,
                'note' => $data['address'],
                'is_default' => 1,
            ]);
            $this->imported[] = [
                'row' => $row,
                'status' => 'success',
                'customer_id' => $customer->id,
            ];
            return $customer;
        } catch (\Exception $e) {
            $this->imported[] = [
                'row' => $row,
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
            return null;
        }
    }

    private function extractDataFromRow(array $row): array
    {
        $normalizedRow = [];
        foreach ($row as $column => $value) {
            $normalizedKey = $this->normalizeHeader((string) $column);
            if ($normalizedKey !== '') {
                $normalizedRow[$normalizedKey] = $value;
            }
        }

        $data = [];
        foreach (self::FIELD_ALIASES as $field => $aliases) {
            $data[$field] = $this->pickFirstValue($normalizedRow, $aliases);
        }

        // Fallback: một số file xlsx không parse được heading và trả row dạng index 0,1,2...
        // Cố gắng map theo vị trí cột mẫu: 0-name, 1-email, 2-phone, 3-address, 4-delivery_time, 5-size, 6-production.
        if ((empty($data['name']) || empty($data['address'])) && $this->hasNumericIndexedValues($row)) {
            $indexed = array_values($row);
            $data['name'] = $data['name'] ?: $this->valueAt($indexed, 0);
            $data['email'] = $data['email'] ?: $this->valueAt($indexed, 1);
            $data['phone'] = $data['phone'] ?: $this->valueAt($indexed, 2);
            $data['address'] = $data['address'] ?: $this->valueAt($indexed, 3);
            $data['delivery_time'] = $data['delivery_time'] ?: $this->valueAt($indexed, 4);
            $data['size'] = $data['size'] ?: $this->valueAt($indexed, 5);
            $data['production'] = $data['production'] ?: $this->valueAt($indexed, 6);
        }

        if ($data['phone'] !== null) {
            // Keep phone as plain text; if Excel gave a float/int, remove scientific notation artifacts.
            $data['phone'] = preg_replace('/\.0+$/', '', trim((string) $data['phone'])) ?? trim((string) $data['phone']);
        }

        if ($data['delivery_time'] !== null && is_numeric($data['delivery_time'])) {
            $fraction = (float) $data['delivery_time'];
            if ($fraction >= 0 && $fraction < 1) {
                $seconds = (int) round($fraction * 86400);
                $hours = (int) floor($seconds / 3600);
                $minutes = (int) floor(($seconds % 3600) / 60);
                $data['delivery_time'] = sprintf('%02d:%02d', $hours, $minutes);
            }
        }

        if ($data['email'] !== null) {
            $data['email'] = trim((string) $data['email']);
        }

        if ($data['name'] !== null) {
            $data['name'] = trim((string) $data['name']);
        }

        if ($data['address'] !== null) {
            $data['address'] = trim((string) $data['address']);
        }

        return $data;
    }

    private function hasNumericIndexedValues(array $row): bool
    {
        if ($row === []) {
            return false;
        }

        $keys = array_keys($row);
        foreach ($keys as $key) {
            if (!is_int($key) && !ctype_digit((string) $key)) {
                return false;
            }
        }

        return true;
    }

    private function valueAt(array $values, int $index): mixed
    {
        if (!array_key_exists($index, $values)) {
            return null;
        }

        $value = $values[$index];
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text === '' ? null : $value;
    }

    private function pickFirstValue(array $normalizedRow, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeHeader($alias);
            if ($key !== '' && array_key_exists($key, $normalizedRow)) {
                $value = $normalizedRow[$key];
                if ($value !== null && trim((string) $value) !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function normalizeHeader(string $header): string
    {
        $header = str_replace("\xEF\xBB\xBF", '', $header);
        $header = trim(mb_strtolower($header));

        if ($header === '') {
            return '';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
        if ($ascii === false) {
            $ascii = $header;
        }

        $ascii = strtolower($ascii);
        $ascii = str_replace(['_', '-', '.'], ' ', $ascii);
        $ascii = preg_replace('/[^a-z0-9 ]+/', ' ', $ascii) ?? $ascii;
        $ascii = preg_replace('/\s+/', ' ', $ascii) ?? $ascii;

        return trim($ascii);
    }

    public function rules(): array
    {
        return [
            '*.name' => 'nullable|string|max:255',
            '*.ten_khach_hang' => 'nullable|string|max:255',
            '*.phone' => 'nullable',
            '*.so_dien_thoai' => 'nullable',
            '*.address' => 'nullable|string',
            '*.dia_chi' => 'nullable|string',
            '*.email' => 'nullable|email|max:255',
            '*.delivery_time' => 'nullable',
            '*.thoi_gian_giao_hang' => 'nullable',
            '*.size' => 'nullable',
            '*.san_luong' => 'nullable|numeric',
            '*.production' => 'nullable|numeric',
        ];
    }
    public function getImported()
    {
        return $this->imported;
    }
}
