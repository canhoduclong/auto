<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('customer_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('customer_types')->insert([
            ['name' => 'Vip', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gold', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Normal', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
