<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all product variants and warehouses
        $productVariants = ProductVariant::all();
        $warehouses = Warehouse::all();

        if ($warehouses->isEmpty()) {
            $this->command->info('No warehouses found. Please create warehouses first.');
            // Optionally, create a default warehouse
            $warehouses->push(Warehouse::factory()->create(['name' => 'Default Warehouse']));
        }

        if ($productVariants->isEmpty() || $warehouses->isEmpty()) {
            $this->command->info('No product variants or warehouses to seed inventory for.');
            return;
        }

        $inventories = [];
        foreach ($productVariants as $variant) {
            // Assign each variant to a random warehouse
            $warehouse = $warehouses->random();
            $inventories[] = [
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => rand(10, 200), // Random stock quantity
                'low_stock_threshold' => rand(5, 20),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert the data
        Inventory::insert($inventories);
    }
}