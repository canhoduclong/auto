<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $orderId = 1; // đảm bảo order này tồn tại

        $steps = DB::table('approval_steps')
            ->where('approval_flow_id', 1)
            ->orderBy('step_order')
            ->get();

        foreach ($steps as $step) {
            DB::table('order_approvals')->insert([
                'order_id'          => $orderId,
                'approval_step_id'  => $step->id,
                'status'            => 'pending',
                'approved_by'       => null,
                'approved_at'       => null,
                'note'              => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
