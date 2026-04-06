<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\District;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class DistrictWardSeeder extends Seeder
{
    public function run(): void
    {
        // Hà Nội
        $hanoi = Province::where('code', '01')->first();
        if ($hanoi) {
            $districts = [
                ['code' => '101', 'name' => 'Quận 1', 'type' => 'Quận', 'old_name' => 'Quận Ba Đình'],
                ['code' => '102', 'name' => 'Quận 2', 'type' => 'Quận', 'old_name' => 'Quận Hoàn Kiếm'],
                ['code' => '103', 'name' => 'Quận 3', 'type' => 'Quận', 'old_name' => 'Quận Hai Bà Trưng'],
                ['code' => '104', 'name' => 'Quận 4', 'type' => 'Quận', 'old_name' => 'Quận Đống Đa'],
                ['code' => '105', 'name' => 'Quận 5', 'type' => 'Quận', 'old_name' => 'Quận Thanh Xuân'],
            ];

            foreach ($districts as $districtData) {
                $districtData['province_id'] = $hanoi->id;
                District::updateOrCreate(
                    ['code' => $districtData['code']],
                    $districtData
                );
            }
        }

        // TP Hồ Chí Minh
        $hcm = Province::where('code', '50')->first();
        if ($hcm) {
            $districts = [
                ['code' => '501', 'name' => 'Quận 1', 'type' => 'Quận'],
                ['code' => '502', 'name' => 'Quận 2', 'type' => 'Quận'],
                ['code' => '503', 'name' => 'Quận 3', 'type' => 'Quận'],
                ['code' => '504', 'name' => 'Quận 4', 'type' => 'Quận'],
                ['code' => '505', 'name' => 'Quận 5', 'type' => 'Quận'],
            ];

            foreach ($districts as $districtData) {
                $districtData['province_id'] = $hcm->id;
                District::updateOrCreate(
                    ['code' => $districtData['code']],
                    $districtData
                );
            }
        }

        // Add wards for each district
        $baseWards = [
            ['code' => '1', 'name' => 'Phường 1', 'type' => 'Phường'],
            ['code' => '2', 'name' => 'Phường 2', 'type' => 'Phường'],
            ['code' => '3', 'name' => 'Phường 3', 'type' => 'Phường'],
        ];

        $districts = District::all();
        foreach ($districts as $district) {
            foreach ($baseWards as $wardData) {
                $wardData['district_id'] = $district->id;
                $wardData['code'] = $district->code . '-' . $wardData['code'];
                Ward::updateOrCreate(
                    ['code' => $wardData['code']],
                    $wardData
                );
            }
        }

        $this->command->info('✓ Đã tạo dữ liệu quận/huyện và phường/xã');
    }
}
