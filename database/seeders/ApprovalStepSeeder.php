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

       DB::table('approval_steps')->updateOrInsert(
            [
                'approval_flow_id' => $flowId,
                'step_order'       => 1,
            ],
            [
                'role_slug'  => 'sale_manager',
                'can_skip'   => false,
                'updated_at'=> now(),
                'created_at'=> now(),
            ]
        );

        DB::table('approval_steps')->updateOrInsert(
            [
                'approval_flow_id' => $flowId,
                'step_order'       => 2,
            ],
            [
                'role_slug'  => 'director',
                'can_skip'   => false,
                'updated_at'=> now(),
                'created_at'=> now(),
            ]
        );


    }
}
