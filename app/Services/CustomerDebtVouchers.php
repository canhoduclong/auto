<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDebtVouchers
{
    public const TYPES = ['receipt' => 'Phiếu thu', 'payment-request' => 'Đề nghị thanh toán'];

    public const STATUSES = ['unpaid' => 'Chưa thanh toán', 'partial' => 'Thanh toán một phần', 'paid' => 'Đã thanh toán', 'settled' => 'Đã tất toán từ phiếu trước'];

    private function prefix(array $customer): string
    {
        $source = config('services.google_sheets.debt_identity_spreadsheet_id', config('services.google_sheets.debt_spreadsheet_id'));
        $identity = app(CustomerDebtPolicyService::class)->identity($customer);

        return 'debt_voucher_'.hash('sha256', $source.'|'.$identity).'_';
    }

    public function rows(array $customer): Collection
    {
        $prefix = $this->prefix($customer);

        return $this->balances(Setting::query()->where('key', 'like', $prefix.'%')->get(['key', 'value'])
            ->filter(fn ($setting) => str_starts_with($setting->key, $prefix))
            ->map(fn ($setting) => json_decode($setting->value, true, flags: JSON_THROW_ON_ERROR))
            ->keyBy('id')->sortBy('sequence'));
    }

    private function balances(Collection $rows): Collection
    {
        $carry = 0;

        return $rows->map(function ($row) use (&$carry) {
            $total = $carry + $row['new_amount'];
            $remaining = $total - $row['paid'];
            $row['carry'] = $carry;
            $row['total'] = $total;
            $row['remaining'] = $carry = $remaining;
            $row['status'] = $total === 0 ? 'settled' : ($remaining === 0 ? 'paid' : ($row['paid'] > 0 ? 'partial' : 'unpaid'));

            return $row;
        });
    }

    public function revision(array $customer, Collection $rows): string
    {
        return hash('sha256', json_encode([$this->prefix($customer), $customer['balance_revision'], $rows->all(),
            $customer['policy']['edit_version'] ?? null, app(CustomerDebtManualAllocationService::class)->records($customer),
        ], JSON_THROW_ON_ERROR));
    }

    private function coveredPurchases(Collection $rows): array
    {
        return [
            $rows->filter(fn ($row) => empty($row['purchase_days']) && $row['new_amount'] > 0)->max('through'),
            $rows->flatMap(fn ($row) => array_keys($row['purchase_days'] ?? []))->flip()->all(),
        ];
    }

    public function purchaseDays(array $customer, Collection $rows): Collection
    {
        [$coveredThrough, $coveredDays] = $this->coveredPurchases($rows);
        $today = today()->toDateString();
        $entries = collect($customer['entries'])->where('date', '<=', $today);
        $policy = $customer['policy'];
        $allocator = app(CustomerDebtPaymentAllocation::class);
        $allocations = $allocator->reconcile($entries->values()->all(), $policy['settlement_method'], $customer['customer_id'],
            array_filter($policy['settlement_changes'], fn ($date) => $date <= $today, ARRAY_FILTER_USE_KEY),
            app(CustomerDebtManualAllocationService::class)->records($customer));

        return $entries->where('type', 'sale')->groupBy('date')->sortKeysDesc()
            ->map(function ($sales, $date) use ($allocator, $allocations, $coveredThrough, $coveredDays) {
                $lines = $sales->map(fn ($entry) => $allocations['lines'][$allocator->key($entry)]);
                $remaining = (int) $lines->sum('remaining');
                $covered = ($coveredThrough !== null && $date <= $coveredThrough) || isset($coveredDays[$date]);

                return [
                    'date' => $date,
                    'sales' => (int) $sales->sum('amount'),
                    'due' => (int) $lines->sum('amount'),
                    'paid' => (int) $lines->sum('paid'),
                    'remaining' => $remaining,
                    'count' => $sales->count(),
                    'covered' => $covered,
                    'selectable' => ! $covered && $remaining > 0,
                ];
            });
    }

    public function suggestedAmount(array $customer, Collection $rows, string $through): int
    {
        [$coveredThrough, $coveredDays] = $this->coveredPurchases($rows);
        if ($rows->isNotEmpty() && $coveredThrough === null) {
            return $this->purchaseDays($customer, $rows)->where('date', '<=', $through)->where('selectable', true)->sum('remaining');
        }

        $sheets = app(CustomerDebtSheetService::class);

        return max(0, collect($customer['entries'])->filter(fn ($entry) => $entry['date'] <= $through
            && (! $coveredThrough || ($entry['date'] > $coveredThrough && $entry['type'] !== 'payment'))
            && ($entry['type'] !== 'sale' || ! isset($coveredDays[$entry['date']])))
            ->sum(fn ($entry) => $sheets->movement($entry)));
    }

    /** The caller holds the customer debt write lock. */
    public function save(array $customer, array $data, int $actor): array
    {
        $rows = $this->rows($customer);
        if (! hash_equals($this->revision($customer, $rows), $data['revision'])) {
            throw ValidationException::withMessages(['revision' => 'Dữ liệu phiếu hoặc công nợ đã thay đổi. Vui lòng tải lại trang.']);
        }

        $id = $data['voucher_id'] ?? (string) Str::uuid();
        $existing = $rows->get($id);
        abort_if(isset($data['voucher_id']) && ! $existing, 404);
        $previous = $rows->last();
        $sequence = $existing['sequence'] ?? (($previous['sequence'] ?? 0) + 1);
        $dates = $data['purchase_dates'] ?? [];
        // Keep the register's running cutoff while a selected voucher can include older, uncovered dates.
        $through = $existing['through'] ?? ($dates === [] ? $data['through'] : max(max($dates), $previous['through'] ?? ''));
        if (! $existing && $previous && $through < $previous['through']) {
            throw ValidationException::withMessages(['through' => 'Ngày chốt phải từ ngày chốt phiếu gần nhất trở đi.']);
        }

        $type = $existing['type'] ?? $data['type'];
        $number = mb_strtoupper(trim($data['number'] ?? ''));
        if ($number === '') {
            $number = $existing['number'] ?? sprintf('%s-%04d', $type === 'receipt' ? 'PT' : 'DNTT', $sequence);
        }
        if ($rows->except($id)->contains(fn ($row) => mb_strtoupper($row['number']) === $number)) {
            throw ValidationException::withMessages(['number' => 'Số phiếu này đã được sử dụng cho khách hàng.']);
        }

        $purchaseDays = $existing['purchase_days'] ?? [];
        if (! $existing && $dates !== []) {
            $days = $this->purchaseDays($customer, $rows)->only($dates)->where('selectable', true);
            if ($days->count() !== count($dates)) {
                throw ValidationException::withMessages(['purchase_dates' => 'Có ngày đã đưa vào phiếu, hết số phải thu hoặc không còn trong danh sách mua hàng. Vui lòng kiểm tra lại.']);
            }
            $purchaseDays = $days->sortKeys()->pluck('remaining', 'date')->all();
        }
        $newAmount = $purchaseDays !== [] ? array_sum($purchaseDays)
            : (int) ($data['new_amount'] ?? $this->suggestedAmount($customer, $rows, $through));

        $voucher = [
            'id' => $id, 'sequence' => $sequence, 'type' => $type, 'number' => $number, 'through' => $through,
            'new_amount' => $newAmount, 'purchase_days' => $purchaseDays,
            'paid' => (int) $data['paid'], 'method' => $data['method'] ?? null, 'note' => $data['note'] ?? null,
            'documents' => $existing['documents'] ?? [], 'created_at' => $existing['created_at'] ?? now()->toIso8601String(),
            'created_by' => $existing['created_by'] ?? $actor, 'updated_at' => now()->toIso8601String(), 'updated_by' => $actor,
        ];
        $updated = $this->balances($rows->put($id, $voucher)->sortBy('sequence'));
        if ($updated->contains(fn ($row) => $row['remaining'] < 0) || (! $existing && $updated[$id]['total'] <= 0)) {
            throw ValidationException::withMessages(['paid' => 'Tổng phải thu phải lớn hơn 0. Số tiền đã thu không được vượt số phải thu của phiếu này hoặc phiếu kế tiếp.']);
        }
        $files = $data['documents'] ?? [];
        if ($updated->contains(fn ($row) => $row['status'] === 'paid' && $row['documents'] === []
            && ($row['id'] !== $id || $files === []))) {
            throw ValidationException::withMessages(['documents' => 'Phiếu đã thanh toán đủ cần có chứng từ. Kiểm tra phiếu này và các phiếu kế tiếp.']);
        }

        $disk = Storage::disk('local');
        $paths = [];
        try {
            foreach ($files as $file) {
                $documentId = (string) Str::uuid();
                $path = $disk->putFileAs("customer-debt-vouchers/{$id}", $file, $documentId.'.'.$file->extension(), 'private');
                if (! $path) {
                    throw new \RuntimeException('Không thể lưu chứng từ phiếu.');
                }
                $paths[] = $path;
                $name = preg_replace('/[\x00-\x1f\x7f]/u', '', basename(str_replace('\\', '/', $file->getClientOriginalName())));
                $voucher['documents'][] = ['id' => $documentId, 'path' => $path, 'name' => Str::limit($name ?: 'Chứng từ', 200, '')];
            }
            DB::transaction(fn () => Setting::set($this->prefix($customer).$id, json_encode($voucher, JSON_THROW_ON_ERROR)));
        } catch (\Throwable $exception) {
            $disk->delete($paths);

            throw $exception;
        }

        return $voucher;
    }
}
