<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SupplierSheetDebtService
{
    private const SNAPSHOT = 'supplier-debt/snapshot.json';

    public function snapshot(): ?array
    {
        $disk = Storage::disk('local');

        return $disk->exists(self::SNAPSHOT) ? json_decode($disk->get(self::SNAPSHOT), true, 512, JSON_THROW_ON_ERROR) : null;
    }

    public function refresh(CustomerDebtSheetService $sheets): array
    {
        return Cache::lock('supplier-sheet-debt-refresh', 90)->block(5, function () use ($sheets) {
            $rows = $this->parse($sheets->fetchPurchases(), $sheets);
            $snapshot = ['loaded_at' => now()->toIso8601String(), 'rows' => $rows];
            $disk = Storage::disk('local');
            if (! is_dir($disk->path('supplier-debt'))) {
                $disk->makeDirectory('supplier-debt');
                chmod($disk->path('supplier-debt'), 02770);
            }
            $temporary = 'supplier-debt/'.Str::uuid().'.tmp';
            try {
                if (! $disk->put($temporary, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
                    || ! chmod($disk->path($temporary), 0660)
                    || ! rename($disk->path($temporary), $disk->path(self::SNAPSHOT))) {
                    throw new RuntimeException('Không lưu được dữ liệu nhà cung cấp.');
                }
            } finally {
                $disk->delete($temporary);
            }

            return $snapshot;
        });
    }

    public function pinnedSuppliers(): array
    {
        $prefix = $this->pinPrefix();
        return Setting::query()->where('key', 'like', $prefix.'%')->pluck('key')
            ->filter(fn ($key) => str_starts_with($key, $prefix))
            ->mapWithKeys(fn ($key) => [substr($key, strlen($prefix)) => true])->all();
    }

    public function setPinned(string $supplier, bool $pinned): void
    {
        $key = $this->pinPrefix().$supplier;
        Cache::lock($key, 10)->block(5, function () use ($key, $pinned) {
            if ($pinned) {
                Setting::set($key, '1');
            } else {
                Setting::query()->where('key', $key)->delete();
            }
        });
    }

    private function pinPrefix(): string
    {
        return 'supplier_debt_pin_'.substr(hash('sha256', (string) config('services.google_sheets.debt_spreadsheet_id')), 0, 16).'_';
    }

    public function parse(array $values, CustomerDebtSheetService $sheets): array
    {
        $header = array_map(fn ($value) => $sheets->normalize((string) $value), $values[0] ?? []);
        foreach ([0 => 'ngay/thang/nam', 1 => 'ncc', 2 => 'san pham', 3 => 'so con', 5 => 'khoi luong', 6 => 'gia thanh', 7 => 'tong tien', 8 => 'tinh trang'] as $column => $label) {
            if (($header[$column] ?? null) !== $label) {
                throw new RuntimeException('Tiêu đề tab nhatkymuahang đã thay đổi. Vui lòng kiểm tra cấu trúc cột.');
            }
        }

        $rows = [];
        foreach (array_slice($values, 1, null, true) as $index => $cells) {
            $date = trim((string) ($cells[0] ?? ''));
            $supplier = trim((string) ($cells[1] ?? ''));
            $item = trim((string) ($cells[2] ?? ''));
            $rawAmount = trim((string) ($cells[7] ?? ''));
            if ($date === '' && in_array($rawAmount, ['', '-', '0'], true)) {
                continue;
            }
            if ($supplier === '' || $item === '' || ! preg_match('~^(\d{1,2})/(\d{1,2})/(\d{4})$~', $date, $parts)
                || ! checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3]) || $rawAmount === '') {
                throw new RuntimeException('Dòng '.($index + 1).' thiếu ngày, nhà cung cấp, mặt hàng hoặc thành tiền.');
            }

            try {
                $amount = $sheets->money($rawAmount);
                $quantity = $this->measure((string) ($cells[3] ?? ''));
                $weight = $this->measure((string) ($cells[5] ?? ''));
                $price = $this->measure((string) ($cells[6] ?? ''));
            } catch (RuntimeException $error) {
                throw new RuntimeException('Dòng '.($index + 1).': '.$error->getMessage(), 0, $error);
            }
            $statuses = array_map(fn ($value) => $sheets->normalize((string) $value), [$cells[8] ?? '', $cells[12] ?? '']);
            $paid = array_intersect($statuses, ['dtt', 'da tt', 'da thanh toan']) !== [];
            $unpaid = array_intersect($statuses, ['ctt', 'chua tt', 'chua thanh toan']) !== [];
            $paymentStatus = $paid === $unpaid ? 'unknown' : ($paid ? 'paid' : 'unpaid');
            $rows[] = [
                'row' => $index + 1, 'date' => sprintf('%04d-%02d-%02d', $parts[3], $parts[2], $parts[1]),
                'supplier_key' => hash('sha256', $sheets->customerName($supplier)), 'supplier' => $supplier,
                'item' => $item, 'variant' => trim((string) ($cells[14] ?? '')), 'invoice' => trim((string) ($cells[10] ?? '')),
                'quantity' => $quantity, 'weight' => $weight, 'price' => $price, 'amount' => $amount, 'payment_status' => $paymentStatus,
            ];
        }
        if ($rows === []) {
            throw new RuntimeException('Tab nhatkymuahang chưa có dòng mua hàng.');
        }

        return $rows;
    }

    private function measure(string $value): ?float
    {
        $value = preg_replace('/\s+/u', '', trim($value));
        if (in_array($value, ['', '-'], true)) {
            return null;
        }
        if (! preg_match('/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/', $value)) {
            throw new RuntimeException('Số lượng, khối lượng hoặc đơn giá không hợp lệ.');
        }

        return (float) str_replace(',', '.', str_replace('.', '', $value));
    }
}
