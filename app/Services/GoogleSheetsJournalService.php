<?php

namespace App\Services;

use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\Request as SheetsRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Collection;
use RuntimeException;

class GoogleSheetsJournalService
{
    private const HEADERS = [
        'Ngày tháng',
        'Tháng',
        'Mã KH',
        'Khách hàng',
        'NVKD',
        'Sản phẩm',
        'SL',
        'Kg/con',
        'Tổng',
        'Đơn giá',
        'Tổng tiền',
    ];

    /**
     * Replace the configured worksheet with all supplied journal rows.
     *
     * @return array{rows:int, spreadsheet_url:string, sheet_name:string}
     */
    public function replaceJournal(Collection $rows): array
    {
        $spreadsheetId = trim((string) config('services.google_sheets.spreadsheet_id'));
        $sheetName = trim((string) config('services.google_sheets.sheet_name', 'Nhật ký bán hàng'));
        if ($spreadsheetId === '' || $sheetName === '') {
            throw new RuntimeException('Chưa cấu hình Google Sheets spreadsheet hoặc tên trang tính.');
        }

        $service = new Sheets($this->client());
        $sheetId = $this->ensureSheetExists($service, $spreadsheetId, $sheetName);
        $rangePrefix = "'".str_replace("'", "''", $sheetName)."'";

        $service->spreadsheets_values->clear(
            $spreadsheetId,
            $rangePrefix.'!A:K',
            new ClearValuesRequest
        );

        $values = collect([self::HEADERS])
            ->concat($rows->map(fn ($row) => [
                Carbon::parse($row->entry_date)->format('d/m/Y'),
                (int) $row->entry_month,
                (string) $row->customer_code,
                (string) $row->customer_name,
                (string) $row->sale_name,
                (string) $row->unit,
                (float) $row->quantity,
                (float) $row->unit_weight,
                (float) $row->total_quantity,
                $row->unit_price === null ? '' : (float) $row->unit_price,
                (float) $row->total_amount,
            ]))
            ->values()
            ->all();

        $service->spreadsheets_values->update(
            $spreadsheetId,
            $rangePrefix.'!A1',
            new ValueRange([
                'majorDimension' => 'ROWS',
                'values' => $values,
            ]),
            ['valueInputOption' => 'RAW']
        );

        $this->formatSheet($service, $spreadsheetId, $sheetId, count($values));

        return [
            'rows' => $rows->count(),
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/'.$spreadsheetId.'/edit',
            'sheet_name' => $sheetName,
        ];
    }

    public function isConfigured(): bool
    {
        try {
            return is_file($this->credentialsPath())
                && trim((string) config('services.google_sheets.spreadsheet_id')) !== '';
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

    private function ensureSheetExists(Sheets $service, string $spreadsheetId, string $sheetName): int
    {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId, [
            'fields' => 'sheets.properties(sheetId,title)',
        ]);

        foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
            $properties = $sheet->getProperties();
            if ($properties?->getTitle() === $sheetName) {
                return (int) $properties->getSheetId();
            }
        }

        $response = $service->spreadsheets->batchUpdate(
            $spreadsheetId,
            new BatchUpdateSpreadsheetRequest([
                'requests' => [
                    new SheetsRequest([
                        'addSheet' => ['properties' => ['title' => $sheetName]],
                    ]),
                ],
            ])
        );

        $sheetId = $response->getReplies()[0]?->getAddSheet()?->getProperties()?->getSheetId();
        if ($sheetId === null) {
            throw new RuntimeException('Google Sheets không trả về ID của trang tính vừa tạo.');
        }

        return (int) $sheetId;
    }

    private function formatSheet(Sheets $service, string $spreadsheetId, int $sheetId, int $rowCount): void
    {
        $service->spreadsheets->batchUpdate(
            $spreadsheetId,
            new BatchUpdateSpreadsheetRequest([
                'requests' => [
                    new SheetsRequest([
                        'updateSheetProperties' => [
                            'properties' => [
                                'sheetId' => $sheetId,
                                'gridProperties' => ['frozenRowCount' => 1],
                            ],
                            'fields' => 'gridProperties.frozenRowCount',
                        ],
                    ]),
                    new SheetsRequest([
                        'repeatCell' => [
                            'range' => [
                                'sheetId' => $sheetId,
                                'startRowIndex' => 0,
                                'endRowIndex' => 1,
                                'startColumnIndex' => 0,
                                'endColumnIndex' => count(self::HEADERS),
                            ],
                            'cell' => [
                                'userEnteredFormat' => [
                                    'backgroundColor' => ['red' => .12, 'green' => .32, 'blue' => .55],
                                    'textFormat' => ['bold' => true, 'foregroundColor' => ['red' => 1, 'green' => 1, 'blue' => 1]],
                                ],
                            ],
                            'fields' => 'userEnteredFormat(backgroundColor,textFormat)',
                        ],
                    ]),
                    new SheetsRequest([
                        'setBasicFilter' => [
                            'filter' => [
                                'range' => [
                                    'sheetId' => $sheetId,
                                    'startRowIndex' => 0,
                                    'endRowIndex' => max(1, $rowCount),
                                    'startColumnIndex' => 0,
                                    'endColumnIndex' => count(self::HEADERS),
                                ],
                            ],
                        ],
                    ]),
                    new SheetsRequest([
                        'autoResizeDimensions' => [
                            'dimensions' => [
                                'sheetId' => $sheetId,
                                'dimension' => 'COLUMNS',
                                'startIndex' => 0,
                                'endIndex' => count(self::HEADERS),
                            ],
                        ],
                    ]),
                ],
            ])
        );
    }
}
