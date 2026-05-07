<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalStepSeeder extends Seeder
{
    public function run(): void
    {
        $flowId = DB::table('approval_flows')
            ->where('code', 'order_default')
            ->value('id');

        if (!$flowId) {
            return;
        }

        $steps = [
            ['step_order' => 1, 'role_slug' => 'leader'],
            ['step_order' => 2, 'role_slug' => 'manager'],
            ['step_order' => 3, 'role_slug' => 'warehouse'],
            ['step_order' => 4, 'role_slug' => 'Shipper'],
        ];

        foreach ($steps as $step) {
            DB::table('approval_steps')->updateOrInsert(
                [
                    'approval_flow_id' => $flowId,
                    'step_order'       => $step['step_order'],
                ],
                [
                    'role_slug'  => $step['role_slug'],
                    'can_skip'   => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
