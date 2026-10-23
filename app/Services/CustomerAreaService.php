<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomerAreaService
{
    public const UNKNOWN = '__unassigned__';
    private const AREA = "COALESCE(NULLIF(TRIM(p.name), ''), NULLIF(TRIM(ca.city), ''), '__unassigned__')";
    private const WARD = "COALESCE(NULLIF(TRIM(w.name), ''), NULLIF(TRIM(ca.ward), ''), '__unassigned__')";

    private function addresses(Builder $customers)
    {
        return DB::query()->fromSub((clone $customers)->reorder()->select('customers.id'), 'sc')
            ->leftJoin('customer_addresses as ca', function ($join) {
                $join->on('ca.customer_id', '=', 'sc.id')->whereRaw('ca.id = (SELECT ca2.id FROM customer_addresses ca2 WHERE ca2.customer_id = sc.id ORDER BY ca2.is_default DESC, ca2.id ASC LIMIT 1)');
            })
            ->leftJoin('provinces as p', 'p.id', '=', 'ca.province_id')
            ->leftJoin('wards as w', 'w.id', '=', 'ca.ward_id');
    }

    public function groups(Builder $customers)
    {
        return $this->addresses($customers)
            ->selectRaw(self::AREA.' as area, '.self::WARD.' as ward, COUNT(*) as customer_count')
            ->groupByRaw(self::AREA.', '.self::WARD)
            ->orderBy('area')->orderBy('ward')->get()->groupBy('area');
    }

    public function apply(Builder $customers, ?string $area, ?string $ward): void
    {
        if ($area === null || $area === '') return;
        $matching = $this->addresses($customers)->whereRaw(self::AREA.' = ?', [$area]);
        if ($ward !== null && $ward !== '') $matching->whereRaw(self::WARD.' = ?', [$ward]);
        $customers->whereIn('customers.id', $matching->select('sc.id'));
    }
}
