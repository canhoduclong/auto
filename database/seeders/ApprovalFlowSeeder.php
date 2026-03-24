<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('approval_flows')->updateOrInsert(
            ['code' => 'order_default'],
            [
                'name' => 'Luồng duyệt đơn hàng mặc định',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
