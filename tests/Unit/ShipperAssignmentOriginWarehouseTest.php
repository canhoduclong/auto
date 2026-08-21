<?php

namespace Tests\Unit;

use App\Http\Controllers\ShipperDashboardController;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class ShipperAssignmentOriginWarehouseTest extends TestCase
{
    public function test_it_resolves_the_warehouse_from_the_account_that_accepted_the_order(): void
    {
        $warehouse = new Warehouse(['name' => 'Kho Chiến Lược']);
        $warehouse->id = 8;

        $user = new User();
        $user->setRelation('warehouse', $warehouse);

        $history = new OrderHistory(['action' => 'start_packing']);
        $history->id = 21;
        $history->created_at = now();
        $history->setRelation('user', $user);

        $order = new Order();
        $order->setRelation('warehouse', null);
        $order->setRelation('histories', new Collection([$history]));
        $order->setRelation('warehouseTransfers', new Collection());

        $method = new ReflectionMethod(ShipperDashboardController::class, 'resolveAssignmentOriginWarehouse');
        $method->setAccessible(true);
        $resolved = $method->invoke(app(ShipperDashboardController::class), $order);

        $this->assertSame($warehouse, $resolved);
    }

    public function test_direct_order_warehouse_has_priority(): void
    {
        $warehouse = new Warehouse(['name' => 'Kho đang giữ đơn']);
        $warehouse->id = 9;

        $order = new Order();
        $order->setRelation('warehouse', $warehouse);
        $order->setRelation('histories', new Collection());
        $order->setRelation('warehouseTransfers', new Collection());

        $method = new ReflectionMethod(ShipperDashboardController::class, 'resolveAssignmentOriginWarehouse');
        $method->setAccessible(true);

        $this->assertSame($warehouse, $method->invoke(app(ShipperDashboardController::class), $order));
    }
}
