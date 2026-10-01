<?php
namespace Tests\Feature;
use App\Http\Controllers\ShipperDashboardController;
use App\Models\ShipperDispatchHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class RouteRecallTest extends TestCase
{
    private $app;
    protected function setUp(): void
    {
        $this->app=require dirname(__DIR__,2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array']); DB::purge('sqlite');
        foreach(['shipper_dispatch_histories'=>'schedule_date,version,route_plan,revoked_at,revoked_by','settings'=>'key,value','warehouse_transfers'=>'order_id,status','orders'=>'status,shipper_id,delivered_at','order_histories'=>'order_id,action,user_id,role,status_before,status_after,note,source'] as $name=>$columns){
            Schema::create($name,function(Blueprint $t)use($columns){$t->id();foreach(explode(',',$columns) as $c)$t->string($c)->nullable();$t->timestamps();});
        }
        DB::table('orders')->insert(['id'=>1,'status'=>'packed_waiting_pickup']);
        foreach([1,2] as $version)ShipperDispatchHistory::create(['schedule_date'=>'2026-10-01','version'=>$version,'route_plan'=>[['shipper_id'=>1,'routes'=>[['orders'=>[['order_id'=>1]]]]]]]);
    }
    protected function tearDown(): void {DB::disconnect('sqlite');$this->app->flush();restore_error_handler();restore_exception_handler();parent::tearDown();}
    public function test_recall_disables_old_versions_and_allows_republishing_without_changing_order_status(): void
    {
        $controller=new class extends ShipperDashboardController {protected function authorizeManagerShipper(): void {}};
        $response=$controller->revokeAssignmentHistory(ShipperDispatchHistory::findOrFail(2));
        self::assertSame(302,$response->getStatusCode());
        self::assertSame(0,ShipperDispatchHistory::whereNull('revoked_at')->count());
        self::assertSame('packed_waiting_pickup',DB::table('orders')->value('status'));
        self::assertSame('schedule_revoked',DB::table('order_histories')->value('action'));
        $check=new \ReflectionMethod($controller,'routePlanHasChanges');
        self::assertTrue($check->invoke($controller,'2026-10-01',ShipperDispatchHistory::find(2)->route_plan));
    }
    public function test_mixed_route_preserves_delivering_and_completed_stops(): void
    {
        DB::table('orders')->insert([['id'=>2,'status'=>'delivering'],['id'=>3,'status'=>'completed']]);
        $dispatch=ShipperDispatchHistory::findOrFail(2);
        $dispatch->update(['route_plan'=>[['shipper_id'=>1,'routes'=>[['orders'=>[['order_id'=>1],['order_id'=>2],['order_id'=>3]]]]]]]);
        $controller=new class extends ShipperDashboardController {protected function authorizeManagerShipper(): void {}};
        $controller->revokeAssignmentHistory($dispatch);
        self::assertSame([1], DB::table('order_histories')->pluck('order_id')->map(fn($id)=>(int)$id)->all());
        $active=ShipperDispatchHistory::whereNull('revoked_at')->firstOrFail();
        self::assertSame([2,3], array_column($active->route_plan[0]['routes'][0]['orders'], 'order_id'));
        self::assertSame('delivering', DB::table('orders')->where('id',2)->value('status'));
        self::assertSame('completed', DB::table('orders')->where('id',3)->value('status'));
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->revokeAssignmentHistory($active);
    }

    public function test_delivered_route_does_not_wait_for_confirmation_after_new_schedule_event(): void
    {
        DB::table('order_histories')->insert(['order_id'=>1,'action'=>'schedule_created']);
        $order=\App\Models\Order::findOrFail(1);
        $order->status='delivered';
        $order->setRelation('items', collect());
        $order->setRelation('histories', collect());
        $controller=app(ShipperDashboardController::class);
        $method=new \ReflectionMethod($controller, 'deliveryRoutesForShipper');
        $route=$method->invoke($controller,1,'2026-10-01',collect([$order]))[0];
        self::assertSame('confirmed',$route['status']);
        self::assertSame('completed',$route['completion_status']);
        $order->status='packed_waiting_pickup';
        $order->setRelation('histories', collect([new \App\Models\OrderHistory(['action'=>'schedule_created'])]));
        $route=$method->invoke($controller,1,'2026-10-01',collect([$order]))[0];
        self::assertSame('waiting',$route['status']);
        self::assertSame(0,$route['completed_orders']);
    }

    public function test_configured_shipper_can_be_completed_and_repeat_does_not_duplicate_history(): void
    {
        \App\Models\Order::flushEventListeners();
        $shipper=new class extends \App\Models\User {public function hasRole($role) {return true;}};
        $shipper->id=1;
        \App\Models\Setting::set('shipper_auto_complete_1','1');
        DB::table('orders')->where('id',1)->update(['shipper_id'=>1]);
        $controller=new class extends ShipperDashboardController {protected function authorizeManagerShipper(): void {}};
        $controller->autoCompleteShipperRoute(ShipperDispatchHistory::findOrFail(2), $shipper);
        self::assertSame('delivered',DB::table('orders')->value('status'));
        self::assertSame(2,DB::table('order_histories')->count());
        $controller->autoCompleteShipperRoute(ShipperDispatchHistory::findOrFail(2), $shipper);
        self::assertSame(2,DB::table('order_histories')->count());
        \App\Models\Setting::set('shipper_auto_complete_1','0');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->autoCompleteShipperRoute(ShipperDispatchHistory::findOrFail(2), $shipper);
    }

}
