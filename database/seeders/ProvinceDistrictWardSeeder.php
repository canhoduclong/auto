<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\District;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class ProvinceDistrictWardSeeder extends Seeder
{
    public function run(): void
    {
        // Dữ liệu các tỉnh/thành phố
        $provincesData = [
            ['code' => '01', 'name' => 'Hà Nội', 'type' => 'Thành phố'],
            ['code' => '02', 'name' => 'Hà Giang', 'type' => 'Tỉnh'],
            ['code' => '04', 'name' => 'Cao Bằng', 'type' => 'Tỉnh'],
            ['code' => '06', 'name' => 'Bắc Kạn', 'type' => 'Tỉnh'],
            ['code' => '08', 'name' => 'Quảng Ninh', 'type' => 'Tỉnh'],
            ['code' => '10', 'name' => 'Bắc Giang', 'type' => 'Tỉnh'],
            ['code' => '11', 'name' => 'Phú Thọ', 'type' => 'Tỉnh'],
            ['code' => '12', 'name' => 'Thái Nguyên', 'type' => 'Tỉnh'],
            ['code' => '14', 'name' => 'Yên Bái', 'type' => 'Tỉnh'],
            ['code' => '15', 'name' => 'Điện Biên', 'type' => 'Tỉnh'],
            ['code' => '17', 'name' => 'Lai Châu', 'type' => 'Tỉnh'],
            ['code' => '19', 'name' => 'Tuyên Quang', 'type' => 'Tỉnh'],
            ['code' => '20', 'name' => 'Sơn La', 'type' => 'Tỉnh'],
            ['code' => '21', 'name' => 'Hòa Bình', 'type' => 'Tỉnh'],
            ['code' => '22', 'name' => 'Hà Tây', 'type' => 'Tỉnh'],
            ['code' => '24', 'name' => 'Hưng Yên', 'type' => 'Tỉnh'],
            ['code' => '25', 'name' => 'Thái Bình', 'type' => 'Tỉnh'],
            ['code' => '26', 'name' => 'Hà Nam', 'type' => 'Tỉnh'],
            ['code' => '27', 'name' => 'Nam Định', 'type' => 'Tỉnh'],
            ['code' => '30', 'name' => 'Vĩnh Phúc', 'type' => 'Tỉnh'],
            ['code' => '31', 'name' => 'Nghệ An', 'type' => 'Tỉnh'],
            ['code' => '32', 'name' => 'Hà Tĩnh', 'type' => 'Tỉnh'],
            ['code' => '35', 'name' => 'Quảng Bình', 'type' => 'Tỉnh'],
            ['code' => '36', 'name' => 'Quảng Trị', 'type' => 'Tỉnh'],
            ['code' => '37', 'name' => 'Thừa Thiên Huế', 'type' => 'Tỉnh'],
            ['code' => '38', 'name' => 'Đà Nẵng', 'type' => 'Thành phố'],
            ['code' => '40', 'name' => 'Quảng Nam', 'type' => 'Tỉnh'],
            ['code' => '42', 'name' => 'Quảng Ngãi', 'type' => 'Tỉnh'],
            ['code' => '44', 'name' => 'Bình Định', 'type' => 'Tỉnh'],
            ['code' => '45', 'name' => 'Phú Yên', 'type' => 'Tỉnh'],
            ['code' => '46', 'name' => 'Khánh Hòa', 'type' => 'Tỉnh'],
            ['code' => '48', 'name' => 'Ninh Thuận', 'type' => 'Tỉnh'],
            ['code' => '49', 'name' => 'Bình Thuận', 'type' => 'Tỉnh'],
            ['code' => '50', 'name' => 'TP Hồ Chí Minh', 'type' => 'Thành phố'],
            ['code' => '52', 'name' => 'Đồng Nai', 'type' => 'Tỉnh'],
            ['code' => '54', 'name' => 'Bà Rịa Vũng Tàu', 'type' => 'Tỉnh'],
            ['code' => '56', 'name' => 'Long An', 'type' => 'Tỉnh'],
            ['code' => '58', 'name' => 'Đồng Tháp', 'type' => 'Tỉnh'],
            ['code' => '60', 'name' => 'An Giang', 'type' => 'Tỉnh'],
            ['code' => '61', 'name' => 'Tiền Giang', 'type' => 'Tỉnh'],
            ['code' => '62', 'name' => 'Bến Tre', 'type' => 'Tỉnh'],
            ['code' => '63', 'name' => 'Trà Vinh', 'type' => 'Tỉnh'],
            ['code' => '64', 'name' => 'Vinh Long', 'type' => 'Tỉnh'],
            ['code' => '66', 'name' => 'Cần Thơ', 'type' => 'Thành phố'],
            ['code' => '67', 'name' => 'Kiên Giang', 'type' => 'Tỉnh'],
            ['code' => '68', 'name' => 'Hậu Giang', 'type' => 'Tỉnh'],
            ['code' => '70', 'name' => 'Sóc Trăng', 'type' => 'Tỉnh'],
            ['code' => '72', 'name' => 'Bạc Liêu', 'type' => 'Tỉnh'],
            ['code' => '74', 'name' => 'Cà Mau', 'type' => 'Tỉnh'],
        ];

        foreach ($provincesData as $provinceData) {
            Province::updateOrCreate(
                ['code' => $provinceData['code']],
                $provinceData
            );
        }

        $this->command->info('✓ Đã tạo dữ liệu các tỉnh/thành phố');
    }
}
