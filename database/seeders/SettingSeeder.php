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
            ['key' => 'slogan', 'value' => 'Your company slogan'],
            ['key' => 'brand_name', 'value' => 'Your Brand'],
            ['key' => 'address', 'value' => 'Your company address'],
            ['key' => 'hotline', 'value' => 'Your hotline number'],
            ['key' => 'email', 'value' => 'your-email@example.com'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
