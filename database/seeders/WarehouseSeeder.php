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
                'name' => 'Kho Chiến Lược',
                'address' => 'Khu công nghiệp Tân Kim, Long An',
                'phone' => '0900000001',
            ],
            [
                'name' => 'Kho Long An',
                'address' => 'Bến Lức, Long An',
                'phone' => '0900000002',
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
