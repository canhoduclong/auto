<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('approval_flows')->insert([
            [
                'id'         => 1,
                'code'       => 'order_default',
                'name'       => 'Luồng duyệt đơn hàng mặc định',
                'is_active'  => true,
                'created_at'=> now(),
                'updated_at'=> now(),
            ],
        ]);
    }
}
