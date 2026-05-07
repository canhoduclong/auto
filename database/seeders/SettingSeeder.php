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
            ['key' => 'logo',                     'value' => null],
            ['key' => 'slogan',                   'value' => 'Chất lượng, Uy Tín làm nên Thương Hiệu'],
            ['key' => 'brand_name',               'value' => 'CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG TNT'],
            ['key' => 'address',                  'value' => '177c Chiến Lược, Bình Trị Đông, TP. HCM'],
            ['key' => 'hotline',                  'value' => '0901 184 222'],
            ['key' => 'email',                    'value' => 'thucphamhoanglongtnt@gmail.com'],
            ['key' => 'tax_number',               'value' => '0318512828'],
            ['key' => 'policy_page',              'value' => null],
            ['key' => 'banner',                   'value' => null],
            ['key' => 'footer_logo',              'value' => null],
            ['key' => 'priority_1_days',          'value' => '30'],
            ['key' => 'priority_2_days',          'value' => '21'],
            ['key' => 'priority_3_days',          'value' => '14'],
            ['key' => 'free_customer_days',       'value' => '20'],
            ['key' => 'customer_free_days',       'value' => '20'],
            ['key' => 'slider_1',                 'value' => null],
            ['key' => 'slider_2',                 'value' => null],
            ['key' => 'slider_3',                 'value' => null],
            ['key' => 'slider_4',                 'value' => null],
            ['key' => 'slider_5',                 'value' => null],
            ['key' => 'user_registration_enabled','value' => '0'],
            ['key' => 'stock_in_max_edits',       'value' => '3'],
            ['key' => 'price_logo',               'value' => null],
            ['key' => 'alert_logo',               'value' => null],
            ['key' => 'company_legal_name',       'value' => 'CÔNG TY CỔ PHẦN THỰC PHẨM HOÀNG LONG TNT'],
            ['key' => 'bank_account',             'value' => '1050717555'],
            ['key' => 'bank_name',                'value' => 'Ngân hàng TMCP Ngoại thương Việt Nam ( Vietcombank )'],
            ['key' => 'bank_branch',              'value' => 'Chi nhánh Hồ Chí Minh'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
