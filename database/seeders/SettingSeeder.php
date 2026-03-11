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
            ['key' => 'slogan', 'value' => 'Chất lượng làm nên thương hiệu'],
            ['key' => 'brand_name', 'value' => 'Your Brand'],
            ['key' => 'address', 'value' => '177c CChiến Lược, Bình Trị Đông, TP. HCM'],
            ['key' => 'hotline', 'value' =>'0901 184 222'],
            ['key' => 'email', 'value' => 'huyhoanglongtnt@gmail.com'],
            ['key' => 'tax_number', 'value' => '0901 184 222'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
