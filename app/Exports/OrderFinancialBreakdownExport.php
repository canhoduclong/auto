<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderFinancialBreakdownExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Ma don',
            'Ngay tao',
            'Khach hang',
            'Sale',
            'Tien hang',
            'Tien giam (discount)',
            'Giam them (discount ngoai)',
            'Tong giam',
            'Tong tien cuoi',
            'Trang thai don',
            'Trang thai thanh toan',
            'Trang thai giao hang',
        ];
    }
}
