<?php

namespace Tests\Unit;

use App\Http\Controllers\WarehouseStocktakeController;
use App\Models\Inventory;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class WarehouseStocktakeGuardTest extends TestCase
{
    public function test_it_accepts_inventory_when_system_quantity_has_not_changed(): void
    {
        $this->invokeGuard(
            collect([5 => ['expected_quantity' => 12.5, 'expected_weight_kg' => 30.2, 'counted_quantity' => 11.8, 'counted_weight_kg' => 28.7]]),
            collect([5 => $this->inventory(5, 12.5, 30.2)])
        );

        $this->addToAssertionCount(1);
    }

    public function test_it_rejects_inventory_that_changed_during_counting(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeGuard(
            collect([5 => ['expected_quantity' => 12.5, 'expected_weight_kg' => 30.2, 'counted_quantity' => 11.8, 'counted_weight_kg' => 28.7]]),
            collect([5 => $this->inventory(5, 13.0, 30.2)])
        );
    }

    public function test_it_rejects_inventory_weight_that_changed_during_counting(): void
    {
        $this->expectException(ValidationException::class);

        $this->invokeGuard(
            collect([5 => ['expected_quantity' => 12, 'expected_weight_kg' => 30.2, 'counted_quantity' => 12, 'counted_weight_kg' => 29.5]]),
            collect([5 => $this->inventory(5, 12, 29.9)])
        );
    }

    private function inventory(int $id, float $quantity, float $weightKg): Inventory
    {
        $inventory = new Inventory;
        $inventory->setRawAttributes(['id' => $id, 'quantity' => $quantity, 'weight_kg' => $weightKg]);

        return $inventory;
    }

    private function invokeGuard(Collection $rows, Collection $inventories): void
    {
        $method = new ReflectionMethod(WarehouseStocktakeController::class, 'guardUnchangedInventory');
        $method->invoke(new WarehouseStocktakeController, $rows, $inventories);
    }
}
