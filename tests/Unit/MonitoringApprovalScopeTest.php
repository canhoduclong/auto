<?php

namespace Tests\Unit;

use App\Http\Controllers\OrderApprovalController;
use App\Http\Controllers\PageController;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use ReflectionClass;
use Tests\TestCase;

class MonitoringApprovalScopeTest extends TestCase
{
    public function test_only_manager_roles_can_use_approve_all(): void
    {
        $controller = (new ReflectionClass(PageController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(PageController::class, 'canApproveAllFromMonitoring');

        $this->assertTrue($method->invoke($controller, $this->userWithRole('manager')));
        $this->assertTrue($method->invoke($controller, $this->userWithRole('manager_sale')));
        $this->assertFalse($method->invoke($controller, $this->userWithRole('leader')));
        $this->assertFalse($method->invoke($controller, $this->userWithRole('sale')));
    }

    public function test_only_leader_roles_can_approve_managed_sales_from_monitoring(): void
    {
        $controller = (new ReflectionClass(PageController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(PageController::class, 'canApproveManagedSalesFromMonitoring');

        $this->assertTrue($method->invoke($controller, $this->userWithRole('leader')));
        $this->assertTrue($method->invoke($controller, $this->userWithRole('leader_sale')));
        $this->assertTrue($method->invoke($controller, $this->userWithRole('sale_manager')));
        $this->assertFalse($method->invoke($controller, $this->userWithRole('manager')));
        $this->assertFalse($method->invoke($controller, $this->userWithRole('director')));
    }

    public function test_leader_can_only_approve_sale_in_the_same_team(): void
    {
        $controller = (new ReflectionClass(OrderApprovalController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(OrderApprovalController::class, 'userCanApproveOrder');

        $leader = $this->userWithRole('leader', 10);
        $sameTeamOrder = $this->orderFor($this->userWithRole('sale', 10));
        $otherTeamOrder = $this->orderFor($this->userWithRole('sale', 11));
        $leaderOrder = $this->orderFor($this->userWithRole('leader', 10));

        $this->assertTrue($method->invoke($controller, $leader, $sameTeamOrder));
        $this->assertFalse($method->invoke($controller, $leader, $otherTeamOrder));
        $this->assertFalse($method->invoke($controller, $leader, $leaderOrder));
    }

    public function test_manager_can_approve_orders_across_teams(): void
    {
        $controller = (new ReflectionClass(OrderApprovalController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(OrderApprovalController::class, 'userCanApproveOrder');

        $manager = $this->userWithRole('manager', 10);
        $order = $this->orderFor($this->userWithRole('sale', 99));

        $this->assertTrue($method->invoke($controller, $manager, $order));
    }

    public function test_monitoring_approval_scope_excludes_cancelled_orders(): void
    {
        $controller = (new ReflectionClass(PageController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(PageController::class, 'applyMonitoringApprovalScope');
        $query = Order::query();

        $result = $method->invoke($controller, $query, collect(['manager']));

        $this->assertInstanceOf(Builder::class, $result);
        $this->assertContains(Order::STATUS_CANCELLED, $query->getQuery()->getBindings());
    }

    private function userWithRole(string $roleName, ?int $teamId = null): User
    {
        $user = new User();
        $user->forceFill(['team_id' => $teamId]);
        $user->setRelation('roles', collect([new Role(['name' => $roleName])]));

        return $user;
    }

    private function orderFor(User $sale): Order
    {
        $order = new Order();
        $order->setRelation('user', $sale);

        return $order;
    }
}
