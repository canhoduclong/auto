<?php

namespace App\Services;

use App\Models\AccountingSalesEntry;
use App\Models\AccountingSalesImportBatch;
use App\Models\AccountingReconciliation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AccountingSalesImportService
{
    private ?Collection $importProductCatalog = null;

    private const ALIASES = [
        'date' => ['ngay thang', 'ngay', 'date'],
        'month' => ['thang', 'month'],
        'customer_code' => ['ma kh', 'ma khach hang', 'customer code'],
        'customer_name' => ['khach hang', 'ten khach hang', 'customer'],
        'sale_name' => ['nvkd', 'sale', 'nhan vien kinh doanh'],
        'unit' => ['dvt', 'don vi tinh', 'unit'],
        'quantity' => ['sl', 'so luong', 'quantity'],
        'unit_weight' => ['kg/con', 'kg con', 'trong luong', 'unit weight'],
        'total_quantity' => ['tong', 'tong kg', 'total'],
        'unit_price' => ['don gia', 'unit price'],
        'total_amount' => ['tong tien', 'thanh tien', 'amount'],
    ];

    public function preview(string $text, User $actor, array $saleMappings = []): array
    {
        $parsed = $this->parse($text);
        $sales = $this->saleUsers();
        [$saleAliases, $salesById] = $this->saleMaps($sales);
        $mappingRows = [];
        $rows = [];
        $seenCustomerCodes = [];
        $seenCustomerNames = [];

        foreach ($parsed as $index => $raw) {
            $line = $index + 2;
            $errors = [];
            $warnings = [];
            $date = $this->parseDate($raw['date'] ?? null);
            if (!$date) $errors[] = 'Ngày không hợp lệ, cần định dạng dd/mm/yyyy.';

            $customerCode = $this->clean($raw['customer_code'] ?? null);
            $customerName = $this->clean($raw['customer_name'] ?? null);
            $importedSaleName = $this->clean($raw['sale_name'] ?? null);
            $unit = $this->clean($raw['unit'] ?? null);
            if (!$customerName) $errors[] = 'Thiếu tên khách hàng.';
            if (!$importedSaleName) $errors[] = 'Thiếu NVKD.';
            if (!$unit) $errors[] = 'Thiếu DVT.';

            $normalizedCustomerName = Customer::normalizeName($customerName);
            if ($customerCode && isset($seenCustomerCodes[mb_strtolower($customerCode)])
                && $seenCustomerCodes[mb_strtolower($customerCode)]['name'] !== $normalizedCustomerName) {
                $errors[] = 'Mã KH trùng với khách khác ở dòng '.$seenCustomerCodes[mb_strtolower($customerCode)]['line'].'.';
            }
            if ($customerCode && isset($seenCustomerNames[$normalizedCustomerName])
                && strcasecmp($seenCustomerNames[$normalizedCustomerName]['code'], $customerCode) !== 0) {
                $errors[] = 'Khách hàng có Mã KH khác ở dòng '.$seenCustomerNames[$normalizedCustomerName]['line'].'.';
            }
            if ($customerCode && $normalizedCustomerName !== '') {
                $seenCustomerCodes[mb_strtolower($customerCode)] ??= ['name' => $normalizedCustomerName, 'line' => $line];
                $seenCustomerNames[$normalizedCustomerName] ??= ['code' => $customerCode, 'line' => $line];
            }

            $mappingKey = $this->mappingKey($importedSaleName);
            $automaticSale = $saleAliases[$this->key($importedSaleName)] ?? null;
            $selectedId = (int) ($saleMappings[$mappingKey] ?? 0);
            $selectedSale = $selectedId ? $salesById->get((string) $selectedId) : null;
            $sale = $selectedSale ?: $automaticSale;
            if ($importedSaleName && !isset($mappingRows[$mappingKey])) {
                $mappingRows[$mappingKey] = [
                    'key' => $mappingKey,
                    'imported_name' => $importedSaleName,
                    'automatic_user_id' => $automaticSale?->id,
                    'selected_user_id' => $sale?->id,
                    'selected_user_name' => $sale?->name,
                ];
            }
            if ($importedSaleName && !$sale) $errors[] = 'Chưa ánh xạ NVKD “'.$importedSaleName.'”.';
            if ($selectedId && !$selectedSale) $errors[] = 'Tài khoản ánh xạ không thuộc nhóm kinh doanh.';

            $customer = $this->findCustomer($customerCode, $customerName);
            if ($customer?->trashed()) {
                $errors[] = 'Khách hàng đang nằm trong thùng rác.';
            } elseif ($customer && $customerCode && $customer->customer_code && strcasecmp($customer->customer_code, $customerCode) !== 0) {
                $errors[] = 'Khách đang có Mã KH “'.$customer->customer_code.'”, khác mã nhập “'.$customerCode.'”.';
            } elseif ($customer && $customerCode && Customer::normalizeName($customer->name) !== Customer::normalizeName($customerName)) {
                $errors[] = 'Mã KH “'.$customerCode.'” đang thuộc khách “'.$customer->name.'”.';
            }

            $quantity = $this->parseNumber($raw['quantity'] ?? null, false);
            $unitWeight = $this->parseNumber($raw['unit_weight'] ?? null, false);
            $totalQuantity = $this->parseNumber($raw['total_quantity'] ?? null, false);
            $unitPrice = $this->parseNumber($raw['unit_price'] ?? null, true);
            $totalAmount = $this->parseNumber($raw['total_amount'] ?? null, true) ?? 0.0;
            if ($quantity === null) $errors[] = 'SL không hợp lệ.';
            if ($unitWeight === null) $errors[] = 'Kg/con không hợp lệ.';
            if ($totalQuantity === null) $errors[] = 'Cột Tổng không hợp lệ.';
            if ($date && isset($raw['month']) && (int) $this->parseNumber($raw['month'], false) !== $date->month) {
                $warnings[] = 'Tháng được lấy theo ngày: '.$date->month.'.';
            }

            $rows[] = [
                'line' => $line,
                'action' => $errors ? 'error' : ($customer ? 'import' : 'create_customer'),
                'errors' => $errors,
                'warnings' => $warnings,
                'customer_id' => $customer?->id,
                'sale_id' => $sale?->id,
                'data' => [
                    'entry_date' => $date?->toDateString(),
                    'entry_month' => $date?->month,
                    'customer_code' => $customerCode,
                    'customer_name' => $customerName,
                    'sale_name' => $importedSaleName,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'unit_weight' => $unitWeight,
                    'total_quantity' => $totalQuantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                ],
            ];
        }

        $groups = [];
        foreach ($rows as $index => $row) {
            if (!$row['data']['entry_date'] || !$row['data']['customer_name']) continue;
            $customerKey = $row['customer_id']
                ? 'id:'.$row['customer_id']
                : 'name:'.Customer::normalizeName($row['data']['customer_name']);
            $key = $row['data']['entry_date'].'|'.$customerKey;
            $groups[$key][] = $index;
        }
        foreach ($groups as $indexes) {
            $saleIds = collect($indexes)->map(fn ($index) => $rows[$index]['sale_id'])->filter()->unique();
            if ($saleIds->count() > 1) {
                foreach ($indexes as $index) {
                    $rows[$index]['action'] = 'error';
                    $rows[$index]['errors'][] = 'Cùng ngày và khách hàng nhưng có nhiều NVKD; một đơn chỉ được thuộc một sale.';
                }
            }
        }

        $hash = hash('sha256', $this->normalizeSource($text));
        $existingBatch = Schema::hasTable('accounting_sales_import_batches')
            ? AccountingSalesImportBatch::where('source_hash', $hash)->first()
            : null;
        $duplicate = $existingBatch
            && ((int) $existingBatch->row_count > 0 || $existingBatch->entries()->exists());
        if ($duplicate) {
            foreach ($rows as &$row) {
                $row['action'] = 'error';
                $row['errors'][] = 'Toàn bộ dữ liệu này đã được import trước đó.';
            }
            unset($row);
        }

        return [
            'rows' => $rows,
            'sale_mappings' => array_values($mappingRows),
            'counts' => collect($rows)->countBy('action')->all(),
            'source_hash' => $hash,
            'duplicate' => $duplicate,
            'order_count' => count($groups),
        ];
    }

    public function import(string $text, User $actor, array $saleMappings = []): array
    {
        $result = $this->preview($text, $actor, $saleMappings);
        if (($result['counts']['error'] ?? 0) > 0) return $result + ['imported' => false];

        DB::transaction(function () use (&$result, $text, $actor): void {
            // An admin deletion intentionally removes the linked revenue rows
            // but keeps the empty batch as an audit record. Remove only that
            // empty shell so the corrected source can be imported again.
            AccountingSalesImportBatch::query()
                ->where('source_hash', $result['source_hash'])
                ->where('row_count', 0)
                ->whereDoesntHave('entries')
                ->delete();

            $batch = AccountingSalesImportBatch::create([
                'imported_by' => $actor->id,
                'source_hash' => $result['source_hash'],
                'row_count' => count($result['rows']),
                'total_amount' => collect($result['rows'])->sum('data.total_amount'),
                'raw_text' => $text,
            ]);

            foreach ($result['rows'] as &$row) {
                $data = $row['data'];
                $customer = $row['customer_id'] ? Customer::find($row['customer_id']) : null;
                if (!$customer) {
                    $customer = Customer::query()
                        ->where('name_normalized', Customer::normalizeName($data['customer_name']))
                        ->first();
                }
                if (!$customer) {
                    $customer = Customer::create([
                        'user_id' => $actor->id,
                        'name' => $data['customer_name'],
                        'customer_code' => $data['customer_code'],
                        'assigned_to' => $row['sale_id'],
                        'current_owner_sale_id' => $row['sale_id'],
                        'assigned_at' => $row['sale_id'] ? now() : null,
                        'customer_status' => $row['sale_id'] ? 'active' : 'free',
                        'current_cycle_no' => 1,
                        'status' => 'active',
                    ]);
                    if ($row['sale_id']) {
                        app(CustomerPriorityService::class)->attachSale($customer, (int) $row['sale_id'], 1, 'accounting_sales_import');
                    }
                } elseif (!$customer->customer_code && $data['customer_code']) {
                    $customer->update(['customer_code' => $data['customer_code']]);
                }

                AccountingSalesEntry::create(array_merge($data, [
                    'customer_id' => $customer->id,
                    'sale_id' => $row['sale_id'],
                    'sale_name' => User::whereKey($row['sale_id'])->value('name') ?: $data['sale_name'],
                    'source' => AccountingSalesEntry::SOURCE_IMPORT,
                    'source_key' => 'batch:'.$batch->id.':row:'.$row['line'],
                    'import_batch_id' => $batch->id,
                    'import_row' => $row['line'],
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]));
                $entry = AccountingSalesEntry::where('source_key', 'batch:'.$batch->id.':row:'.$row['line'])->firstOrFail();
                $row['entry_id'] = $entry->id;
                $row['customer_id'] = $customer->id;
            }
            unset($row);
            $result['orders_created'] = $this->createHistoricalOrders($batch, $result['rows'], $actor);
            $result['batch_id'] = $batch->id;
        });

        $result['imported'] = true;
        return $result;
    }

    private function parse(string $text): array
    {
        $lines = preg_split('/\R/u', str_replace("\xEF\xBB\xBF", '', trim($text))) ?: [];
        $lines = array_values(array_filter($lines, fn ($line) => trim($line) !== ''));
        if (!$lines) throw new \InvalidArgumentException('Vui lòng dán dữ liệu doanh số.');
        $headers = array_map(fn ($value) => $this->resolveHeader($value), $this->splitLine(array_shift($lines)));
        foreach (['date', 'customer_name', 'sale_name', 'unit', 'quantity', 'unit_weight', 'total_quantity', 'total_amount'] as $required) {
            if (!in_array($required, $headers, true)) throw new \InvalidArgumentException('Thiếu cột bắt buộc: '.$required.'.');
        }
        return array_map(function ($line) use ($headers): array {
            $values = $this->splitLine($line);
            $row = [];
            foreach ($headers as $index => $field) if ($field) $row[$field] = $values[$index] ?? null;
            return $row;
        }, $lines);
    }

    private function splitLine(string $line): array
    {
        $line = rtrim($line, "\r\n");
        $parts = str_contains($line, "\t") ? explode("\t", $line) : (preg_split('/\s{2,}/u', trim($line)) ?: []);
        return array_map(fn ($value) => trim(str_replace('**', '', $value)), $parts);
    }

    private function resolveHeader(string $header): ?string
    {
        $key = $this->key($header);
        foreach (self::ALIASES as $field => $aliases) if (in_array($key, array_map(fn ($v) => $this->key($v), $aliases), true)) return $field;
        return null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $value = $this->clean($value);
        if (!$value) return null;
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date && $date->format($format) === $value) return $date;
            } catch (\Throwable) {}
        }
        return null;
    }

    private function parseNumber(mixed $value, bool $blankAllowed): ?float
    {
        $value = $this->clean($value);
        if ($value === null || preg_match('/^-+$/', str_replace(' ', '', $value))) return $blankAllowed ? null : null;
        $negative = str_contains($value, '-');
        $number = preg_replace('/[^0-9,.]/', '', $value) ?? '';
        if ($number === '') return $blankAllowed ? null : null;
        if (str_contains($number, ',')) {
            $number = str_replace('.', '', $number);
            $number = str_replace(',', '.', $number);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $number)) {
            $number = str_replace('.', '', $number);
        }
        if (!is_numeric($number)) return null;
        return ($negative ? -1 : 1) * (float) $number;
    }

    private function findCustomer(?string $code, ?string $name): ?Customer
    {
        if ($code) {
            $customer = Customer::withTrashed()->where('customer_code', $code)->first();
            if ($customer) return $customer;
        }
        return $name ? Customer::withTrashed()->where('name_normalized', Customer::normalizeName($name))->first() : null;
    }

    private function saleUsers(): Collection
    {
        return User::whereHas('roles', fn ($query) => $query->whereIn(DB::raw('LOWER(name)'), [
            'sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale',
        ]))->orderBy('name')->get(['id', 'name', 'short_name', 'email']);
    }

    private function saleMaps(Collection $users): array
    {
        $aliases = [];
        foreach ($users as $user) foreach ([$user->name, $user->short_name] as $name) if ($name) {
            $key = $this->key($name);
            if (!array_key_exists($key, $aliases)) $aliases[$key] = $user;
            elseif (!$aliases[$key] || $aliases[$key]->id !== $user->id) $aliases[$key] = null;
        }
        return [$aliases, $users->keyBy(fn ($user) => (string) $user->id)];
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) return null;
        $value = trim(str_replace(["\xc2\xa0", '**'], [' ', ''], (string) $value));
        return $value === '' ? null : $value;
    }

    private function key(?string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', Str::ascii((string) $value)) ?? ''));
    }

    private function mappingKey(?string $name): string { return sha1($this->key($name)); }
    private function normalizeSource(string $text): string { return trim(str_replace(["\r\n", "\r"], "\n", $text)); }

    private function createHistoricalOrders(AccountingSalesImportBatch $batch, array $rows, User $actor): int
    {
        $groups = collect($rows)->groupBy(fn ($row) => $row['data']['entry_date'].'|'.$row['customer_id']);
        $created = 0;

        foreach ($groups as $group) {
            $first = $group->first();
            $customer = Customer::findOrFail($first['customer_id']);
            $saleId = (int) $first['sale_id'];
            $total = max(0, round((float) $group->sum('data.total_amount'), 2));
            $entryDate = Carbon::parse($first['data']['entry_date']);
            $groupKey = hash('sha256', 'batch:'.$batch->id.'|'.$entryDate->toDateString().'|customer:'.$customer->id);

            $order = new Order();
            $order->forceFill([
                'customer_id' => $customer->id,
                'user_id' => $saleId,
                'code' => 'HIS-'.$batch->id.'-'.str_pad((string) ($created + 1), 4, '0', STR_PAD_LEFT),
                'total' => $total,
                'subtotal_amount' => $total,
                'status' => Order::STATUS_COMPLETED,
                'payment_status' => 'unpaid',
                'amount_paid' => 0,
                'amount_due' => 0,
                'recipient_name' => $customer->name,
                'recipient_phone' => $customer->phone,
                'recipient_address' => $customer->address,
                'delivery_date' => $entryDate->toDateString(),
                'delivered_at' => $entryDate->copy()->endOfDay(),
                'accounting_sales_import_batch_id' => $batch->id,
                'imported_sales_group_key' => $groupKey,
                'needs_operational_completion' => true,
                'operational_completion_note' => 'Đơn lịch sử từ sổ kế toán; cần bổ sung Kho và Shipper. Không tự động trừ tồn kho.',
                'note' => 'Tạo từ phiên import doanh số kế toán #'.$batch->id.'.',
            ])->save();

            $reconciliation = AccountingReconciliation::create([
                'order_id' => $order->id,
                'sale_id' => $saleId,
                'total_amount' => $total,
                'paid_amount' => 0,
                'shipping_fee' => 0,
                'return_amount' => 0,
                'recognized_revenue' => $total,
                'status' => AccountingReconciliation::STATUS_CONFIRMED,
                'confirmed_by' => $actor->id,
                'confirmed_at' => $entryDate->copy()->endOfDay(),
                'note' => 'Xác nhận tự động từ phiên import doanh số kế toán #'.$batch->id.'.',
            ]);

            $order->forceFill(['amount_due' => $total])->save();
            AccountingSalesEntry::whereIn('id', $group->pluck('entry_id'))
                ->update(['order_id' => $order->id, 'accounting_reconciliation_id' => $reconciliation->id]);

            AccountingSalesEntry::query()
                ->whereIn('id', $group->pluck('entry_id'))
                ->get()
                ->each(fn (AccountingSalesEntry $entry) => $this->syncEntryOrderItem($entry));

            if (Schema::hasTable('order_commissions')) {
                $percent = (float) ($customer->commission_percent ?? 0);
                $commission = round($total * $percent / 100, 2);
                DB::table('order_commissions')->updateOrInsert(['order_id' => $order->id], [
                    'sale_user_id' => $saleId,
                    'customer_id' => $customer->id,
                    'order_total' => $total,
                    'commission_percent' => $percent,
                    'commission_amount' => $commission,
                    'status' => 'confirmed',
                    'confirmed_by' => $actor->id,
                    'confirmed_at' => $entryDate->copy()->endOfDay(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $order->forceFill([
                    'commission_percent_snapshot' => $percent,
                    'commission_amount_snapshot' => $commission,
                    'commission_created_at' => $entryDate->copy()->endOfDay(),
                ])->save();
            }
            $created++;
        }

        return $created;
    }

    public function repairHistoricalOrderItems(): array
    {
        if (! Schema::hasColumn('order_items', 'accounting_sales_entry_id')) {
            return ['created' => 0, 'updated' => 0];
        }

        $created = 0;
        $updated = 0;

        AccountingSalesEntry::query()
            ->where('source', AccountingSalesEntry::SOURCE_IMPORT)
            ->whereNotNull('order_id')
            ->orderBy('id')
            ->chunkById(500, function ($entries) use (&$created, &$updated): void {
                foreach ($entries as $entry) {
                    $exists = OrderItem::query()
                        ->where('accounting_sales_entry_id', $entry->id)
                        ->exists();
                    $this->syncEntryOrderItem($entry);
                    $exists ? $updated++ : $created++;
                }
            });

        return compact('created', 'updated');
    }

    public function syncEntryOrderItem(AccountingSalesEntry $entry): ?OrderItem
    {
        if (! $entry->order_id || ! Schema::hasColumn('order_items', 'accounting_sales_entry_id')) {
            return null;
        }

        $unit = trim((string) $entry->unit);
        [$product, $variant] = $this->resolveImportedProduct($unit, (float) $entry->unit_weight);
        $quantity = max(0, (int) round((float) $entry->quantity));
        $unitWeight = max(0.001, (float) $entry->unit_weight);
        $totalWeight = max(0, (float) $entry->total_quantity);
        $unitPrice = (float) ($entry->unit_price ?? 0);
        $totalAmount = (float) $entry->total_amount;
        $isPricedByKg = $this->isImportedLinePricedByWeight(
            $unit,
            $quantity,
            $totalWeight,
            $unitPrice,
            $totalAmount,
            $variant?->effective_priced_by_kg ?? $product?->is_priced_by_kg
        );

        $item = OrderItem::query()->updateOrCreate(
            ['accounting_sales_entry_id' => $entry->id],
            [
                'order_id' => $entry->order_id,
                'product_id' => $product?->id,
                'product_variant_id' => $variant?->id,
                'imported_name' => $this->importedLineName($unit),
                'quantity' => $quantity,
                'price' => $unitPrice,
                'base_price' => $unitPrice,
                'unit_discount' => 0,
                'discount_total' => 0,
                'unit_weight' => round($unitWeight, 3),
                'is_priced_by_kg' => $isPricedByKg,
                'total_weight' => round($totalWeight, 3),
                'total' => round($totalAmount, 2),
            ]
        );

        if ((int) $entry->order_item_id !== (int) $item->id) {
            AccountingSalesEntry::query()->whereKey($entry->id)->update(['order_item_id' => $item->id]);
        }

        return $item;
    }

    private function resolveImportedProduct(string $unit, float $unitWeight): array
    {
        $key = $this->key($unit);
        if (in_array($key, ['vat', 'nha xe', 'shiper', 'shipper', 'thung xop', 'giam tru'], true)) {
            return [null, null];
        }

        $catalog = $this->importProductCatalog ??= Product::query()
            ->with('variants')
            ->where('status', true)
            ->get();

        $product = $catalog
            ->map(function (Product $product) use ($key): array {
                $name = $this->key($product->name);
                $score = 0;
                if ($key === 'con') {
                    if (str_contains($name, 'vit nguyen con')) $score += 100;
                    if ((string) $product->unit === 'con') $score += 40;
                    if (str_contains($name, 'bam')) $score -= 100;
                } elseif ($key !== '' && str_contains($name, $key)) {
                    $score += 80;
                }

                return ['product' => $product, 'score' => $score];
            })
            ->filter(fn (array $candidate) => $candidate['score'] > 0)
            ->sortByDesc('score')
            ->first()['product'] ?? null;

        if (! $product) {
            return [null, null];
        }

        $variant = $product->variants
            ->sortBy(fn ($variant) => abs((float) $variant->effective_kg - max($unitWeight, 0.001)))
            ->first();

        return [$product, $variant];
    }

    private function importedLineName(string $unit): string
    {
        return match ($this->key($unit)) {
            'con' => 'Vịt nguyên con',
            'vat' => 'VAT',
            'nha xe' => 'Phí nhà xe',
            'shiper', 'shipper' => 'Phí shipper',
            'thung xop' => 'Thùng xốp',
            'giam tru' => 'Giảm trừ',
            default => $unit !== '' ? $unit : 'Dòng doanh số',
        };
    }

    private function isImportedLinePricedByWeight(
        string $unit,
        int $quantity,
        float $totalWeight,
        float $unitPrice,
        float $totalAmount,
        ?bool $catalogDefault
    ): bool {
        if ($unitPrice != 0.0) {
            $weightDifference = abs($totalAmount - ($unitPrice * $totalWeight));
            $quantityDifference = abs($totalAmount - ($unitPrice * $quantity));
            if (abs($weightDifference - $quantityDifference) > 0.01) {
                return $weightDifference < $quantityDifference;
            }
        }

        return $catalogDefault ?? in_array($this->key($unit), ['con', 'uc', 'long', 'huyet', 'vat'], true);
    }
}
