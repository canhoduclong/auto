<?php
namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Customer::leftjoin('customer_addresses', function($join) {
            $join->on('customers.id', '=', 'customer_addresses.customer_id')
                 ->where('customer_addresses.is_default', '=', 1);
        })
        ->select('customers.id','customers.name','customers.phone','customers.email','customers.website','customers.gender','customers.dob','customers.customer_type_id','customers.note', 'customer_addresses.note as address')
        ->get();
    }
    public function headings(): array
    {
        return ['id','name','phone','email','website','gender','dob','customer_type_id','note','address'];
    }
}
