<?php

namespace App\Services;

use App\Models\Order;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
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
        $order->loadMissing(['customer:id,name,phone', 'user:id,name,short_name', 'items.product', 'items.variant.product']);

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
                'status' => $order->status === Order::STATUS_CANCELLED ? 'Hủy đơn' : 'Đơn mới',
            ];
        });
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
        if ($order->status === Order::STATUS_CANCELLED || $existingStatus === 'Hủy đơn') {
            $data[] = new ValueRange([
                'range' => $this->sheetRange($sheet['title'], "H{$rowNumber}"),
                'majorDimension' => 'ROWS',
                'values' => [[$values[7]]],
            ]);
        }
        $service->spreadsheets_values->batchUpdate(
            $spreadsheetId,
            new BatchUpdateValuesRequest(['valueInputOption' => 'RAW', 'data' => $data])
        );

        return [
            'row' => $rowNumber,
            'format_cancelled' => $order->status === Order::STATUS_CANCELLED
                ? true
                : ($existingStatus === 'Hủy đơn' ? false : null),
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
            $order->status === Order::STATUS_CANCELLED ? 'Hủy đơn' : 'Đơn mới',
            '', // Cột I do nhân sự vận hành xác nhận trực tiếp trên Sheet.
            $order->status === Order::STATUS_CANCELLED
                ? collect(['Hủy đơn', $order->cancel_reason, $order->note])->filter()->implode(' — ')
                : (string) ($order->note ?? ''),
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
                (string) ($order->note ?? ''),
            ];
        })->all();
    }

    private function orderCode(Order $order): string
    {
        return trim((string) ($order->code ?: '#'.$order->id));
    }

    private function sheetRange(string $sheetName, string $cells): string
    {
        return "'".str_replace("'", "''", $sheetName)."'!{$cells}";
    }
}
