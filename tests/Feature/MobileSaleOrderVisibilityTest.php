<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\SaleApiController;
use App\Http\Controllers\MyDashboardController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class MobileSaleOrderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_sees_own_orders_only(): void
    {
        [$saleA, $saleB] = $this->salesInSeparateTeams();
        $ownOrder = $this->orderFor($saleA, 'SALE-OWN');
        $this->orderFor($saleB, 'SALE-OTHER');

        $payload = $this->ordersFor($saleA);

        $this->assertSame(1, $payload['meta']['total']);
        $this->assertSame($ownOrder->id, $payload['data'][0]['id']);
        $this->assertSame($saleA->name, $payload['data'][0]['sale_name']);
    }

    public function test_leader_sees_orders_in_their_team(): void
    {
        [$saleA, $saleB, $teamA] = $this->salesInSeparateTeams();
        $leaderRole = Role::create(['name' => 'leader']);
        $leader = User::factory()->create(['team_id' => $teamA->id]);
        $leader->roles()->attach($leaderRole);
        $teamOrder = $this->orderFor($saleA, 'TEAM-ORDER');
        $this->orderFor($saleB, 'OTHER-TEAM-ORDER');

        $payload = $this->ordersFor($leader);

        $this->assertSame(1, $payload['meta']['total']);
        $this->assertSame($teamOrder->id, $payload['data'][0]['id']);
    }

    public function test_admin_in_sale_layout_sees_all_orders(): void
    {
        [$saleA, $saleB] = $this->salesInSeparateTeams();
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $this->orderFor($saleA, 'ADMIN-VISIBLE-1');
        $this->orderFor($saleB, 'ADMIN-VISIBLE-2');

        $payload = $this->ordersFor($admin);

        $this->assertSame(2, $payload['meta']['total']);
    }

    public function test_sale_dashboard_passes_the_mobile_request_to_dashboard_stats(): void
    {
        [$sale] = $this->salesInSeparateTeams();
        $request = Request::create('/api/mobile/sale/dashboard', 'GET');
        $request->setUserResolver(fn () => $sale->load('roles'));

        $dashboard = Mockery::mock(MyDashboardController::class);
        $dashboard->shouldReceive('stats')
            ->once()
            ->with($request)
            ->andReturn(response()->json([
                'dashboardStats' => ['orders_this_month' => 0],
                'pendingWarehouseAdjustments' => [],
            ]));
        $this->app->instance(MyDashboardController::class, $dashboard);

        $payload = app(SaleApiController::class)->dashboard($request)->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame(0, $payload['data']['stats']['orders_this_month']);
        $this->assertSame([], $payload['data']['today_orders']);
        $this->assertSame([], $payload['data']['recent_orders']);
    }

    private function salesInSeparateTeams(): array
    {
        $saleRole = Role::create(['name' => 'sale']);
        $teamA = Team::create(['name' => 'Đội A']);
        $teamB = Team::create(['name' => 'Đội B']);
        $saleA = User::factory()->create(['team_id' => $teamA->id]);
        $saleB = User::factory()->create(['team_id' => $teamB->id]);
        $saleA->roles()->attach($saleRole);
        $saleB->roles()->attach($saleRole);

        return [$saleA, $saleB, $teamA, $teamB];
    }

    private function orderFor(User $sale, string $code): Order
    {
        $customer = Customer::create(['name' => 'Khách ' . $code, 'status' => 'active']);

        return Order::create([
            'customer_id' => $customer->id,
            'user_id' => $sale->id,
            'code' => $code,
            'status' => Order::STATUS_COMPLETED,
            'total' => 100000,
        ]);
    }

    private function ordersFor(User $user): array
    {
        $request = Request::create('/api/mobile/sale/orders', 'GET', ['per_page' => 50]);
        $request->setUserResolver(fn () => $user->load('roles'));

        return app(SaleApiController::class)->orders($request)->getData(true);
    }
}
