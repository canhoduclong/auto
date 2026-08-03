<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AccountingSalesLedgerExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    public function __construct(private readonly Collection $entries) {}

    public function collection(): Collection
    {
        return $this->entries->map(fn ($entry) => [
            $entry->entry_date?->format('d/m/Y'),
            (int) $entry->entry_month,
            $entry->customer_code,
            $entry->customer_name,
            $entry->sale_name,
            $entry->unit,
            (float) $entry->quantity,
            (float) $entry->unit_weight,
            (float) $entry->total_quantity,
            $entry->unit_price === null ? null : (float) $entry->unit_price,
            (float) $entry->total_amount,
        ]);
    }

    public function headings(): array
    {
        return ['Ngày tháng', 'Tháng', 'Mã KH', 'Khách hàng', 'NVKD', 'DVT', 'SL', 'Kg/con', 'Tổng', 'Đơn giá', 'Tổng tiền'];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'G' => '#,##0.0',
            'H' => '#,##0.00',
            'I' => '#,##0.0',
            'J' => '#,##0',
            'K' => '#,##0',
        ];
    }
}
