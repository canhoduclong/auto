<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileInventoryReservations extends Command
{
    protected $signature   = 'inventory:reconcile-reservations {--dry-run : Show mismatches without fixing}';
    protected $description = 'Sync reserved_quantity on inventories to match actual sum of inventory_reservations';

    public function handle(): int
    {
        $isDry = $this->option('dry-run');

        // Get actual sum per inventory from the reservations table
        $actualByInventory = InventoryReservation::query()
            ->selectRaw('inventory_id, COALESCE(SUM(quantity), 0) as actual_reserved')
            ->groupBy('inventory_id')
            ->pluck('actual_reserved', 'inventory_id')
            ->map(fn ($v) => (int) $v);

        // Find all inventories that have a non-zero reserved_quantity OR appear in reservations
        $inventoryIds = Inventory::where('reserved_quantity', '>', 0)
            ->pluck('id')
            ->merge($actualByInventory->keys())
            ->unique();

        $fixed = 0;
        $mismatches = 0;

        foreach ($inventoryIds as $id) {
            $inventory = Inventory::find($id);
            if (!$inventory) {
                continue;
            }

            $actual   = (int) ($actualByInventory[$id] ?? 0);
            $recorded = (int) $inventory->reserved_quantity;

            if ($actual !== $recorded) {
                $mismatches++;
                $this->line("  Inventory #{$id} (variant #{$inventory->product_variant_id}): recorded={$recorded}, actual={$actual}");

                if (!$isDry) {
                    $inventory->reserved_quantity = max(0, $actual);
                    $inventory->save();
                    $fixed++;
                }
            }
        }

        if ($isDry) {
            $this->info("Dry-run: {$mismatches} inventory mismatches found. Run without --dry-run to fix.");
        } else {
            $this->info("Reconciled {$fixed} inventories. {$mismatches} mismatches corrected.");
        }

        return self::SUCCESS;
    }
}
