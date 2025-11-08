<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $managerId = \App\Models\User::where('email', 'manager@example.com')->value('id');
        $staffId = \App\Models\User::where('email', 'staff@example.com')->value('id');
        DB::table('contacts')->insert([
            [
                'customer_id' => 1,
                'type' => 'call',
                'subject' => 'Gọi điện tư vấn',
                'note' => 'Khách hàng quan tâm sản phẩm A',
                'contacted_at' => Carbon::now()->subDays(2),
                'user_id' => $managerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_id' => 2,
                'type' => 'email',
                'subject' => 'Gửi báo giá',
                'note' => 'Đã gửi báo giá qua email',
                'contacted_at' => Carbon::now()->subDay(),
                'user_id' => $staffId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
