<?php
namespace Tests\Feature;

use App\Models\{Order, User};
use App\Http\Controllers\OrderAdjustmentController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeliveredOrderAdjustmentTest extends TestCase
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
    public function test_delivered_and_completed_orders_do_not_require_prior_reconciliation(): void
    {
        foreach ([Order::STATUS_DELIVERED,Order::STATUS_COMPLETED] as $status) {
            $order=new Order(['status'=>$status]);
            $order->setRelation('accountingReconciliation',null);
            self::assertTrue($order->canRequestAdjustment());
        }
        foreach ([Order::STATUS_PACKED,Order::STATUS_DELIVERING,Order::STATUS_CANCELLED,Order::STATUS_RETURNED] as $status) {
            $order=new Order(['status'=>$status,'delivered_at'=>now()]);
            self::assertFalse($order->canRequestAdjustment());
        }
    }
    public function test_sale_can_request_only_their_own_delivered_order(): void
    {
        $user=new class extends User {
            public function hasRole($role) { return $role === 'sale'; }
        };
        $user->id=7; auth()->setUser($user);
        $controller=(new \ReflectionClass(OrderAdjustmentController::class))->newInstanceWithoutConstructor();
        $method=new \ReflectionMethod(OrderAdjustmentController::class,'authorizeCreate');
        $order=new Order(['status'=>Order::STATUS_DELIVERED,'user_id'=>7]);
        $method->invoke($controller,$order);
        self::assertTrue(true);
        $order->user_id=8;
        try { $method->invoke($controller,$order); self::fail('Other sale order allowed'); }
        catch(HttpException $e) { self::assertSame(403,$e->getStatusCode()); }
    }
}
