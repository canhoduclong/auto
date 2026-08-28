<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Warehouse;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleSheetsInventoryService
{
    /** @return array<string, mixed> */
    public function preview(Warehouse $warehouse, string $selectedDate): array
    {
        $spreadsheetId = trim((string) config('services.google_sheets.inventory_spreadsheet_id'));
        $sheetId = (int) config('services.google_sheets.inventory_sheet_id');
        if ($spreadsheetId === '' || $sheetId <= 0) {
            throw new RuntimeException('Chưa cấu hình Google Sheet tồn kho.');
        }

        $service = new Sheets($this->client());
        $sheetTitle = $this->sheetTitle($service, $spreadsheetId, $sheetId);
        $rangeTitle = "'".str_replace("'", "''", $sheetTitle)."'";
        $response = $service->spreadsheets_values->get(
            $spreadsheetId,
            $rangeTitle.'!A1:ZZ100',
            ['valueRenderOption' => 'FORMATTED_VALUE', 'dateTimeRenderOption' => 'FORMATTED_STRING']
        );

        $variants = ProductVariant::query()
            ->with('product:id,name')
            ->get(['id', 'product_id', 'name', 'sku', 'inventory_name', 'size']);

        return array_merge(
            $this->parseValues($response->getValues() ?? [], $warehouse, $selectedDate, $variants),
            [
                'spreadsheet_id' => $spreadsheetId,
                'sheet_id' => $sheetId,
                'sheet_name' => $sheetTitle,
                'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/'.$spreadsheetId.'/edit?gid='.$sheetId.'#gid='.$sheetId,
            ]
        );
    }

    /**
     * @param  array<int, array<int, mixed>>  $values
     * @return array<string, mixed>
     */
    public function parseValues(array $values, Warehouse $warehouse, string $selectedDate, Collection $variants): array
    {
        if (count($values) < 3) {
            throw new RuntimeException('Google Sheet không có đủ hàng tiêu đề để đọc tồn kho.');
        }

        $dateHeader = $values[1] ?? [];
        $typeHeader = $values[2] ?? [];
        $columnDates = [];
        $currentDate = null;
        $availableDates = [];
        $maxColumns = max(count($dateHeader), count($typeHeader));

        for ($column = 1; $column < $maxColumns; $column++) {
            $headerValue = trim((string) ($dateHeader[$column] ?? ''));
            if ($headerValue !== '') {
                try {
                    $currentDate = Carbon::createFromFormat('d/m/Y', $headerValue)->toDateString();
                } catch (\Throwable) {
                    $currentDate = null;
                }
            }

            $columnDates[$column] = $currentDate;
            if ($currentDate !== null && $this->normalize((string) ($typeHeader[$column] ?? '')) === 'ton') {
                $availableDates[$currentDate] = $column;
            }
        }

        ksort($availableDates);
        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        if (! array_key_exists($selectedDate, $availableDates)) {
            $range = $availableDates === []
                ? 'không tìm thấy ngày nào'
                : Carbon::parse(array_key_first($availableDates))->format('d/m/Y').' – '.Carbon::parse(array_key_last($availableDates))->format('d/m/Y');
            throw new RuntimeException('Ngày '.Carbon::parse($selectedDate)->format('d/m/Y').' không có cột Tồn trong sheet (phạm vi: '.$range.').');
        }

        $stockColumn = $availableDates[$selectedDate];
        $section = Str::contains($this->normalize($warehouse->name), 'chien luoc') ? 'strategic' : 'main';
        $strategicStart = collect($values)->search(fn ($row) => $this->normalize((string) ($row[0] ?? '')) === 'chien luoc');
        $strategicStart = $strategicStart === false ? count($values) : (int) $strategicStart;
        $start = $section === 'strategic' ? $strategicStart + 1 : 0;
        $end = $section === 'strategic' ? count($values) : $strategicStart;

        $variantsForInventory = $variants
            ->filter(function (ProductVariant $variant): bool {
                $identity = $this->normalize(implode(' ', [
                    $variant->product?->name,
                    $variant->name,
                    $variant->sku,
                ]));

                return Str::contains($identity, ['moc', 'vit nguyen con']);
            });
        $variantsByInventoryName = $variants
            ->filter(fn (ProductVariant $variant) => filled($variant->inventory_name))
            ->groupBy(fn (ProductVariant $variant) => $this->canonicalInventoryName((string) $variant->inventory_name));
        $variantBySize = $variantsForInventory
            ->mapWithKeys(function (ProductVariant $variant): array {
                $size = is_numeric($variant->size) ? (float) $variant->size : null;
                if ($size === null && preg_match('/(\d+(?:[.,]\d+)?)/', (string) ($variant->sku ?: $variant->name), $matches)) {
                    $size = (float) str_replace(',', '.', $matches[1]);
                }

                return $size === null ? [] : [number_format($size, 1, '.', '') => $variant];
            });

        $rows = collect();
        for ($rowIndex = $start; $rowIndex < $end; $rowIndex++) {
            $sheetCode = trim((string) ($values[$rowIndex][0] ?? ''));
            $configuredMatches = $variantsByInventoryName->get($this->canonicalInventoryName($sheetCode), collect());
            $isMocCode = preg_match('/^M\s*(\d+)(?:[,.](\d+))?$/iu', $sheetCode, $matches) === 1;
            if ($configuredMatches->isEmpty() && ! $isMocCode) {
                continue;
            }

            $size = $isMocCode
                ? number_format((float) ($matches[1].'.'.($matches[2] ?? '0')), 1, '.', '')
                : null;
            $quantity = $this->number($values[$rowIndex][$stockColumn] ?? null);
            $hasAmbiguousInventoryName = $configuredMatches->count() > 1;
            $variant = $configuredMatches->count() === 1
                ? $configuredMatches->first()
                : ($hasAmbiguousInventoryName || $size === null ? null : $variantBySize->get($size));
            $matchMethod = $variant
                ? ($configuredMatches->count() === 1 ? 'inventory_name' : 'fallback_size')
                : null;
            $rows->push([
                'sheet_row' => $rowIndex + 1,
                'sheet_code' => $sheetCode,
                'normalized_code' => $size !== null ? 'MOC - '.$size : ($variant?->inventory_name ?: $sheetCode),
                'quantity' => $quantity,
                'variant_id' => $variant?->id,
                'variant_name' => $variant ? trim(($variant->product?->name ? $variant->product->name.' – ' : '').($variant->name ?: $variant->sku)) : null,
                'variant_sku' => $variant?->sku,
                'matched' => $variant !== null,
                'match_method' => $matchMethod,
                'match_error' => $hasAmbiguousInventoryName ? 'Tên tồn kho đang bị trùng giữa nhiều biến thể.' : null,
            ]);
        }

        if ($rows->isEmpty()) {
            throw new RuntimeException('Không tìm thấy các dòng mã Móc trong khu vực kho tương ứng trên sheet.');
        }

        $positiveRows = $rows->where('quantity', '>', 0)->values();
        $unmatchedPositive = $positiveRows->where('matched', false)->values();

        return [
            'selected_date' => $selectedDate,
            'available_dates' => array_keys($availableDates),
            'stock_column' => $stockColumn + 1,
            'warehouse_section' => $section,
            'warehouse_section_label' => $section === 'strategic' ? 'CHIẾN LƯỢC' : 'QUAY LÔNG / HÀNG MÓC',
            'rows' => $rows,
            'import_rows' => $positiveRows->where('matched', true)->values(),
            'unmatched_positive_rows' => $unmatchedPositive,
            'total_quantity' => (float) $positiveRows->where('matched', true)->sum('quantity'),
            'has_blocking_errors' => $unmatchedPositive->isNotEmpty(),
        ];
    }

    public function serviceAccountEmail(): ?string
    {
        try {
            $credentials = json_decode((string) file_get_contents($this->credentialsPath()), true);

            return is_array($credentials) ? ($credentials['client_email'] ?? null) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function client(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setApplicationName((string) config('app.name', 'Hoang Long TNT'));
        $client->setAuthConfig($this->credentialsPath());
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);

        return $client;
    }

    private function credentialsPath(): string
    {
        $configured = trim((string) config('services.google_sheets.credentials'));
        $path = str_starts_with($configured, DIRECTORY_SEPARATOR) ? $configured : base_path($configured);
        if ($configured === '' || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Không đọc được khóa Google Sheets trên máy chủ.');
        }

        return $path;
    }

    private function sheetTitle(Sheets $service, string $spreadsheetId, int $sheetId): string
    {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId, ['fields' => 'sheets.properties(sheetId,title)']);
        foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
            if ((int) $sheet->getProperties()?->getSheetId() === $sheetId) {
                return (string) $sheet->getProperties()->getTitle();
            }
        }

        throw new RuntimeException('Không tìm thấy trang tính gid='.$sheetId.'.');
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    private function canonicalInventoryName(string $value): string
    {
        $value = Str::of($value)->ascii()->lower()->trim()->toString();
        if (preg_match('/^m\s*(\d+)(?:\s*[,.]\s*(\d+))?$/', $value, $matches)) {
            return 'm:'.number_format((float) ($matches[1].'.'.($matches[2] ?? '0')), 1, '.', '');
        }

        return $this->normalize($value);
    }

    private function number(mixed $value): float
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/^-+$/', preg_replace('/\s+/', '', $value))) {
            return 0;
        }

        $value = preg_replace('/\s+/u', '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        return is_numeric($value) ? max(0, round((float) $value, 3)) : 0;
    }
}
