<?php

use App\Http\Controllers\MobileAccountingSessionController;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

require_once __DIR__.'/../../vendor/autoload.php';

final class MobileAccountingSessionTest extends TestCase
{
    protected function setUp(): void
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['cache.default' => 'array']);
    }

    private function request(string $role, string $page): Request
    {
        $user = new User(['name' => 'Test']);
        $user->id = 123;
        $user->setRelation('roles', new Collection([new Role(['name' => $role])]));
        $request = Request::create('/api/mobile/accounting/mobile-session', 'POST', ['page' => $page]);
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    public function test_supported_pages_create_scoped_one_time_tickets(): void
    {
        foreach (['reconciliation' => '/accounting/reconciliation', 'daily_sales' => '/accounting/daily-sales', 'adjustments' => '/accounting/order-adjustments'] as $page => $path) {
            $response = (new MobileAccountingSessionController)->create($this->request('accounting', $page));
            $ticket = basename($response->getData(true)['data']['url']);
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{64}$/', $ticket);
            $key = 'mobile-accounting-session:'.hash('sha256', $ticket);
            $this->assertSame(['user_id' => 123, 'path' => $path, 'token_id' => null], Cache::pull($key));
            $this->assertNull(Cache::pull($key));
            try {
                (new MobileAccountingSessionController)->consume(Request::create('/'), $ticket);
                $this->fail('Consumed ticket must be rejected');
            } catch (HttpException $exception) {
                $this->assertSame(410, $exception->getStatusCode());
            }
        }
    }

    public function test_sale_cannot_create_accounting_session(): void
    {
        try {
            (new MobileAccountingSessionController)->create($this->request('sale', 'reconciliation'));
            $this->fail('Sale access must be denied');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_arbitrary_redirect_target_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new MobileAccountingSessionController)->create($this->request('admin', 'https://example.com'));
    }
    public function test_revoked_embedded_session_is_invalidated(): void
    {
        $request = Request::create('/accounting/reconciliation');
        $session = new Illuminate\Session\Store('test', new Illuminate\Session\ArraySessionHandler(120));
        $session->start();
        $session->put('mobile_accounting', true);
        $request->setLaravelSession($session);
        $guard = Mockery::mock();
        $guard->shouldReceive('logout')->once();
        Illuminate\Support\Facades\Auth::shouldReceive('guard')->with('web')->andReturn($guard);
        try {
            (new App\Http\Middleware\ValidateMobileAccountingSession)->handle($request, fn () => response('private'));
            $this->fail('Missing mobile token must invalidate the session');
        } catch (HttpException $exception) {
            $this->assertSame(401, $exception->getStatusCode());
            $this->assertFalse($session->has('mobile_accounting'));
        } finally {
            Mockery::close();
        }
    }

    public function test_new_ticket_can_replace_expired_session(): void
    {
        $request = Request::create('/mobile/accounting/session/test');
        $route = new Illuminate\Routing\Route('GET', '/mobile/accounting/session/{ticket}', fn () => null);
        $route->name('mobile.accounting.session');
        $request->setRouteResolver(fn () => $route);
        $response = (new App\Http\Middleware\ValidateMobileAccountingSession)->handle($request, fn () => 'fresh ticket');
        $this->assertSame('fresh ticket', $response);
    }

}
