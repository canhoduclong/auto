<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('order_items')->insert([
            [
                'order_id' => 1,
                'product_id' => 1,
                'product_variant_id' => 1,
                'quantity' => 2,
                'price' => 500000,
                'total' => 1000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => 1,
                'product_id' => 2,
                'product_variant_id' => 2,
                'quantity' => 1,
                'price' => 500000,
                'total' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_id' => 2,
                'product_id' => 3,
                'product_variant_id' => 3,
                'quantity' => 5,
                'price' => 500000,
                'total' => 2500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
