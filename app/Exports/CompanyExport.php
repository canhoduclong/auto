<?php
namespace App\Exports;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompanyExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Company::select('id', 'name', 'mst', 'phone', 'email', 'address', 'note')->get();
    }

    public function headings(): array
    {
        return ['id', 'name', 'mst', 'phone', 'email', 'address', 'note'];
    }
}
