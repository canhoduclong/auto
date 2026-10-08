<?php

namespace Database\Seeders;

use App\Models\ProcessDefinition;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShippingExpenseProcessSeeder extends Seeder
{
    public function run(): void
    {
        if (ProcessDefinition::where('code', 'shipping_expense')->exists()) {
            return;
        }
        $coordinator = User::where('name', 'Biet Nguyen')->whereHas('roles', fn ($q) => $q->where('name', 'manager_shipper'))->first();
        $accountant = User::where('name', 'Chi - Kế toán')->whereHas('roles', fn ($q) => $q->whereIn('name', ['accountant', 'accounting', 'account']))->first();
        ProcessDefinition::create(['code' => 'shipping_expense', 'name' => 'Shipper ghi nhận → Điều phối xác nhận → Kế toán chốt chi phí', 'activity' => 'shipping_expense', 'version' => 1, 'is_active' => (bool) ($coordinator && $accountant), 'configuration' => ['initiator_role' => 'shipper', 'steps' => [['name' => 'Điều phối xác nhận', 'role' => 'manager_shipper', 'user_id' => $coordinator?->id], ['name' => 'Kế toán xác nhận', 'role' => $accountant?->roles->first(fn ($r) => in_array($r->name, ['accountant', 'accounting', 'account']))?->name ?? 'accountant', 'user_id' => $accountant?->id]]]]);
    }
}
