<?php
namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerExport implements FromCollection, WithHeadings
{
    public function __construct(private Builder $customers) {}

    public function collection()
    {
        return (clone $this->customers)
            ->select('customers.id', 'customers.name', 'customers.phone', 'customers.email', 'customers.website',
                'customers.gender', 'customers.dob', 'customers.customer_type_id', 'customers.note', 'customers.size', 'customers.production')
            ->addSelect(['address' => \Illuminate\Support\Facades\DB::table('customer_addresses')
                ->select('note')->whereColumn('customer_id', 'customers.id')->where('is_default', true)->orderBy('id')->limit(1)])
            ->orderBy('customers.name')->orderBy('customers.id')->get();
    }
    public function headings(): array
    {
        return ['id','name','phone','email','website','gender','dob','customer_type_id','note','size','production','address'];
    }
}
