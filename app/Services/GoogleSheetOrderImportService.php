<?php

namespace App\Services;

use App\Models\{AccountingReconciliation, AccountingSalesEntry, AccountingSalesImportBatch, Customer, Order, OrderHistory, OrderItem, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GoogleSheetOrderImportService
{
    public const SPREADSHEET = '1K4n-tDDo98TlDDdVvKE0oFGz0N2zTqo2k3t2NWkuVUs';
    public const GID = 438108508;
    public const URL = 'https://docs.google.com/spreadsheets/d/'.self::SPREADSHEET.'/edit?gid='.self::GID;
    private const HEADERS = [
        'date' => ['ngay thang', 'ngay', 'date', 'ngay giao', 'ngay giao hang'],
        'customer_code' => ['ma kh', 'ma khach hang', 'customer code'],
        'customer_name' => ['khach hang', 'ten khach hang', 'customer'],
        'sale_name' => ['nvkd', 'sale', 'nhan vien kinh doanh'],
        'unit' => ['dvt', 'don vi tinh', 'unit', 'san pham', 'ten hang'],
        'quantity' => ['sl', 'so luong', 'quantity'],
        'unit_weight' => ['kg/con', 'kg con', 'trong luong', 'unit weight'],
        'total_quantity' => ['tong', 'tong kg', 'total', 'tong kl'],
        'unit_price' => ['don gia', 'unit price'],
        'total_amount' => ['tong tien', 'thanh tien', 'amount'],
    ];

    public function parseText(string $text, string $from, string $to): array
    {
        $text = str_replace("\xEF\xBB\xBF", '', $text);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $text);
        rewind($stream);
        $values = [];
        while (($row = fgetcsv($stream, null, "\t", '"', '')) !== false) $values[] = $row;
        fclose($stream);
        return $this->parseValues($values, $from, $to);
    }

    public function parseValues(array $values, string $from, string $to): array
    {
        $headers = null; $rows = []; $carry = []; $invalidDates = [];
        foreach ($values as $index => $cells) {
            $candidate = [];
            foreach ($cells as $column => $label) {
                foreach (self::HEADERS as $field => $aliases) {
                    if (in_array($this->normalize((string) $label), $aliases, true)) $candidate[$field] = $column;
                }
            }
            if (isset($candidate['date'], $candidate['customer_name'], $candidate['unit'], $candidate['total_amount'])) {
                $headers = $candidate; $carry = []; continue;
            }
            if (!$headers || !array_filter($cells, fn ($v) => trim((string) $v) !== '')) continue;
            $row = [];
            foreach ($headers as $field => $column) $row[$field] = trim((string) ($cells[$column] ?? ''));
            // Only detail rows are imported. Totals never inherit a customer.
            if (preg_match('/^(tong|total|cong)(\s|$)/', $this->normalize($row['customer_name'] ?? ''))) { unset($carry['customer_name'], $carry['customer_code']); continue; }
            if (empty($row['unit'])) {
                if (($row['total_amount'] ?? '') !== '' && ($row['customer_name'] ?? '') !== '') throw new RuntimeException('Dòng '.($index + 1).' có tiền nhưng thiếu hàng / DVT.');
                continue;
            }
            if (($row['date'] ?? '') !== '') {
                if (($carry['date'] ?? null) !== $row['date']) unset($carry['customer_name'], $carry['customer_code']);
                $carry['date'] = $row['date'];
            }
            if (($row['customer_name'] ?? '') !== '') {
                $carry['customer_name'] = $row['customer_name'];
                $carry['customer_code'] = $row['customer_code'] ?? '';
            }
            foreach (['date', 'customer_name', 'customer_code'] as $field) {
                if (($row[$field] ?? '') === '') $row[$field] = $carry[$field] ?? '';
            }
            $date = $this->date($row['date'] ?? '');
            if (!$date) { $invalidDates[] = $index + 1; continue; }
            if ($date < $from || $date > $to) continue;
            if (empty($row['customer_name'])) throw new RuntimeException('Dòng '.($index + 1).' thiếu khách hàng.');
            $row['date'] = $date; $row['sheet_row'] = $index + 1;
            $row['customer_key'] = hash('sha256', $this->normalize($row['customer_name']));
            $rows[] = $row;
        }
        if (!$headers) throw new RuntimeException('Không nhận diện được tiêu đề. Cần các cột Ngày, Khách hàng, DVT, Tổng tiền; cùng SL, Kg/con, Tổng, Đơn giá cho chi tiết hàng.');
        if ($invalidDates) throw new RuntimeException('Không xác định được ngày ở các dòng '.implode(', ', array_slice($invalidDates, 0, 15)).'. Vui lòng sửa ngày trước khi tải để tránh bỏ sót đơn.');
        return $rows;
    }

    private function date(string $value): ?string
    {
        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date && $date->format($format) === $value) return $date->toDateString();
            } catch (\Throwable) {}
        }
        return null;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/u', ' ', Str::ascii(str_replace("\xc2\xa0", ' ', $value)))));
    }

    public function sourceKey(array $row): string
    {
        return hash('sha256', self::SPREADSHEET.'|'.self::GID.'|'.$row['date'].'|'.$row['customer_key']);
    }

    public function sameDayOrders(Customer $customer, array $dates, int $additionalOrders = 1): array
    {
        if (!$dates) return [];
        $dateExpression = 'DATE(COALESCE(delivery_date, created_at))';
        return Order::query()->where('customer_id', $customer->id)
            ->whereNull('trash_at')->whereNotIn('status', ['cancelled', 'canceled', 'rejected', 'draft'])
            ->whereIn(DB::raw($dateExpression), array_values(array_unique($dates)))
            ->select(['id', 'code', 'status', 'total'])
            ->selectRaw($dateExpression.' as business_date')->orderBy('id')->get()
            ->groupBy('business_date')
            ->filter(fn ($orders) => $orders->count() + $additionalOrders >= 2)
            ->map(fn ($orders, $date) => [
                'date' => $date, 'existing_count' => $orders->count(),
                'resulting_count' => $orders->count() + $additionalOrders,
                'orders' => $orders->map(fn ($order) => ['id' => $order->id, 'code' => $order->code ?: '#'.$order->id, 'status' => $order->status, 'total' => (float) $order->total])->all(),
            ])->sortKeys()->values()->all();
    }

    public function prepare(array $rows, Customer $customer, User $sale, User $actor): array
    {
        $columns = ['date', 'customer_code', 'customer_name', 'sale_name', 'unit', 'quantity', 'unit_weight', 'total_quantity', 'unit_price', 'total_amount'];
        $lines = [implode("\t", ['Ngày', 'Mã KH', 'Khách hàng', 'NVKD', 'DVT', 'SL', 'Kg/con', 'Tổng', 'Đơn giá', 'Tổng tiền'])];
        foreach ($rows as $row) {
            $row['date'] = Carbon::parse($row['date'])->format('d/m/Y');
            $row['customer_code'] = $customer->customer_code;
            $row['customer_name'] = $customer->name;
            $row['sale_name'] = $sale->name;
            $lines[] = implode("\t", array_map(fn ($key) => preg_replace('/[\r\n\t]+/u', ' ', (string) ($row[$key] ?? '')), $columns));
        }
        $text = implode("\n", $lines);
        $mappings = [sha1($this->normalize($sale->name)) => $sale->id];
        $preview = app(AccountingSalesImportService::class)->preview($text, $actor, $mappings);
        foreach ($preview['rows'] as &$row) {
            $data = &$row['data'];
            $price = (float) ($data['unit_price'] ?? 0);
            $amount = (float) $data['total_amount'];
            // Legacy sheets use prices in thousands while amounts are in VND.
            if ($price > 0 && $amount > 0) {
                $bases = [(float) $data['quantity'], (float) $data['total_quantity']];
                $matches = fn (float $factor) => collect($bases)->contains(fn ($base) => $base > 0 && abs($base * $price * $factor - $amount) <= 1);
                if (!$matches(1) && $matches(1000)) {
                    $data['unit_price'] = $price * 1000;
                    $row['warnings'][] = 'Đơn giá nguồn '.number_format($price, 3, ',', '.').' được quy đổi x1.000 thành '.number_format($data['unit_price']).' đ để khớp thành tiền.';
                }
            }
            unset($data);

            if ((int) $row['customer_id'] !== (int) $customer->id) throw ValidationException::withMessages(['customer_id' => 'Tên hoặc mã khách B bị trùng trong hệ thống. Vui lòng kiểm tra hồ sơ khách trước khi import.']);
        }
        unset($row);
        $preview['same_day_orders'] = $this->sameDayOrders($customer, array_column($rows, 'date'));
        return compact('text', 'preview');
    }

    public function import(array $rows, Customer $customer, User $sale, User $actor, array $options): array
    {
        if (!$rows || count(array_unique(array_column($rows, 'customer_key'))) !== 1) throw ValidationException::withMessages(['source_customer' => 'Mỗi lần import phải chọn đúng một khách nguồn.']);
        return DB::transaction(function () use ($rows, $customer, $sale, $actor, $options) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if (!Customer::query()->managedBySale((int) $sale->id)->whereKey($customer->id)->exists()) {
                throw ValidationException::withMessages(['customer_id' => 'Khách B không còn do sale đã chọn quản lý. Vui lòng chọn lại khách.']);
            }
            $keys = array_unique(array_map(fn ($r) => $this->sourceKey($r), $rows));
            if (DB::table('google_sheet_order_imports')->whereIn('source_key', $keys)->exists()) {
                throw ValidationException::withMessages(['source_customer' => 'Có đơn của khách/ngày này đã import. Hãy tải lại dữ liệu và bỏ những ngày đã nhập.']);
            }
            ['text' => $text, 'preview' => $preview] = $this->prepare($rows, $customer, $sale, $actor);
            if ($preview['counts']['error'] ?? 0) {
                throw ValidationException::withMessages(['source_customer' => collect($preview['rows'])->flatMap(fn ($r) => $r['errors'])->unique()->implode(' ')]);
            }
            $batch = AccountingSalesImportBatch::create([
                'imported_by' => $actor->id, 'source_hash' => hash('sha256', 'sheet-orders:'.implode('|', $keys)),
                'source_warehouse_id' => $options['warehouse_id'], 'row_count' => count($rows),
                'total_amount' => collect($preview['rows'])->sum('data.total_amount'), 'raw_text' => $text,
            ]);
            $oldNames = array_filter(array_map('trim', explode("\n", (string) $customer->old_name)));
            foreach (array_unique(array_column($rows, 'customer_name')) as $name) if (!in_array($name, $oldNames, true)) $oldNames[] = $name;
            $customer->forceFill(['old_name' => implode("\n", $oldNames)])->save();
            $orders = [];
            foreach (collect($preview['rows'])->groupBy('data.entry_date') as $date => $details) {
                $raw = collect($rows)->firstWhere('date', $date);
                $order = $this->createOrder($batch, $details, $customer, $sale, $actor, $options, $date);
                DB::table('google_sheet_order_imports')->insert([
                    'source_key' => $this->sourceKey($raw), 'order_date' => $date, 'source_customer' => $raw['customer_name'],
                    'customer_id' => $customer->id, 'order_id' => $order->id, 'imported_by' => $actor->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $orders[] = $order->code;
            }
            return $orders;
        });
    }

    private function createOrder($batch, $details, Customer $customer, User $sale, User $actor, array $options, string $date): Order
    {
        $stage = $options['stage'];
        $completed = $stage === 'completed';
        $delivered = $stage !== 'created';
        $status = $completed ? Order::STATUS_COMPLETED : ($delivered ? Order::STATUS_DELIVERED : Order::STATUS_PENDING_LEADER_APPROVAL);
        $total = max(0, round((float) $details->sum('data.total_amount'), 2));
        $order = new Order;
        $order->forceFill([
            'customer_id' => $customer->id, 'user_id' => $sale->id,
            'code' => 'GSI-'.$batch->id.'-'.str_replace('-', '', $date), 'status' => $status,
            'total' => $total, 'subtotal_amount' => $total, 'payment_status' => 'unpaid', 'amount_paid' => 0,
            'amount_due' => $completed ? $total : 0, 'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone, 'recipient_address' => $customer->address,
            'delivery_date' => $date, 'delivered_at' => $delivered ? Carbon::parse($date)->endOfDay() : null,
            'warehouse_id' => $options['warehouse_id'], 'shipper_id' => $options['shipper_id'] ?? null,
            'accounting_sales_import_batch_id' => $batch->id,
            'imported_sales_group_key' => hash('sha256', 'sheet:'.$batch->id.'|'.$date.'|'.$customer->id),
            'needs_operational_completion' => false,
            'skip_auto_cancel' => true,
            'note' => 'Import Google Sheet gid='.self::GID.'; ngày '.$date.'. Không tác động tồn kho hiện tại.',
        ])->save();
        // The customer observer can assign a default shipper; retain the admin selection.
        $order->forceFill(['shipper_id' => $options['shipper_id'] ?? null])->saveQuietly();
        $reconciliation = $completed ? AccountingReconciliation::create([
            'order_id' => $order->id, 'sale_id' => $sale->id, 'shipper_id' => $order->shipper_id,
            'total_amount' => $total, 'paid_amount' => 0, 'shipping_fee' => 0, 'return_amount' => 0,
            'recognized_revenue' => $total, 'status' => AccountingReconciliation::STATUS_CONFIRMED,
            'confirmed_by' => $actor->id, 'confirmed_at' => now(), 'note' => 'Admin import lịch sử và xác nhận kế toán từ Google Sheet.',
        ]) : null;
        foreach ($details as $row) {
            $entry = AccountingSalesEntry::create(array_merge($row['data'], [
                'customer_id' => $customer->id, 'sale_id' => $sale->id, 'sale_name' => $sale->name,
                'source' => AccountingSalesEntry::SOURCE_IMPORT, 'source_key' => 'batch:'.$batch->id.':row:'.$row['line'],
                'order_id' => $order->id, 'accounting_reconciliation_id' => $reconciliation?->id,
                'import_batch_id' => $batch->id, 'import_row' => $row['line'], 'created_by' => $actor->id, 'updated_by' => $actor->id,
            ]));
            $item = app(AccountingSalesImportService::class)->syncEntryOrderItem($entry);
            if (!$item) {
                $item = OrderItem::create([
                    'order_id' => $order->id, 'accounting_sales_entry_id' => $entry->id,
                    'imported_name' => $entry->unit, 'quantity' => (float) $entry->quantity ?: 1,
                    'price' => $entry->unit_price ?? $entry->total_amount, 'base_price' => $entry->unit_price ?? $entry->total_amount,
                    'unit_weight' => $entry->unit_weight, 'total_weight' => $entry->total_quantity, 'total' => $entry->total_amount,
                ]);
                $entry->update(['order_item_id' => $item->id]);
            }
            if ($delivered) $item->forceFill(['packed_weight' => $item->total_weight])->save();
        }
        $order->forceFill(['total_weight' => round((float) $order->items()->sum('total_weight'), 3), 'amount_due' => $completed ? $total : 0])->saveQuietly();
        if ($completed) {
            $percent = (float) ($customer->commission_percent ?? 0);
            $commission = round($total * $percent / 100, 2);
            DB::table('order_commissions')->insert([
                'order_id' => $order->id, 'sale_user_id' => $sale->id, 'customer_id' => $customer->id,
                'order_total' => $total, 'commission_percent' => $percent, 'commission_amount' => $commission,
                'status' => 'confirmed', 'confirmed_by' => $actor->id, 'confirmed_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $order->forceFill(['commission_percent_snapshot' => $percent, 'commission_amount_snapshot' => $commission, 'commission_created_at' => now()])->saveQuietly();
        }
        OrderHistory::create([
            'order_id' => $order->id, 'user_id' => $actor->id, 'role' => 'admin',
            'action' => $completed ? 'accounting_confirmed' : ($delivered ? 'shipper_delivered' : 'create_order'), 'status_before' => null, 'status_after' => $status,
            'note' => 'Import lịch sử từ Google Sheet, khách cũ: '.DB::table('customers')->where('id', $customer->id)->value('old_name').'. Mức xử lý: '.$stage.'.',
        ]);
        return $order;
    }
}
