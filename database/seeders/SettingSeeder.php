<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'logo', 'value' => ''],
            ['key' => 'slogan', 'value' => 'Auto Tây Bắc – Nơi chất lượng làm nên giá trị'],
            ['key' => 'brand_name', 'value' => 'Your Brand'],
            ['key' => 'address', 'value' => '307 Tỉnh Lộ 8, Xã Tân An Hội, Thành phố Hồ Chí Minh, Việt Nam'],
            ['key' => 'hotline', 'value' => '093 820 5979'],
            ['key' => 'email', 'value' => 'ntchungtas@gmail.com'],
            ['key' => 'tax_number', 'value' => '0319183943'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
