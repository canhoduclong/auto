<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\Mobile\ShipperApiController;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProcessDefinition;
use App\Models\Role;
use App\Models\ShipperDispatchHistory;
use App\Models\ShippingExpenseClaim;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function verifyMobile($condition, $label)
{
    if (! $condition) {
        throw new RuntimeException($label);
    }
    echo "PASS $label\n";
}
DB::beginTransaction();
try {
    $shipper = User::create(['name' => 'TEMP Mobile fee', 'email' => uniqid().'@test.local', 'password' => bcrypt('test')]);
    $shipper->roles()->attach(Role::whereRaw('LOWER(name)=?', ['shipper'])->firstOrFail());
    Auth::setUser($shipper);
    $customer = Customer::firstOrFail();
    $makeOrder = fn () => Order::withoutEvents(fn () => Order::create(['customer_id' => $customer->id, 'shipper_id' => $shipper->id, 'code' => 'TEST-MOBILE-'.uniqid(), 'status' => 'delivering', 'shipping_fee' => 1000]));
    $invoke = function ($order, $data) use ($shipper, $app) {
        $request = Request::create('/api/mobile/shipper/orders/'.$order->id.'/complete-delivery', 'POST', $data);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $shipper);
        $app->instance('request', $request);

        return $app->make(ShipperApiController::class)->completeDelivery($request, $order);
    };
    $order = $makeOrder();
    try {
        $invoke($order, ['shipping_expense_amount' => -1]);
        throw new RuntimeException('Invalid fee accepted');
    } catch (Illuminate\Validation\ValidationException $e) {
        verifyMobile($order->fresh()->status === 'delivering', 'invalid fee leaves order unchanged');
    }
    try {
        $invoke($order, ['shipping_expense_amount' => 50000]);
        throw new RuntimeException('Unassigned order accepted');
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        verifyMobile($e->getStatusCode() === 422 && $order->fresh()->status === 'delivering', 'failed expense submission rolls back delivery');
    }
    $definition = ProcessDefinition::where('activity', 'shipping_expense')->where('is_active', true)->firstOrFail();
    ShipperDispatchHistory::create(['schedule_date' => now()->toDateString(), 'version' => ((int) ShipperDispatchHistory::whereDate('schedule_date', now()->toDateString())->max('version')) + 1, 'route_plan' => [['shipper_id' => $shipper->id, 'routes' => [['orders' => [['order_id' => $order->id]]]]]], 'created_by' => $definition->configuration['steps'][0]['user_id'], 'published_at' => now()]);
    $response = $invoke($order, ['shipping_expense_amount' => 50000, 'shipping_expense_note' => 'Phí giao thực tế']);
    $data = $response->getData(true)['data'];
    $claim = ShippingExpenseClaim::findOrFail($data['shipping_expense_claim_id']);
    verifyMobile($order->fresh()->status === 'completed', 'delivery completed with expense request');
    verifyMobile((float) $claim->total === 50000.0 && $claim->items->first()->note === 'Phí giao thực tế', 'expense amount and explanation saved');
    verifyMobile($claim->run->status === 'running' && $claim->run->current_step === 0, 'expense goes to first configured reviewer');
    verifyMobile((float) $order->fresh()->shipping_fee === 1000.0, 'official fee remains unchanged before accountant confirmation');
    try {
        $invoke($order->fresh(), ['shipping_expense_amount' => 50000]);
        throw new RuntimeException('Duplicate completion accepted');
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        verifyMobile($e->getStatusCode() === 422 && ShippingExpenseClaim::where('shipper_id', $shipper->id)->count() === 1, 'retry cannot create duplicate request');
    }
    $withoutFee = $makeOrder();
    $invoke($withoutFee, []);
    verifyMobile($withoutFee->fresh()->status === 'completed' && ShippingExpenseClaim::where('shipper_id', $shipper->id)->count() === 1, 'old app can complete without expense field');

    $first = $makeOrder();
    $first->update(['status' => 'completed']);
    $second = $makeOrder();
    $second->update(['status' => 'completed']);
    ShipperDispatchHistory::create([
        'schedule_date' => now()->toDateString(),
        'version' => ((int) ShipperDispatchHistory::whereDate('schedule_date', now()->toDateString())->max('version')) + 1,
        'route_plan' => [['shipper_id' => $shipper->id, 'routes' => [['orders' => [['order_id' => $first->id], ['order_id' => $second->id]]]]]],
        'created_by' => $definition->configuration['steps'][0]['user_id'], 'published_at' => now(),
    ]);
    $request = Request::create('/api/mobile/shipper/delivery-schedules/shipping-expenses', 'GET', ['date' => now()->toDateString()]);
    $request->setUserResolver(fn () => $shipper);
    $app->instance('request', $request);
    $api = $app->make(ShipperApiController::class);
    $rows = $api->routeShippingExpenses($request)->getData(true)['data']['orders'];
    verifyMobile(collect($rows)->firstWhere('id', $first->id)['eligible'] === true, 'completed route exposes eligible orders');
    verifyMobile(collect($rows)->firstWhere('id', $order->id)['eligible'] === false, 'already submitted order cannot be selected again');
    $request->merge(['items' => [['order_id' => $first->id, 'amount' => 50000], ['order_id' => $second->id, 'amount' => 70000]]]);
    $validItems = $request->input('items');
    $request->merge(['items' => [['order_id' => 2147483647, 'amount' => 1000]]]);
    try {
        $api->submitRouteShippingExpenses($request);
        throw new RuntimeException('Foreign order accepted');
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        verifyMobile($e->getStatusCode() === 422, 'order outside selected route is rejected');
    }
    $request->merge(['items' => $validItems]);
    $first->update(['status' => 'delivering']);
    try {
        $api->submitRouteShippingExpenses($request);
        throw new RuntimeException('Unfinished route accepted');
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        verifyMobile($e->getStatusCode() === 422, 'unfinished route cannot submit fees');
    }
    $first->update(['status' => 'completed']);
    $result = $api->submitRouteShippingExpenses($request)->getData(true)['data'];
    $routeClaim = ShippingExpenseClaim::findOrFail($result['claim_id']);
    verifyMobile($routeClaim->items()->count() === 2 && (int) $routeClaim->total === 120000, 'route creates one request for multiple orders');
    verifyMobile($routeClaim->run->current_step === 0 && $routeClaim->run->status === 'running', 'route request reaches coordinator');

    $service = app(App\Services\ShippingExpenseService::class);
    $coordinator = User::findOrFail($definition->configuration['steps'][0]['user_id']);
    $accountant = User::findOrFail($definition->configuration['steps'][1]['user_id']);
    $service->act($routeClaim, $coordinator, 'revise', 'Cần bổ sung chi phí thực tế');
    $request->merge(['claim_id' => $routeClaim->id]);
    $detail = $api->routeShippingExpenses($request)->getData(true)['data'];
    verifyMobile((int) $detail['orders'][0]['amount'] === 50000 && count($detail['orders']) === 2, 'revision form returns all saved claim fees');
    verifyMobile($detail['revision_reason'] === 'Cần bổ sung chi phí thực tế', 'revision form returns reviewer explanation');
    $list = $api->deliveryScheduleList($request)->getData(true)['data'];
    $summary = collect($list)->firstWhere('date', now()->toDateString());
    verifyMobile(collect($summary['shipping_expense_requests'])->firstWhere('id', $routeClaim->id)['status'] === 'revision', 'route card exposes waiting revision state');
    $request->merge(['items' => [['order_id' => $first->id, 'amount' => 45000], ['order_id' => $second->id, 'amount' => 65000]]]);
    $api->submitRouteShippingExpenses($request);
    verifyMobile((int) $routeClaim->fresh()->total === 110000 && $routeClaim->run->fresh()->current_step === 0, 'mobile revision updates existing claim and returns to coordinator');
    $service->act($routeClaim, $coordinator, 'approve', null);
    $service->act($routeClaim, $accountant, 'revise', 'Bổ sung cho kế toán');
    $api->submitRouteShippingExpenses($request);
    verifyMobile($routeClaim->run->fresh()->current_step === 1, 'accountant correction resubmits directly to accountant');
} finally {
    DB::rollBack();
}
