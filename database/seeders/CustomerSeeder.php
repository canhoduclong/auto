<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('customers')->truncate();
        DB::table('customer_addresses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $types = DB::table('customer_types')->pluck('id', 'name');
        $users = DB::table('users')->pluck('id');
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $type = $i <= 3 ? 'Vip' : ($i <= 6 ? 'Gold' : 'Normal');
            $data[] = [
                'name' => 'Khách hàng ' . $i,
                'email' => 'customer' . $i . '@example.com',
                'phone' => '09000000' . $i,
                'dob' => now()->subYears(20 + $i),
                'note' => 'Khách hàng mẫu',
                'gender' => $i % 2 == 0 ? 'male' : 'female',
                'status' => 'active',
                'customer_type_id' => $types[$type] ?? null,
                'assigned_to' => $users->random(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('customers')->insert($data);

        // Tạo địa chỉ mặc định cho mỗi customer
        $customers = DB::table('customers')->get();
        $addressData = [];
        foreach ($customers as $customer) {
            $addressData[] = [
                'customer_id' => $customer->id,
                'project_name' => 'Dự án A',
                'zone' => 'Zone 1',
                'block' => 'Block A',
                'floor' => rand(1, 20),
                'unit_number' => rand(101, 999),
                'house_number' => 'Số ' . rand(1, 200),
                'temporary_number' => null,
                'street' => 'Đường chính',
                'ward' => 'Phường ' . rand(1, 10),
                'district' => 'Quận ' . rand(1, 12),
                'city' => 'Hà Nội',
                'is_default' => 1,
                'note' => 'Địa chỉ mặc định',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($addressData)) {
            DB::table('customer_addresses')->insert($addressData);
        }
    }
}
