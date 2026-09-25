<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchClearValuesRequest;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\Request as SheetsRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class GoogleSheetsOrderService
{
    /** @return array{order_row:int, detail_rows:int, status:string} */
    public function sync(Order|int $order): array
    {
        $order = $order instanceof Order ? $order : Order::query()->findOrFail($order);
        $order->loadMissing([
            'customer:id,name,phone', 'user:id,name,short_name',
            'items.product', 'items.variant.product',
            'adjustments:id,order_id,status,adjustment_note,reject_reason,submitted_at,updated_at',
        ]);

        return Cache::lock('google-sheets-order-sync-'.$order->id, 90)->block(20, function () use ($order): array {
            $spreadsheetId = trim((string) config('services.google_sheets.order_spreadsheet_id'));
            if ($spreadsheetId === '') {
                throw new RuntimeException('Chưa cấu hình GOOGLE_SHEETS_ORDER_SPREADSHEET_ID.');
            }

            $service = new Sheets($this->client());
            $sheets = $this->resolveSheets($service, $spreadsheetId);
            $orderResult = $this->upsertOrderRow($service, $spreadsheetId, $sheets['orders'], $order);
            $detailRows = $this->upsertDetailRows($service, $spreadsheetId, $sheets['details'], $order);
            if ($orderResult['format_cancelled'] !== null) {
                $this->formatOrderStatus($service, $spreadsheetId, $sheets['orders']['id'], $orderResult['row'], $orderResult['format_cancelled']);
            }

            return [
                'order_row' => $orderResult['row'],
                'detail_rows' => $detailRows,
                'status' => $this->statusLabel($order),
            ];
        });
    }

    /**
     * Đồng bộ cả ngày bằng các batch request để không vượt hạn mức API khi
     * Manager đối soát nhiều đơn cùng lúc.
     *
     * @return array{orders:int,details:int,deleted:int}
     */
    public function syncReviewOrders(iterable $orders, iterable $deletedRecords = []): array
    {
        $liveOrders = collect($orders)->map(function (Order $order): Order {
            $order->loadMissing([
                'customer:id,name,phone', 'user:id,name,short_name',
                'items.product', 'items.variant.product',
                'adjustments:id,order_id,status,adjustment_note,reject_reason,submitted_at,updated_at',
            ]);

            return $order;
        });
        $deletedOrders = collect($deletedRecords)->map(fn ($record) => $this->deletedOrder($record));
        $exportOrders = $liveOrders->concat($deletedOrders)
            ->keyBy(fn (Order $order) => $this->orderCode($order))
            ->values();

        return Cache::lock('google-sheets-order-review-sync', 300)->block(30, function () use ($exportOrders, $deletedOrders): array {
            $spreadsheetId = trim((string) config('services.google_sheets.order_spreadsheet_id'));
            if ($spreadsheetId === '') {
                throw new RuntimeException('Chưa cấu hình GOOGLE_SHEETS_ORDER_SPREADSHEET_ID.');
            }

            $service = new Sheets($this->client());
            $sheets = $this->resolveSheets($service, $spreadsheetId);
            $rowByCode = $this->bulkUpsertOrderRows($service, $spreadsheetId, $sheets['orders'], $exportOrders);
            $detailCount = $this->bulkUpsertDetailRows($service, $spreadsheetId, $sheets['details'], $exportOrders);
            $this->bulkFormatStatuses($service, $spreadsheetId, $sheets['orders']['id'], $exportOrders, $rowByCode);

            return [
                'orders' => $exportOrders->count(),
                'details' => $detailCount,
                'deleted' => $deletedOrders->count(),
            ];
        });
    }

    /** @param array{id:int,title:string} $sheet */
    private function bulkUpsertOrderRows(Sheets $service, string $spreadsheetId, array $sheet, \Illuminate\Support\Collection $orders): array
    {
        $existing = $service->spreadsheets_values->get(
            $spreadsheetId,
            $this->sheetRange($sheet['title'], 'A2:J'),
            ['valueRenderOption' => 'UNFORMATTED_VALUE']
        )->getValues() ?? [];
        $offsetByCode = collect($existing)->mapWithKeys(fn (array $row, int $offset) => [trim((string) ($row[0] ?? '')) => $offset]);
        $updates = [];
        $appends = [];
        $rowByCode = [];
        $nextRow = count($existing) + 2;

        foreach ($orders as $order) {
            $code = $this->orderCode($order);
            $values = $this->orderValues($order);
            $offset = $offsetByCode->get($code);
            if ($offset === null) {
                $appends[] = $values;
                $rowByCode[$code] = $nextRow++;
                continue;
            }

            $row = (int) $offset + 2;
            $rowByCode[$code] = $row;
            $updates[] = new ValueRange([
                'range' => $this->sheetRange($sheet['title'], "A{$row}:H{$row}"),
                'majorDimension' => 'ROWS',
                'values' => [array_slice($values, 0, 8)],
            ]);
            $updates[] = new ValueRange([
                'range' => $this->sheetRange($sheet['title'], "J{$row}"),
                'majorDimension' => 'ROWS',
                'values' => [[$values[9]]],
            ]);
        }

        if ($updates !== []) {
            $service->spreadsheets_values->batchUpdate($spreadsheetId, new BatchUpdateValuesRequest([
                'valueInputOption' => 'RAW',
                'data' => $updates,
            ]));
        }
        if ($appends !== []) {
            $service->spreadsheets_values->append(
                $spreadsheetId,
                $this->sheetRange($sheet['title'], 'A:J'),
                new ValueRange(['majorDimension' => 'ROWS', 'values' => $appends]),
                ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
            );
        }

        return $rowByCode;
    }

    /** @param array{id:int,title:string} $sheet */
    private function bulkUpsertDetailRows(Sheets $service, string $spreadsheetId, array $sheet, \Illuminate\Support\Collection $orders): int
    {
        $existing = $service->spreadsheets_values->get(
            $spreadsheetId,
            $this->sheetRange($sheet['title'], 'A2:A'),
            ['valueRenderOption' => 'UNFORMATTED_VALUE']
        )->getValues() ?? [];
        $rowsByCode = collect($existing)->mapToGroups(fn (array $row, int $offset) => [trim((string) ($row[0] ?? '')) => $offset + 2]);
        $updates = [];
        $clearRanges = [];
        $appends = [];
        $detailCount = 0;

        foreach ($orders as $order) {
            $code = $this->orderCode($order);
            $values = $this->detailValues($order);
            $detailCount += count($values);
            $availableRows = collect($rowsByCode->get($code, []))->values();
            $commonCount = min($availableRows->count(), count($values));
            for ($index = 0; $index < $commonCount; $index++) {
                $row = $availableRows[$index];
                $updates[] = new ValueRange([
                    'range' => $this->sheetRange($sheet['title'], "A{$row}:I{$row}"),
                    'majorDimension' => 'ROWS',
                    'values' => [$values[$index]],
                ]);
            }
            foreach ($availableRows->slice($commonCount) as $row) {
                $clearRanges[] = $this->sheetRange($sheet['title'], "A{$row}:I{$row}");
            }
            array_push($appends, ...array_slice($values, $commonCount));
        }

        if ($updates !== []) {
            $service->spreadsheets_values->batchUpdate($spreadsheetId, new BatchUpdateValuesRequest([
                'valueInputOption' => 'RAW',
                'data' => $updates,
            ]));
        }
        if ($clearRanges !== []) {
            $service->spreadsheets_values->batchClear(
                $spreadsheetId,
                new BatchClearValuesRequest(['ranges' => $clearRanges])
            );
        }
        if ($appends !== []) {
            $service->spreadsheets_values->append(
                $spreadsheetId,
                $this->sheetRange($sheet['title'], 'A:I'),
                new ValueRange(['majorDimension' => 'ROWS', 'values' => $appends]),
                ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
            );
        }

        return $detailCount;
    }

    private function bulkFormatStatuses(Sheets $service, string $spreadsheetId, int $sheetId, \Illuminate\Support\Collection $orders, array $rowByCode): void
    {
        $requests = $orders->map(function (Order $order) use ($sheetId, $rowByCode): SheetsRequest {
            $deleted = (bool) $order->getAttribute('sheet_deleted') || (bool) $order->trash_at;
            $cancelled = $order->status === Order::STATUS_CANCELLED;
            $format = match (true) {
                $cancelled => ['backgroundColor' => ['red' => 0.96, 'green' => 0.80, 'blue' => 0.80], 'textFormat' => ['foregroundColor' => ['red' => 0.72, 'green' => 0.05, 'blue' => 0.05], 'bold' => true]],
                $deleted => ['backgroundColor' => ['red' => 0.88, 'green' => 0.89, 'blue' => 0.91], 'textFormat' => ['foregroundColor' => ['red' => 0.25, 'green' => 0.28, 'blue' => 0.32], 'bold' => true]],
                default => ['backgroundColor' => ['red' => 1, 'green' => 0.95, 'blue' => 0.77], 'textFormat' => ['foregroundColor' => ['red' => 0.20, 'green' => 0.16, 'blue' => 0.03], 'bold' => false]],
            };
            $row = $rowByCode[$this->orderCode($order)];

            return new SheetsRequest(['repeatCell' => [
                'range' => ['sheetId' => $sheetId, 'startRowIndex' => $row - 1, 'endRowIndex' => $row, 'startColumnIndex' => 7, 'endColumnIndex' => 8],
                'cell' => ['userEnteredFormat' => $format],
                'fields' => 'userEnteredFormat(backgroundColor,textFormat.foregroundColor,textFormat.bold)',
            ]]);
        })->all();

        if ($requests !== []) {
            $service->spreadsheets->batchUpdate($spreadsheetId, new BatchUpdateSpreadsheetRequest(['requests' => $requests]));
        }
    }

    public function isConfigured(): bool
    {
        try {
            return (bool) config('services.google_sheets.order_sync_enabled')
                && trim((string) config('services.google_sheets.order_spreadsheet_id')) !== ''
                && is_file($this->credentialsPath());
        } catch (RuntimeException) {
            return false;
        }
    }

    private function client(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setApplicationName((string) config('app.name', 'Hoang Long TNT'));
        $client->setAuthConfig($this->credentialsPath());
        $client->setScopes([Sheets::SPREADSHEETS]);

        return $client;
    }

    private function credentialsPath(): string
    {
        $configuredPath = trim((string) config('services.google_sheets.credentials'));
        if ($configuredPath === '') {
            throw new RuntimeException('Chưa cấu hình GOOGLE_SHEETS_CREDENTIALS.');
        }

        $path = str_starts_with($configuredPath, DIRECTORY_SEPARATOR)
            ? $configuredPath
            : base_path($configuredPath);
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Không đọc được khóa service account tại {$path}.");
        }

        return $path;
    }

    /**
     * @return array{orders:array{id:int,title:string},details:array{id:int,title:string}}
     */
    private function resolveSheets(Sheets $service, string $spreadsheetId): array
    {
        $targets = [
            'orders' => [
                'id' => (int) config('services.google_sheets.order_sheet_id'),
                'title' => trim((string) config('services.google_sheets.order_sheet_name', '01_DON_HANG')),
            ],
            'details' => [
                'id' => (int) config('services.google_sheets.order_detail_sheet_id'),
                'title' => trim((string) config('services.google_sheets.order_detail_sheet_name', '02_CHI_TIET_DON_HANG')),
            ],
        ];
        $spreadsheet = $service->spreadsheets->get($spreadsheetId, [
            'fields' => 'sheets.properties(sheetId,title)',
        ]);
        $available = collect($spreadsheet->getSheets() ?? [])->map(fn ($sheet) => [
            'id' => (int) $sheet->getProperties()?->getSheetId(),
            'title' => (string) $sheet->getProperties()?->getTitle(),
        ]);

        foreach ($targets as $key => $target) {
            $resolved = $available->first(fn (array $sheet) => ($target['id'] > 0 && $sheet['id'] === $target['id'])
                || ($target['title'] !== '' && $sheet['title'] === $target['title']));
            if (! $resolved) {
                throw new RuntimeException('Không tìm thấy tab Google Sheets '.$target['title'].' (gid '.$target['id'].').');
            }
            $targets[$key] = $resolved;
        }

        return $targets;
    }

    /**
     * @param array{id:int,title:string} $sheet
     * @return array{row:int,format_cancelled:?bool}
     */
    private function upsertOrderRow(Sheets $service, string $spreadsheetId, array $sheet, Order $order): array
    {
        $range = $this->sheetRange($sheet['title'], 'A2:J');
        $rows = $service->spreadsheets_values->get($spreadsheetId, $range, [
            'valueRenderOption' => 'UNFORMATTED_VALUE',
        ])->getValues() ?? [];
        $code = $this->orderCode($order);
        $rowOffset = collect($rows)->search(fn (array $row) => trim((string) ($row[0] ?? '')) === $code);
        $values = $this->orderValues($order);

        if ($rowOffset === false) {
            $service->spreadsheets_values->append(
                $spreadsheetId,
                $this->sheetRange($sheet['title'], 'A:J'),
                new ValueRange(['majorDimension' => 'ROWS', 'values' => [$values]]),
                ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
            );

            return [
                'row' => count($rows) + 2,
                'format_cancelled' => $order->status === Order::STATUS_CANCELLED ? true : null,
            ];
        }

        $rowNumber = (int) $rowOffset + 2;
        $data = [
            new ValueRange([
                'range' => $this->sheetRange($sheet['title'], "A{$rowNumber}:G{$rowNumber}"),
                'majorDimension' => 'ROWS',
                'values' => [array_slice($values, 0, 7)],
            ]),
            new ValueRange([
                'range' => $this->sheetRange($sheet['title'], "J{$rowNumber}"),
                'majorDimension' => 'ROWS',
                'values' => [[$values[9]]],
            ]),
        ];
        $existingStatus = trim((string) ($rows[$rowOffset][7] ?? ''));
        $data[] = new ValueRange([
            'range' => $this->sheetRange($sheet['title'], "H{$rowNumber}"),
            'majorDimension' => 'ROWS',
            'values' => [[$values[7]]],
        ]);
        $service->spreadsheets_values->batchUpdate(
            $spreadsheetId,
            new BatchUpdateValuesRequest(['valueInputOption' => 'RAW', 'data' => $data])
        );

        return [
            'row' => $rowNumber,
            'format_cancelled' => $order->status === Order::STATUS_CANCELLED
                ? true
                : (in_array($existingStatus, ['Hủy đơn', 'Đã xóa'], true) ? false : null),
        ];
    }

    /** @param array{id:int,title:string} $sheet */
    private function upsertDetailRows(Sheets $service, string $spreadsheetId, array $sheet, Order $order): int
    {
        $existing = $service->spreadsheets_values->get(
            $spreadsheetId,
            $this->sheetRange($sheet['title'], 'A2:A'),
            ['valueRenderOption' => 'UNFORMATTED_VALUE']
        )->getValues() ?? [];
        $code = $this->orderCode($order);
        $existingRows = collect($existing)
            ->map(fn (array $row, int $offset) => trim((string) ($row[0] ?? '')) === $code ? $offset + 2 : null)
            ->filter()
            ->values();
        $detailValues = $this->detailValues($order);
        $commonCount = min($existingRows->count(), count($detailValues));

        $data = [];
        for ($index = 0; $index < $commonCount; $index++) {
            $rowNumber = $existingRows[$index];
            $data[] = new ValueRange([
                'range' => $this->sheetRange($sheet['title'], "A{$rowNumber}:I{$rowNumber}"),
                'majorDimension' => 'ROWS',
                'values' => [$detailValues[$index]],
            ]);
        }
        if ($data !== []) {
            $service->spreadsheets_values->batchUpdate(
                $spreadsheetId,
                new BatchUpdateValuesRequest(['valueInputOption' => 'RAW', 'data' => $data])
            );
        }

        foreach ($existingRows->slice($commonCount) as $rowNumber) {
            $service->spreadsheets_values->clear(
                $spreadsheetId,
                $this->sheetRange($sheet['title'], "A{$rowNumber}:I{$rowNumber}"),
                new ClearValuesRequest
            );
        }

        $newRows = array_slice($detailValues, $commonCount);
        if ($newRows !== []) {
            $service->spreadsheets_values->append(
                $spreadsheetId,
                $this->sheetRange($sheet['title'], 'A:I'),
                new ValueRange(['majorDimension' => 'ROWS', 'values' => $newRows]),
                ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
            );
        }

        return count($detailValues);
    }

    private function formatOrderStatus(Sheets $service, string $spreadsheetId, int $sheetId, int $rowNumber, bool $cancelled): void
    {
        $format = $cancelled
            ? ['backgroundColor' => ['red' => 0.96, 'green' => 0.80, 'blue' => 0.80], 'textFormat' => ['foregroundColor' => ['red' => 0.72, 'green' => 0.05, 'blue' => 0.05], 'bold' => true]]
            : ['backgroundColor' => ['red' => 1, 'green' => 0.95, 'blue' => 0.77], 'textFormat' => ['foregroundColor' => ['red' => 0.20, 'green' => 0.16, 'blue' => 0.03], 'bold' => false]];

        $service->spreadsheets->batchUpdate($spreadsheetId, new BatchUpdateSpreadsheetRequest([
            'requests' => [new SheetsRequest([
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => $rowNumber - 1,
                        'endRowIndex' => $rowNumber,
                        'startColumnIndex' => 7,
                        'endColumnIndex' => 8,
                    ],
                    'cell' => ['userEnteredFormat' => $format],
                    'fields' => 'userEnteredFormat(backgroundColor,textFormat.foregroundColor,textFormat.bold)',
                ],
            ])],
        ]));
    }

    /** @return array<int, mixed> */
    private function orderValues(Order $order): array
    {
        $receivedAt = ($order->created_at ?: now())->copy()->timezone(config('app.timezone'));
        $deadline = collect([
            $order->delivery_date?->format('d/m/Y'),
            trim((string) $order->delivery_time),
        ])->filter()->implode(' ');

        return [
            $this->orderCode($order),
            $receivedAt->format('d/m/Y'),
            $receivedAt->format('H:i'),
            $order->user?->short_name ?: $order->user?->name ?: '',
            $order->customer?->name ?: $order->recipient_name ?: '',
            $order->recipient_phone ?: $order->customer?->phone ?: '',
            $deadline,
            $this->statusLabel($order),
            '', // Cột I do nhân sự vận hành xác nhận trực tiếp trên Sheet.
            $this->reviewNote($order),
        ];
    }

    /** @return array<int, array<int, mixed>> */
    private function detailValues(Order $order): array
    {
        return $order->items->values()->map(function ($item, int $index) use ($order): array {
            $requestedWeight = (float) ($item->total_weight ?? 0);
            if ($requestedWeight <= 0 && $item->effective_priced_by_kg) {
                $requestedWeight = (float) $item->display_total_value;
            }

            return [
                $this->orderCode($order),
                $index + 1,
                $item->display_name,
                $item->variant?->size ?: '',
                (float) ($item->quantity ?? 0),
                round(max(0, $requestedWeight), 3),
                '', // Chưa có trường pha/lóc riêng trong dữ liệu đơn.
                '',
                $this->specialRequest($order),
            ];
        })->all();
    }

    private function orderCode(Order $order): string
    {
        return trim((string) ($order->code ?: '#'.$order->id));
    }

    private function statusLabel(Order $order): string
    {
        if ($order->getAttribute('sheet_deleted')) {
            return 'Đã xóa';
        }
        if ($order->status === Order::STATUS_CANCELLED) {
            return 'Hủy đơn';
        }
        if ($order->trash_at) {
            return 'Đã xóa';
        }

        return [
            'draft' => 'Bản nháp',
            'pending' => 'Chờ gửi duyệt',
            Order::STATUS_PENDING_LEADER_APPROVAL => 'Chờ Leader duyệt',
            Order::STATUS_PENDING_MANAGER_APPROVAL => 'Chờ Manager duyệt',
            'pending_warehouse_approval' => 'Chờ Kho duyệt',
            Order::STATUS_APPROVED => 'Đã duyệt',
            Order::STATUS_REJECTED => 'Từ chối',
            Order::STATUS_READY_TO_PACK => 'Chờ đóng hàng',
            Order::STATUS_PACKING => 'Đang đóng hàng',
            Order::STATUS_READY_TO_SHIP => 'Chờ giao hàng',
            Order::STATUS_DELIVERING, Order::STATUS_IN_DELIVERY, Order::STATUS_SHIPPING => 'Đang giao hàng',
            Order::STATUS_DELIVERED => 'Đã giao hàng',
            Order::STATUS_COMPLETED => 'Hoàn thành',
            Order::STATUS_RETURNING => 'Đang trả hàng',
            Order::STATUS_RETURNED, Order::STATUS_RETURNED_COMPLETED => 'Đã trả hàng',
            Order::STATUS_ORDER_PLACED => 'Đơn mới',
            Order::STATUS_ORDER_CONFIRMED => 'Đã xác nhận',
            Order::STATUS_PACKED => 'Đã đóng gói',
        ][$order->status] ?? (Order::statusOptions()[$order->status] ?? str_replace('_', ' ', (string) $order->status));
    }

    private function reviewNote(Order $order): string
    {
        $notes = collect();
        if ($order->getAttribute('sheet_deleted')) {
            $notes->push('Đã xóa đơn');
            $notes->push($order->getAttribute('sheet_deletion_reason'));
        } elseif ($order->status === Order::STATUS_CANCELLED) {
            $notes->push('Hủy đơn');
            $notes->push($order->cancel_reason);
        }
        $notes->push($order->note);
        foreach ($order->adjustments ?? collect() as $adjustment) {
            $notes->push('YC thay đổi #'.$adjustment->id.': '.$adjustment->progressLabel());
            $notes->push($adjustment->adjustment_note ?: $adjustment->reject_reason);
        }

        return $notes->filter(fn ($note) => trim((string) $note) !== '')->unique()->implode(' — ');
    }

    private function specialRequest(Order $order): string
    {
        return collect([
            $order->note,
            ...collect($order->adjustments ?? [])->pluck('adjustment_note')->all(),
        ])->filter(fn ($note) => trim((string) $note) !== '')->unique()->implode(' — ');
    }

    private function deletedOrder(mixed $record): Order
    {
        $snapshot = data_get($record, 'snapshot', []);
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true) ?: [];
        }
        $attributes = (array) data_get($snapshot, 'order', []);
        $order = new Order;
        $order->setRawAttributes($attributes, true);
        $order->setAttribute('id', data_get($record, 'order_id', $attributes['id'] ?? null));
        $order->setAttribute('code', data_get($record, 'order_code', $attributes['code'] ?? null));
        $order->setAttribute('sheet_deleted', true);
        $order->setAttribute('sheet_deletion_reason', data_get($record, 'reason'));
        $order->setRelation('customer', new Customer((array) data_get($snapshot, 'customer', [])));
        $order->setRelation('user', new User((array) data_get($snapshot, 'sale', [])));
        $order->setRelation('adjustments', collect());
        $order->setRelation('items', collect(data_get($snapshot, 'items', []))->map(function (array $item): OrderItem {
            $model = new OrderItem([
                'product_id' => $item['product_id'] ?? null,
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'imported_name' => $item['product'] ?? 'Sản phẩm',
                'quantity' => $item['quantity'] ?? 0,
                'unit_weight' => $item['unit_weight'] ?? null,
                'total_weight' => $item['total_weight'] ?? null,
                'price' => $item['price'] ?? null,
                'total' => $item['total'] ?? null,
            ]);
            $model->setRelation('product', null);
            $model->setRelation('variant', null);

            return $model;
        }));

        return $order;
    }

    private function sheetRange(string $sheetName, string $cells): string
    {
        return "'".str_replace("'", "''", $sheetName)."'!{$cells}";
    }
}
