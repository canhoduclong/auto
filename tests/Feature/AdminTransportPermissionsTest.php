<?php
namespace Tests\Feature;

use App\Http\Controllers\AdminTransportController;
use App\Models\{User, OrderTransfer, WarehouseDispatchSlip};
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminTransportPermissionsTest extends TestCase
{
    private $app;
    protected function setUp(): void
    {
        $this->app=require dirname(__DIR__,2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    }
    protected function tearDown(): void
    {
        $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    public function test_non_admin_cannot_change_transport_driver_in_either_endpoint(): void
    {
        $user=new class extends User { public function isAdmin() { return false; } };
        $request=Request::create('/admin/order-transfers/1/reassign','POST');
        $request->setUserResolver(fn()=>$user);
        $controller=new AdminTransportController;
        foreach(['reassignGroup'=>new OrderTransfer,'reassignSlip'=>new WarehouseDispatchSlip] as $method=>$model) {
            try { $controller->$method($request,$model); self::fail('Non-admin accepted'); }
            catch(HttpException $e) { self::assertSame(403,$e->getStatusCode()); }
        }
    }
    public function test_admin_must_provide_driver_and_reason(): void
    {
        $user=new class extends User { public function isAdmin() { return true; } };
        $request=Request::create('/admin/warehouse-dispatch-slips/1/reassign','POST');
        $request->setUserResolver(fn()=>$user);
        try { (new AdminTransportController)->reassignSlip($request,new WarehouseDispatchSlip); self::fail('Missing data accepted'); }
        catch(\Illuminate\Validation\ValidationException $e) {
            self::assertArrayHasKey('shipper_id',$e->errors());
            self::assertArrayHasKey('reason',$e->errors());
        }
    }
}
