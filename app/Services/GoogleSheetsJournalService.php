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
use Illuminate\Support\Facades\Cache;
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
        $sheet = $this->resolveSheet($service, $spreadsheetId, $sheetName);
        $sheetId = $sheet['id'];
        $sheetName = $sheet['title'];
        $rangePrefix = "'".str_replace("'", "''", $sheetName)."'";

        $values = collect([self::HEADERS])
            ->concat($this->journalValues($rows))
            ->values()
            ->all();

        $this->writeValues($service, $spreadsheetId, $sheetId, $rangePrefix, $values);

        return [
            'rows' => $rows->count(),
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/'.$spreadsheetId.'/edit',
            'sheet_name' => $sheetName,
        ];
    }

    /**
     * Read the worksheet and replace only rows belonging to the supplied dates.
     * Rows from every other date remain untouched.
     *
     * @param  array<int, string>  $dates
     * @return array{rows:int, dates:int, spreadsheet_url:string, sheet_name:string}
     */
    public function syncJournalDates(Collection $rows, array $dates): array
    {
        $targetDates = collect($dates)
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($targetDates->isEmpty()) {
            throw new RuntimeException('Không có ngày bán hàng để đồng bộ Google Sheets.');
        }

        return Cache::lock('google-sheets-journal-sync', 120)->block(30, function () use ($rows, $targetDates): array {
            $spreadsheetId = trim((string) config('services.google_sheets.spreadsheet_id'));
            $sheetName = trim((string) config('services.google_sheets.sheet_name', 'Nhật ký bán hàng'));
            if ($spreadsheetId === '' || $sheetName === '') {
                throw new RuntimeException('Chưa cấu hình Google Sheets spreadsheet hoặc tên trang tính.');
            }

            $service = new Sheets($this->client());
            $sheet = $this->resolveSheet($service, $spreadsheetId, $sheetName);
            $sheetId = $sheet['id'];
            $sheetName = $sheet['title'];
            $rangePrefix = "'".str_replace("'", "''", $sheetName)."'";
            $response = $service->spreadsheets_values->get($spreadsheetId, $rangePrefix.'!A:K');
            $existingRows = collect($response->getValues() ?? [])->skip(1);

            $preservedRows = $existingRows
                ->filter(function (array $row) use ($targetDates): bool {
                    $date = $this->normalizeSheetDate($row[0] ?? null);

                    return $date === null || ! $targetDates->contains($date);
                });

            $mergedRows = $preservedRows
                ->concat($this->journalValues($rows))
                ->sortByDesc(function (array $row): string {
                    return $this->normalizeSheetDate($row[0] ?? null) ?? '0000-00-00';
                })
                ->values();
            $values = collect([self::HEADERS])->concat($mergedRows)->all();

            $this->writeValues($service, $spreadsheetId, $sheetId, $rangePrefix, $values);

            return [
                'rows' => $rows->count(),
                'dates' => $targetDates->count(),
                'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/'.$spreadsheetId.'/edit',
                'sheet_name' => $sheetName,
            ];
        });
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

        $credentials = json_decode((string) file_get_contents($path), true);
        if (! is_array($credentials)
            || ($credentials['type'] ?? null) !== 'service_account'
            || empty($credentials['client_email'])
            || empty($credentials['private_key'])) {
            throw new RuntimeException('Tệp khóa Google không phải JSON service account hợp lệ.');
        }

        return $path;
    }

    /** @return array{id:int, title:string} */
    private function resolveSheet(Sheets $service, string $spreadsheetId, string $sheetName): array
    {
        $configuredSheetId = (int) config('services.google_sheets.sheet_id', 0);
        $spreadsheet = $service->spreadsheets->get($spreadsheetId, [
            'fields' => 'sheets.properties(sheetId,title)',
        ]);

        foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
            $properties = $sheet->getProperties();
            if ($configuredSheetId > 0 && (int) $properties?->getSheetId() === $configuredSheetId) {
                return ['id' => $configuredSheetId, 'title' => (string) $properties->getTitle()];
            }
        }

        foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
            $properties = $sheet->getProperties();
            if ($properties?->getTitle() === $sheetName) {
                return ['id' => (int) $properties->getSheetId(), 'title' => $sheetName];
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

        return ['id' => (int) $sheetId, 'title' => $sheetName];
    }

    private function journalValues(Collection $rows): Collection
    {
        return $rows->map(fn ($row) => [
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
        ]);
    }

    private function normalizeSheetDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                // Preserve rows whose date is not in a supported format.
            }
        }

        return null;
    }

    private function writeValues(
        Sheets $service,
        string $spreadsheetId,
        int $sheetId,
        string $rangePrefix,
        array $values
    ): void {
        // A basic filter cannot cover merged cells. Unmerge only the merged
        // ranges intersecting the journal table and leave the rest untouched.
        // Do this before writing so every exported value has its own cell.
        $this->unmergeJournalCells($service, $spreadsheetId, $sheetId, count($values));

        $service->spreadsheets_values->update(
            $spreadsheetId,
            $rangePrefix.'!A1',
            new ValueRange([
                'majorDimension' => 'ROWS',
                'values' => $values,
            ]),
            ['valueInputOption' => 'RAW']
        );

        // Only remove rows left over from a previous, larger export. Writing
        // first means a temporary Google API failure never leaves the sheet
        // completely blank.
        $service->spreadsheets_values->clear(
            $spreadsheetId,
            $rangePrefix.'!A'.(count($values) + 1).':K',
            new ClearValuesRequest
        );

        $this->formatSheet($service, $spreadsheetId, $sheetId, count($values));
    }

    private function unmergeJournalCells(
        Sheets $service,
        string $spreadsheetId,
        int $sheetId,
        int $rowCount
    ): void {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId, [
            'fields' => 'sheets(properties.sheetId,merges)',
        ]);
        $requests = [];
        $columnCount = count(self::HEADERS);

        foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
            if ((int) $sheet->getProperties()?->getSheetId() !== $sheetId) {
                continue;
            }

            foreach ($sheet->getMerges() ?? [] as $merge) {
                $startRow = (int) ($merge->getStartRowIndex() ?? 0);
                $endRow = (int) ($merge->getEndRowIndex() ?? 0);
                $startColumn = (int) ($merge->getStartColumnIndex() ?? 0);
                $endColumn = (int) ($merge->getEndColumnIndex() ?? 0);

                if ($startRow >= $rowCount || $endRow <= 0
                    || $startColumn >= $columnCount || $endColumn <= 0) {
                    continue;
                }

                // Use the merge's exact range because Google rejects an
                // unmerge request that only partially spans a merged region.
                $requests[] = new SheetsRequest([
                    'unmergeCells' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'startRowIndex' => $startRow,
                            'endRowIndex' => $endRow,
                            'startColumnIndex' => $startColumn,
                            'endColumnIndex' => $endColumn,
                        ],
                    ],
                ]);
            }

            break;
        }

        if ($requests !== []) {
            $service->spreadsheets->batchUpdate(
                $spreadsheetId,
                new BatchUpdateSpreadsheetRequest(['requests' => $requests])
            );
        }
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
                        'clearBasicFilter' => ['sheetId' => $sheetId],
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
