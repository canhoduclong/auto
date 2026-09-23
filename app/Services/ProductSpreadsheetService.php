<?php

namespace App\Services;

use App\Enums\ProductUnit;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ProductSpreadsheetService
{
    public const LIMIT = 2000;
    public const HEADERS = [
        'product_id', 'product_name', 'category_id', 'unit', 'product_type',
        'product_kg', 'is_priced_by_kg', 'product_status', 'description',
        'variant_id', 'sku', 'variant_name', 'size', 'variant_kg', 'inventory_name', 'variant_status', 'variant_is_priced_by_kg',
        'price', 'min_price',
    ];

    public function read(string $path, string $extension): array
    {
        $reader = IOFactory::createReader($extension === 'csv' ? 'Csv' : 'Xlsx');
        $info = $reader->listWorksheetInfo($path);
        if (empty($info) || $info[0]['totalRows'] > self::LIMIT + 1 || $info[0]['totalColumns'] > count(self::HEADERS)) {
            $this->fail(1, 'File tối đa '.self::LIMIT.' dòng dữ liệu và phải đúng số cột của file mẫu.');
        }
        if ($extension !== 'csv') $reader->setLoadSheetsOnly($info[0]['worksheetName']);
        $book = $reader->load($path);
        try {
            $sheet = $book->getSheet(0);
            $rows = [];
            for ($row = 1; $row <= $sheet->getHighestDataRow(); $row++) {
                $values = [];
                for ($column = 1; $column <= count(self::HEADERS); $column++) {
                    $cell = $sheet->getCell([$column, $row]);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $this->fail($row, 'Không dùng công thức Excel; vui lòng dán giá trị.');
                    }
                    $values[] = $cell->getValue();
                }
                $rows[] = $values;
            }
            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function import(array $rows, int $userId, bool $updateExisting = false): array
    {
        $headings = array_map(fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"), array_shift($rows) ?? []);
        if ($headings !== self::HEADERS) $this->fail(1, 'Tên hoặc thứ tự cột không đúng. Hãy tải file mẫu mới.');
        if (count($rows) > self::LIMIT) $this->fail(1, 'Vượt quá '.self::LIMIT.' dòng dữ liệu.');

        return DB::transaction(function () use ($rows, $userId, $updateExisting) {
            $counts = ['products_created' => 0, 'products_updated' => 0, 'variants_created' => 0, 'variants_updated' => 0];
            $productsInFile = [];
            $seenSkus = [];
            $seenVariants = [];
            $processed = 0;
            foreach ($rows as $index => $values) {
                $line = $index + 2;
                if (!array_filter($values, fn ($value) => $value !== null && trim((string) $value) !== '')) continue;
                if (count($values) !== count(self::HEADERS)) $this->fail($line, 'Số cột không đúng file mẫu.');
                $row = array_combine(self::HEADERS, array_map(fn ($value) => is_string($value) ? trim($value) : $value, $values));
                $row = array_map(fn ($value) => $value === '' ? null : $value, $row);
                foreach (['sku', 'size'] as $field) {
                    if ($row[$field] !== null) $row[$field] = (string) $row[$field];
                }
                foreach (['product_kg', 'variant_kg', 'size', 'price', 'min_price'] as $field) {
                    if (is_string($row[$field]) && preg_match('/^\d+,\d+$/', $row[$field])) $row[$field] = str_replace(',', '.', $row[$field]);
                }
                $validation = Validator::make($row, [
                    'product_id' => ['nullable', 'integer', 'min:1', 'exists:products,id'],
                    'product_name' => ['required', 'string', 'max:255'],
                    'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                    'unit' => ['required', Rule::in(ProductUnit::values())],
                    'product_type' => ['required', Rule::in(array_keys(Product::typeOptions()))],
                    'product_kg' => ['nullable', 'numeric', 'min:0', 'max:999999'],
                    'is_priced_by_kg' => ['required', 'boolean'],
                    'product_status' => ['required', 'boolean'],
                    'description' => ['nullable', 'string', 'max:10000'],
                    'variant_id' => ['nullable', 'integer', 'min:1', 'exists:product_variants,id'],
                    'sku' => ['nullable', 'string', 'max:255'],
                    'variant_name' => ['nullable', 'string', 'max:255'],
                    'size' => ['nullable', 'numeric', 'gt:0', 'max:99999999.99'],
                    'variant_kg' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
                    'inventory_name' => ['nullable', 'string', 'max:100'],
                    'variant_status' => ['nullable', 'boolean'],
                    'variant_is_priced_by_kg' => ['nullable', 'boolean'],
                    'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
                    'min_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
                ]);
                if ($validation->fails()) $this->fail($line, implode(' ', $validation->errors()->all()));
                if ($row['sku'] === null && $row['variant_id'] === null && collect($row)->only(['variant_name', 'size', 'variant_kg', 'inventory_name', 'variant_status', 'variant_is_priced_by_kg', 'price', 'min_price'])->contains(fn ($value) => $value !== null)) {
                    $this->fail($line, 'Cần có SKU khi nhập thông tin biến thể hoặc giá.');
                }
                if ($row['min_price'] !== null && $row['price'] === null) $this->fail($line, 'Cần nhập price khi nhập min_price.');
                if ((float) ($row['min_price'] ?? 0) > (float) ($row['price'] ?? 0)) $this->fail($line, 'Giá tối thiểu không được lớn hơn giá bán.');
                $skuKey = mb_strtolower((string) $row['sku']);
                if ($row['sku'] !== null && isset($seenSkus[$skuKey])) $this->fail($line, 'SKU bị lặp trong file: '.$row['sku']);
                if ($row['sku'] !== null) $seenSkus[$skuKey] = true;
                $matches = $row['sku'] !== null ? ProductVariant::whereRaw('LOWER(sku) = ?', [$skuKey])->lockForUpdate()->get() : collect();
                if ($matches->count() > 1) $this->fail($line, 'SKU đang trùng trong hệ thống, cần xử lý trước: '.$row['sku']);
                $variant = $matches->first();
                if ($row['variant_id'] !== null) {
                    $byId = ProductVariant::lockForUpdate()->findOrFail($row['variant_id']);
                    if ($variant && $variant->id !== $byId->id) $this->fail($line, 'SKU thuộc biến thể khác với variant_id.');
                    $variant = $byId;
                }
                if ($variant) {
                    if (isset($seenVariants[$variant->id])) $this->fail($line, 'Biến thể bị lặp trong file.');
                    $seenVariants[$variant->id] = true;
                }
                if ($variant && !$updateExisting) $this->fail($line, 'SKU đã tồn tại: '.$row['sku'].'. Chọn chế độ cập nhật để sửa.');

                $productData = [
                    'name' => $row['product_name'], 'category_id' => $row['category_id'] !== null ? (int) $row['category_id'] : null,
                    'unit' => $row['unit'], 'product_type' => $row['product_type'],
                    'kg' => $row['product_kg'] !== null ? (float) $row['product_kg'] : 1,
                    'is_priced_by_kg' => (bool) $row['is_priced_by_kg'],
                    'status' => (bool) $row['product_status'], 'description' => $row['description'],
                ];
                if ($row['product_id'] !== null) {
                    $product = Product::lockForUpdate()->find($row['product_id']);
                    if ($variant && (int) $variant->product_id !== (int) $product->id) $this->fail($line, 'SKU không thuộc product_id này.');
                } elseif ($variant) {
                    $product = Product::lockForUpdate()->findOrFail($variant->product_id);
                } else {
                    $matches = Product::where('name', $row['product_name'])->where('category_id', $row['category_id'])->lockForUpdate()->get();
                    if ($matches->count() > 1) $this->fail($line, 'Tên sản phẩm bị trùng; hãy điền product_id từ file xuất.');
                    $product = $matches->first();
                }
                if ($product && isset($productsInFile[$product->id]) && $productsInFile[$product->id] !== $productData) {
                    $this->fail($line, 'Thông tin chung của cùng một sản phẩm phải giống nhau ở mọi dòng.');
                }
                if (!$product) {
                    if ($row['category_id'] === null) $this->fail($line, 'Cần category_id khi tạo sản phẩm mới.');
                    $product = Product::create($productData + ['user_id' => $userId]);
                    $counts['products_created']++;
                } elseif (!isset($productsInFile[$product->id])) {
                    if (!$updateExisting) $this->fail($line, 'Sản phẩm đã tồn tại. Chọn chế độ cập nhật để thêm biến thể hoặc sửa sản phẩm.');
                    $product->update($productData);
                    $counts['products_updated']++;
                }
                $productsInFile[$product->id] = $productData;
                if ($row['sku'] !== null || $variant) {
                    if ($row['inventory_name'] !== null && ProductVariant::whereRaw('LOWER(TRIM(inventory_name)) = ?', [mb_strtolower($row['inventory_name'])])
                        ->when($variant, fn ($query) => $query->where('id', '!=', $variant->id))->exists()) {
                        $this->fail($line, 'Tên kho đã được dùng bởi biến thể khác.');
                    }
                    $isNew = !$variant;
                    $variant ??= new ProductVariant();
                    $variant->fill([
                        'product_id' => $product->id, 'sku' => $row['sku'], 'name' => $row['variant_name'],
                        'size' => $row['size'], 'kg' => $row['variant_kg'] ?? ($variant->kg ?? $product->kg ?? 1), 'inventory_name' => $row['inventory_name'],
                        'is_priced_by_kg' => $row['variant_is_priced_by_kg'] !== null ? (bool) $row['variant_is_priced_by_kg'] : ($variant->is_priced_by_kg ?? (bool) $row['is_priced_by_kg']),
                    ]);
                    $variant->status = $row['variant_status'] !== null ? (bool) $row['variant_status'] : ($isNew ? true : $variant->status);
                    $variant->save();
                    $counts[$isNew ? 'variants_created' : 'variants_updated']++;
                    if ($row['price'] !== null) {
                        $old = $variant->latestPriceRule()->first();
                        $price = (float) $row['price'];
                        $minimum = $row['min_price'] !== null ? (float) $row['min_price'] : (float) ($old?->min_price ?? 0);
                        if ($minimum > $price) $this->fail($line, 'Giá bán thấp hơn giá tối thiểu hiện có; vui lòng nhập lại min_price.');
                        if (!$old || (float) $old->price !== $price || (float) $old->min_price !== $minimum) {
                            $variant->priceRules()
                                ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', now()->toDateString()))
                                ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString()))
                                ->update(['end_date' => now()->subDay()->toDateString()]);
                            $rule = $variant->priceRules()->create(['price' => $price, 'min_price' => $minimum, 'start_date' => now()->toDateString(), 'created_by' => $userId, 'reason' => 'Nhập file sản phẩm']);
                            $variant->priceLogs()->create(['product_id' => $product->id, 'price_rule_id' => $rule->id, 'old_price' => $old?->price ?? 0, 'new_price' => $price, 'applied_at' => now(), 'applied_by' => $userId, 'user_id' => $userId]);
                        }
                    }
                }
                $processed++;
            }
            if (!$processed) $this->fail(2, 'File không có dữ liệu sản phẩm.');
            return $counts;
        });
    }

    public function productRows(iterable $products): \Generator
    {
        foreach ($products as $product) {
            foreach ($product->variants->isEmpty() ? [null] : $product->variants as $variant) {
                yield [
                    $product->id, $product->name, $product->category_id, $product->unit, $product->product_type ?: 'whole',
                    $product->kg, (int) $product->is_priced_by_kg, (int) $product->status, $product->description,
                    $variant?->id, $variant?->sku, $variant?->name, $variant?->size, $variant?->kg, $variant?->inventory_name,
                    $variant ? (int) $variant->status : null, $variant ? (int) $variant->effective_priced_by_kg : null, $variant?->latestPriceRule?->price, $variant?->latestPriceRule?->min_price,
                ];
            }
        }
    }

    public function workbook(iterable $rows): Spreadsheet
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('San pham');
        foreach (self::HEADERS as $index => $heading) $sheet->setCellValueExplicit([$index + 1, 1], $heading, DataType::TYPE_STRING);
        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                if ($value !== null) $sheet->setCellValueExplicit([$index + 1, $rowNumber], (string) $value, DataType::TYPE_STRING);
            }
            $rowNumber++;
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:S'.max(1, $rowNumber - 1));
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);
        foreach (range('A', 'S') as $column) $sheet->getColumnDimension($column)->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(36);
        return $book;
    }

    private function fail(int $line, string $message): never
    {
        throw ValidationException::withMessages(['file' => "Dòng {$line}: {$message}"]);
    }
}
