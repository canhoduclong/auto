<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $managerId = \App\Models\User::where('email', 'manager@example.com')->value('id');
        $staffId = \App\Models\User::where('email', 'staff@example.com')->value('id');
        DB::table('tasks')->insert([
            [
                'customer_id' => 1,
                'user_id' => $managerId,
                'title' => 'Gọi điện xác nhận đơn hàng',
                'description' => 'Nhắc nhở gọi điện cho khách hàng xác nhận đơn hàng ORD001',
                'due_date' => Carbon::now()->addDay(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_id' => 2,
                'user_id' => $staffId,
                'title' => 'Gửi email cảm ơn',
                'description' => 'Gửi email cảm ơn sau khi khách hàng hoàn tất đơn ORD002',
                'due_date' => Carbon::now()->addDays(2),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
