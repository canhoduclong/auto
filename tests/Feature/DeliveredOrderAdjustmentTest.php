<?php
namespace Tests\Feature;

use App\Models\{Order, User};
use App\Http\Controllers\OrderAdjustmentController;
use App\Support\AccountingChargeAmounts;
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
    public function test_sales_management_roles_can_request_adjustment_only_for_their_own_orders(): void
    {
        foreach (['sale','leader','leader_sale','sale_manager','manager','manager_sale'] as $role) {
            $user=new User; $user->id=7;
            $user->setRelation('roles',collect([new \App\Models\Role(['name'=>$role])]));
            auth()->setUser($user);
            $controller=(new \ReflectionClass(OrderAdjustmentController::class))->newInstanceWithoutConstructor();
            $method=new \ReflectionMethod(OrderAdjustmentController::class,'authorizeCreate');
            foreach(['delivered','completed'] as $status) {
                $order=new Order(['status'=>$status,'user_id'=>7]);
                $method->invoke($controller,$order);
                self::assertTrue($order->canRequestAdjustmentBy($user));
                $order->user_id=8;
                try { $method->invoke($controller,$order); self::fail('Other owner allowed: '.$role); }
                catch(HttpException $e) { self::assertSame(403,$e->getStatusCode()); }
            }
        }
    }

    public function test_accounting_prices_approved_adjustment_then_delivery_then_warehouse_and_never_estimate(): void
    {
        $order = new Order;
        $order->forceFill(['status' => Order::STATUS_PACKED, 'total' => 4867500, 'subtotal_amount' => 4867500]);
        $item = new \App\Models\OrderItem(['quantity' => 30, 'total_weight' => 82.5, 'price' => 59000, 'total' => 4867500, 'is_priced_by_kg' => true]);
        $item->id = 1;
        $item->setRelation('product', null)->setRelation('variant', null);
        $order->setRelation('items', new \Illuminate\Database\Eloquent\Collection([$item]));
        $order->setRelation('additionalFees', collect());

        self::assertNull(AccountingChargeAmounts::forOrder($order));
        $item->packed_weight = 83.4;
        $item->total = 4920600;
        self::assertSame(4920600.0, AccountingChargeAmounts::forOrder($order)['total']);

        $order->status = Order::STATUS_DELIVERED;
        $item->actual_weight = 83.4;
        self::assertNull($item->accountingWeights($order)['delivered_weight']);
        self::assertSame(4920600.0, AccountingChargeAmounts::forOrder($order)['total']);
        $item->delivered_weight = 82;
        self::assertSame(4838000.0, AccountingChargeAmounts::forOrder($order)['total']);

        $item->accounting_adjusted_weight = 81.5;
        self::assertSame(4808500.0, AccountingChargeAmounts::forOrder($order)['total']);
        self::assertSame(['packed_weight' => 83.4, 'delivered_weight' => 82.0], $item->accountingWeights($order));
        self::assertSame(82.5, $item->display_total_value);

        $item->accounting_adjusted_weight = 0;
        self::assertSame(0.0, AccountingChargeAmounts::forOrder($order)['total']);
    }

    public function test_partial_warehouse_confirmation_changes_the_weight_that_accounting_must_review(): void
    {
        $item = new \App\Models\OrderAdjustmentItem([
            'original_quantity' => 30, 'adjusted_quantity' => 20, 'warehouse_received_quantity' => 5,
            'original_weight' => 83.4, 'adjusted_weight' => 55.6,
        ]);
        self::assertTrue($item->changesWeight());
        self::assertSame(55.6, $item->weightAfterWarehouse('confirmed_full'));
        self::assertSame(69.5, $item->weightAfterWarehouse('confirmed_partial'));
    }

}
