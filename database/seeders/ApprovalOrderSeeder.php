<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalOrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('approval_steps')->updateOrInsert(
            [
                'approval_flow_id' => 1,
                'step_order'       => 1,
            ],
            [
                'role_slug'  => 'sale_manager',
                'can_skip'   => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('approval_steps')->updateOrInsert(
            [
                'approval_flow_id' => 1,
                'step_order'       => 2,
            ],
            [
                'role_slug'  => 'director',
                'can_skip'   => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
