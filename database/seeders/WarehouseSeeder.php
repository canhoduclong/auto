<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name'    => 'Kho Chiến Lược',
                'address' => '177C Chiến Lược, Bình Trị Đông, Hồ Chí Minh',
                'phone'   => '0900000001',
            ],
            [
                'name'    => 'Kho Long An',
                'address' => 'ấp Chánh, Xã Đức Lập, Tỉnh Tây Ninh',
                'phone'   => '0900000002',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['name' => $warehouse['name']],
                [
                    'address' => $warehouse['address'],
                    'phone' => $warehouse['phone'],
                ]
            );
        }
    }
}
