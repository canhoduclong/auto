<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAdminArea;
use App\Models\{Role, User};
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminAreaAccessTest extends TestCase
{
    private $app;
    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
    }
    protected function tearDown(): void
    {
        $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    private function user(string $role): User
    {
        $user = new User; $user->id = 1;
        $user->setRelation('roles', collect([new Role(['name' => $role])]));
        return $user;
    }
    public function test_all_registered_admin_routes_have_an_admin_only_guard(): void
    {
        $count = 0;
        foreach (app('router')->getRoutes() as $route) {
            if ($route->uri() !== 'admin' && !str_starts_with($route->uri(), 'admin/')) continue;
            $middleware = app('router')->gatherRouteMiddleware($route);
            self::assertTrue(in_array(EnsureAdminArea::class, $middleware, true) || in_array(\App\Http\Middleware\RoleMiddleware::class.':admin', $middleware, true), $route->uri());
            $count++;
        }
        self::assertGreaterThan(100, $count);
    }
    public function test_non_admin_roles_cannot_read_or_mutate_any_admin_path(): void
    {
        foreach (['sale', 'leader', 'manager', 'CEO', 'director', 'accounting', 'warehouse', 'shipper', 'manager_shipper'] as $role) {
            foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
                $request = Request::create('/admin/import-orders', $method);
                $request->setUserResolver(fn () => $this->user($role));
                try {
                    (new EnsureAdminArea)->handle($request, fn () => self::fail('Controller must not run'));
                    self::fail($role.' accepted for '.$method);
                } catch (HttpException $e) { self::assertSame(403, $e->getStatusCode()); }
            }
        }
    }
    public function test_admin_is_allowed_and_other_workspaces_are_unaffected(): void
    {
        foreach ([['/admin', 'admin'], ['/admin/events', 'admin'], ['/shipper/manage-assignments', 'manager_shipper'], ['/my-customers', 'sale']] as [$path, $role]) {
            $request = Request::create($path); $request->setUserResolver(fn () => $this->user($role));
            self::assertSame('allowed', (new EnsureAdminArea)->handle($request, fn () => response('allowed'))->getContent());
        }
    }
    public function test_guest_html_redirects_to_login_and_guest_json_is_unauthorized(): void
    {
        $request = Request::create('/admin/events');
        $request->setUserResolver(fn () => null);
        self::assertSame(route('login'), (new EnsureAdminArea)->handle($request, fn () => self::fail('Guest accepted'))->getTargetUrl());
        $request = Request::create('/admin/events', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => null);
        try {
            (new EnsureAdminArea)->handle($request, fn () => self::fail('Guest accepted'));
            self::fail('Guest JSON accepted');
        } catch (HttpException $e) { self::assertSame(401, $e->getStatusCode()); }
    }
    public function test_admin_layouts_are_denied_even_outside_admin_urls(): void
    {
        foreach (['layouts.admin', 'layouts.admin-accounting'] as $layout) {
            auth()->setUser($this->user('manager'));
            try {
                app('view')->callComposer(view($layout));
                self::fail('Non-admin rendered '.$layout);
            } catch (HttpException $e) { self::assertSame(403, $e->getStatusCode()); }
            auth()->setUser($this->user('admin'));
            app('view')->callComposer(view($layout));
            self::assertTrue(true);
        }
    }
}
