<?php

namespace Tests\Unit;

use App\Http\Middleware\RequireActiveRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PackageWorkspaceAuthorizationTest extends TestCase
{
    public function test_multi_role_leader_cannot_open_package_routes_until_switching_workspace(): void
    {
        $user = $this->userWithRoles(['leader', 'package']);

        $leaderRequest = $this->requestFor($user, 'leader');
        $this->expectException(HttpException::class);
        (new RequireActiveRole())->handle($leaderRequest, fn () => new Response('ok'), 'package');
    }

    public function test_package_active_role_can_open_package_routes(): void
    {
        $user = $this->userWithRoles(['leader', 'package']);
        $request = $this->requestFor($user, 'package');

        $response = (new RequireActiveRole())->handle($request, fn () => new Response('ok'), 'package');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_sales_header_filters_packing_notifications(): void
    {
        $base = dirname(__DIR__, 2);
        $helpers = file_get_contents($base.'/app/helpers.php');
        $controller = file_get_contents($base.'/app/Http/Controllers/MyDashboardController.php');
        $routes = file_get_contents($base.'/routes/web.php');

        $this->assertStringContainsString('WarehouseNewOrderApproved::class', $helpers);
        $this->assertStringContainsString('packingNotificationClasses', $controller);
        $this->assertStringContainsString('salesOrderNotificationUrl', $controller);
        $this->assertStringContainsString("'active.role:package'", $routes);
    }

    private function userWithRoles(array $roleNames): User
    {
        $user = new User();
        $user->setRelation('roles', collect(array_map(fn ($name) => new Role(['name' => $name]), $roleNames)));

        return $user;
    }

    private function requestFor(User $user, string $activeRole): Request
    {
        $request = Request::create('/package/orders', 'GET');
        $session = new Store('test', new ArraySessionHandler(120));
        $session->put('active_role', $activeRole);
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
