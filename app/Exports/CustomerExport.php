<?php
namespace App\Exports;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Builder $customers) {}

    public function collection()
    {
        return (clone $this->customers)
            ->select('customers.id', 'customers.name', 'customers.phone', 'customers.email', 'customers.website',
                'customers.gender', 'customers.dob', 'customers.customer_type_id', 'customers.note', 'customers.size', 'customers.production',
                'customers.assigned_to', 'customers.current_owner_sale_id', 'customers.assigned_at', 'customers.is_employee', 'customers.customer_status')
            ->with(['currentOwner:id,name', 'assignedTo:id,name', 'lastOrder'])
            ->addSelect(['address' => DB::table('customer_addresses')
                ->select('note')->whereColumn('customer_id', 'customers.id')->where('is_default', true)->orderBy('id')->limit(1)])
            ->orderBy('customers.name')->orderBy('customers.id')->get();
    }
    public function map($customer): array
    {
        /** @var Customer $customer */
        $fields = $customer->attributesToArray();
        $fields['Sale'] = $customer->is_employee || $customer->isFree()
            ? null
            : ($customer->currentOwner ?? $customer->assignedTo)?->name;

        return array_map(fn ($field) => $fields[$field] ?? null, $this->headings());
    }

    public function headings(): array
    {
        return ['id', 'name', 'phone', 'email', 'website', 'gender', 'dob', 'customer_type_id', 'note', 'size', 'production', 'address', 'Sale'];
    }
}
