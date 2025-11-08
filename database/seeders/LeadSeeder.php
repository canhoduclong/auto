<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $managerId = \App\Models\User::where('email', 'manager@example.com')->value('id');
        $staffId = \App\Models\User::where('email', 'staff@example.com')->value('id');
        DB::table('leads')->insert([
            [
                'customer_id' => 1,
                'name' => 'Cơ hội bán hàng A',
                'status' => 'new',
                'expected_value' => 10000000,
                'expected_close_date' => Carbon::now()->addDays(10),
                'user_id' => $managerId,
                'note' => 'Khách hàng tiềm năng',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_id' => 2,
                'name' => 'Cơ hội bán hàng B',
                'status' => 'contacted',
                'expected_value' => 5000000,
                'expected_close_date' => Carbon::now()->addDays(5),
                'user_id' => $staffId,
                'note' => 'Đã liên hệ, chờ phản hồi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
