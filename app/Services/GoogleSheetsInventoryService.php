<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Warehouse;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleSheetsInventoryService
{
    /** @return array<string, mixed> */
    public function preview(Warehouse $warehouse, string $selectedDate): array
    {
        $source = $this->configuration($warehouse);
        $spreadsheetId = $source['spreadsheet_id'];
        $sheetId = $source['sheet_id'];
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

    /** @return array{spreadsheet_id:string,sheet_id:int,spreadsheet_url:string} */
    public function configuration(Warehouse $warehouse): array
    {
        $prefix = $this->settingPrefix($warehouse);
        $spreadsheetId = trim((string) Setting::get(
            $prefix.'spreadsheet_id',
            config('services.google_sheets.inventory_spreadsheet_id')
        ));
        $sheetId = (int) Setting::get(
            $prefix.'sheet_id',
            config('services.google_sheets.inventory_sheet_id')
        );

        if ($spreadsheetId === '' || $sheetId < 0) {
            throw new RuntimeException('Chưa cấu hình Google Sheet tồn kho.');
        }

        return [
            'spreadsheet_id' => $spreadsheetId,
            'sheet_id' => $sheetId,
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/'.$spreadsheetId.'/edit?gid='.$sheetId.'#gid='.$sheetId,
        ];
    }

    /** @return array{spreadsheet_id:string,sheet_id:int,spreadsheet_url:string} */
    public function saveConfiguration(Warehouse $warehouse, string $spreadsheetSource, int $sheetId): array
    {
        $spreadsheetId = $this->extractSpreadsheetId($spreadsheetSource);
        $prefix = $this->settingPrefix($warehouse);

        Setting::set($prefix.'spreadsheet_id', $spreadsheetId);
        Setting::set($prefix.'sheet_id', (string) $sheetId);

        return $this->configuration($warehouse);
    }

    /** @return array{spreadsheet_id:string,sheet_id:int|null,spreadsheet_url:string|null} */
    public function exportConfiguration(Warehouse $warehouse): array
    {
        $prefix = $this->settingPrefix($warehouse);
        $spreadsheetId = trim((string) Setting::get($prefix.'export_spreadsheet_id', ''));
        $storedSheetId = Setting::get($prefix.'export_sheet_id');
        $sheetId = $storedSheetId === null || $storedSheetId === '' ? null : (int) $storedSheetId;

        return [
            'spreadsheet_id' => $spreadsheetId,
            'sheet_id' => $sheetId,
            'spreadsheet_url' => $spreadsheetId !== '' && $sheetId !== null
                ? 'https://docs.google.com/spreadsheets/d/'.$spreadsheetId.'/edit?gid='.$sheetId.'#gid='.$sheetId
                : null,
        ];
    }

    /** @return array{spreadsheet_id:string,sheet_id:int,spreadsheet_url:string} */
    public function saveExportConfiguration(Warehouse $warehouse, string $spreadsheetSource, int $sheetId): array
    {
        $spreadsheetId = $this->extractSpreadsheetId($spreadsheetSource);
        $prefix = $this->settingPrefix($warehouse);

        Setting::set($prefix.'export_spreadsheet_id', $spreadsheetId);
        Setting::set($prefix.'export_sheet_id', (string) $sheetId);

        /** @var array{spreadsheet_id:string,sheet_id:int,spreadsheet_url:string} */
        return $this->exportConfiguration($warehouse);
    }

    /** @return array{rows:int,spreadsheet_id:string,sheet_id:int,spreadsheet_url:string,sheet_name:string,stock_column:int} */
    public function closingInventoryForDate(Warehouse $warehouse, string $selectedDate): Collection
    {
        $inventories = Inventory::query()
            ->where('warehouse_id', $warehouse->id)
            ->get();
        $futureMovements = InventoryMovement::query()
            ->whereIn('inventory_id', $inventories->pluck('id'))
            ->where('created_at', '>', Carbon::parse($selectedDate)->endOfDay())
            ->selectRaw('inventory_id, SUM(quantity) AS quantity_after')
            ->groupBy('inventory_id')
            ->pluck('quantity_after', 'inventory_id');
        return $inventories
            ->groupBy('product_variant_id')
            ->map(fn (Collection $rows): float => round((float) $rows->sum(
                fn (Inventory $inventory): float => (float) $inventory->quantity
                    - (float) ($futureMovements[$inventory->id] ?? 0)
            ), 3));
    }

    public function exportInventoryPreview(Warehouse $warehouse, string $selectedDate): Collection
    {
        $closing = $this->closingInventoryForDate($warehouse, $selectedDate);

        return ProductVariant::query()->with('product:id,name')
            ->whereIn('id', $closing->keys())->orderBy('product_id')->orderBy('id')->get()
            ->map(fn (ProductVariant $variant): array => [
                'variant_id' => $variant->id,
                'product_name' => $variant->product?->name ?? '—',
                'sku' => $variant->sku,
                'size' => $variant->size,
                'inventory_name' => $variant->inventory_name ?: $variant->name,
                'closing' => $closing->get($variant->id, 0),
            ]);
    }

    public function writeDailyInventory(Warehouse $warehouse, string $selectedDate): array
    {
        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $source = $this->exportConfiguration($warehouse);
        if ($source['spreadsheet_id'] === '' || $source['sheet_id'] === null) {
            throw new RuntimeException('Chưa cấu hình file Google Sheet đích để ghi tồn kho.');
        }
        $service = new Sheets($this->client());
        $sheetTitle = $this->sheetTitle($service, $source['spreadsheet_id'], $source['sheet_id']);
        $rangeTitle = "'".str_replace("'", "''", $sheetTitle)."'";
        $response = $service->spreadsheets_values->get(
            $source['spreadsheet_id'],
            $rangeTitle.'!A1:ZZ100',
            ['valueRenderOption' => 'FORMATTED_VALUE', 'dateTimeRenderOption' => 'FORMATTED_STRING']
        );
        $variants = ProductVariant::query()
            ->with('product:id,name')
            ->get(['id', 'product_id', 'name', 'sku', 'inventory_name', 'size']);
        $parsed = $this->parseValues($response->getValues() ?? [], $warehouse, $selectedDate, $variants);
        $matchedRows = $parsed['rows']->where('matched', true)->values();

        if ($matchedRows->isEmpty()) {
            throw new RuntimeException('Không có sản phẩm nào trên Sheet được ánh xạ để ghi tồn kho.');
        }

        $closingByVariant = $this->closingInventoryForDate($warehouse, $selectedDate);
        $stockColumn = (int) $parsed['stock_column'];
        $columnLetter = $this->columnLetter($stockColumn);
        $updates = $matchedRows->map(function (array $row) use ($rangeTitle, $columnLetter, $closingByVariant): ValueRange {
            return new ValueRange([
                'range' => $rangeTitle.'!'.$columnLetter.$row['sheet_row'],
                'values' => [[(float) ($closingByVariant[(int) $row['variant_id']] ?? 0)]],
            ]);
        })->all();

        $service->spreadsheets_values->batchUpdate(
            $source['spreadsheet_id'],
            new BatchUpdateValuesRequest([
                'valueInputOption' => 'RAW',
                'data' => $updates,
            ])
        );

        return [
            'rows' => count($updates),
            'spreadsheet_id' => $source['spreadsheet_id'],
            'sheet_id' => $source['sheet_id'],
            'spreadsheet_url' => $source['spreadsheet_url'],
            'sheet_name' => $sheetTitle,
            'stock_column' => $stockColumn,
        ];
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
        $importColumns = collect($columnDates)
            ->filter(fn (?string $date): bool => $date === $selectedDate)
            ->keys()
            ->filter(fn (int $column): bool => str_starts_with(
                $this->normalize((string) ($typeHeader[$column] ?? '')),
                'nhap'
            ))
            ->values();
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
            $stockQuantity = $this->number($values[$rowIndex][$stockColumn] ?? null);
            $importQuantity = (float) $importColumns->sum(
                fn (int $column): float => $this->number($values[$rowIndex][$column] ?? null)
            );
            $quantity = round($stockQuantity + $importQuantity, 3);
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
                'stock_quantity' => $stockQuantity,
                'import_quantity' => $importQuantity,
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
            'import_columns' => $importColumns->map(fn (int $column): int => $column + 1)->all(),
            'import_column_labels' => $importColumns
                ->map(fn (int $column): string => trim((string) ($typeHeader[$column] ?? 'Nhập')))
                ->all(),
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
        $client->setScopes([Sheets::SPREADSHEETS]);

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

    private function settingPrefix(Warehouse $warehouse): string
    {
        return 'warehouse.google_sheet_inventory.'.$warehouse->id.'.';
    }

    private function extractSpreadsheetId(string $source): string
    {
        $source = trim($source);
        if (preg_match('~/spreadsheets/d/([a-zA-Z0-9_-]+)~', $source, $matches)) {
            return $matches[1];
        }
        if (preg_match('/^[a-zA-Z0-9_-]{10,}$/', $source)) {
            return $source;
        }

        throw new RuntimeException('Link file hoặc Spreadsheet ID không hợp lệ.');
    }

    private function columnLetter(int $column): string
    {
        $letter = '';
        while ($column > 0) {
            $column--;
            $letter = chr(65 + ($column % 26)).$letter;
            $column = intdiv($column, 26);
        }

        return $letter;
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
