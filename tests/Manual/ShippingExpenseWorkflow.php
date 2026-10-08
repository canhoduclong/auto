<?php

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['view.compiled' => '/tmp/process-compiled']);
view()->share('errors', new Illuminate\Support\ViewErrorBag);
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProcessDefinition;
use App\Models\Role;
use App\Models\ShipperDispatchHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function check($condition, $label)
{
    if (! $condition) {
        throw new Exception($label);
    }echo 'PASS '.$label.PHP_EOL;
}
function rejected($action, $status, $label)
{
    try {
        $action();
        throw new Exception('Expected rejection '.$label);
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        check($e->getStatusCode() === $status, $label);
    }
}
foreach (['/process-management' => 'process-management.index', '/process-runs' => 'process-management.runs', '/shipping-expenses' => 'shipping-expenses.index', '/shipping-expenses/create' => 'shipping-expenses.create'] as $path => $name) {
    check(app('router')->getRoutes()->match(Illuminate\Http\Request::create($path))->getName() === $name, 'route resolves '.$path);
}
$processFileIds = [];
DB::beginTransaction();
try {
    $service = app(App\Services\ShippingExpenseService::class);
    $engine = $service->engine;
    $def = ProcessDefinition::where('code', 'shipping_expense')->firstOrFail();
    $coordinator = User::findOrFail($def->configuration['steps'][0]['user_id']);
    $accountant = User::findOrFail($def->configuration['steps'][1]['user_id']);
    $shipper = User::create(['name' => 'TEMP Shipper workflow', 'email' => 'workflow-'.uniqid().'@test.local', 'password' => bcrypt('test')]);
    $shipper->roles()->attach(Role::whereRaw('LOWER(name)=?', ['shipper'])->firstOrFail());
    $customer = Customer::firstOrFail();
    $order = Order::withoutEvents(fn () => Order::create(['customer_id' => $customer->id, 'shipper_id' => $shipper->id, 'code' => 'TEST-'.uniqid(), 'status' => 'delivered', 'shipping_fee' => 1000]));
    $dispatch = ShipperDispatchHistory::create(['schedule_date' => now()->toDateString(), 'version' => ((int) ShipperDispatchHistory::whereDate('schedule_date', now()->toDateString())->max('version')) + 1, 'route_plan' => [['shipper_id' => $shipper->id, 'routes' => [['orders' => [['order_id' => $order->id]]]]]], 'created_by' => $coordinator->id, 'published_at' => now()]);
    $data = ['items' => [['order_id' => $order->id, 'amount' => 2000, 'note' => 'Phí thực tế']], 'note' => 'Kiểm tra'];
    $claim = $service->submit($shipper, $data);
    check($claim->run->status === 'running' && $claim->run->current_step === 0, 'shipper submits to designated coordinator');
    check((float) $order->fresh()->shipping_fee === 1000.0, 'pending request does not change official fee');
    rejected(fn () => $service->submit($shipper, $data), 422, 'duplicate order request blocked');
    rejected(fn () => $service->act($claim, $shipper, 'approve', null), 403, 'shipper cannot approve');
    rejected(fn () => $service->act($claim, $accountant, 'approve', null), 403, 'accountant cannot skip coordinator');
    $service->act($claim, $coordinator, 'revise', 'Chi phí chưa hợp lý');
    check($claim->run->fresh()->status === 'revision', 'coordinator requests correction');
    $data['items'][0]['amount'] = 1800;
    $service->revise($claim, $shipper, $data);
    check($claim->run->fresh()->current_step === 0, 'coordinator correction returns to coordinator');
    $service->act($claim, $coordinator, 'approve', 'Đồng ý');
    check($claim->run->fresh()->current_step === 1, 'coordinator forwards to accountant');
    $service->act($claim, $accountant, 'revise', 'Bổ sung giải trình');
    $data['items'][0]['order_id'] = (string) $order->id;
    $data['items'][0]['amount'] = 1600;
    $service->revise($claim, $shipper, $data);
    check($claim->run->fresh()->current_step === 1, 'accountant correction returns directly to accountant');
    check(DB::table('notifications')->where('notifiable_id', $coordinator->id)->get()->contains(fn ($n) => str_contains(json_decode($n->data, true)['message'] ?? '', 'Shipper đã điều chỉnh')), 'coordinator notified on direct accountant resubmission');
    $service->act($claim, $accountant, 'approve', 'Chốt');
    check($claim->run->fresh()->status === 'confirmed' && (float) $order->fresh()->shipping_fee === 1600.0, 'accountant confirms and writes official fee once');
    rejected(fn () => $service->act($claim, $accountant, 'approve', null), 403, 'repeated confirm blocked');
    Auth::setUser($shipper);
    $transaction = $service->payment($claim, $shipper);
    check((float) $transaction->amount === 1600.0 && $order->fresh()->shipping_fee_transaction_id === $transaction->id, 'payment request uses confirmed amount and links order');
    rejected(fn () => $service->payment($claim, $shipper), 422, 'repeated payment blocked');
    // Definition edits must not alter a running request snapshot.
    $config = $def->configuration;
    $config['steps'][0]['name'] = 'Changed definition';
    $def->update(['version' => 2, 'configuration' => $config]);
    check($claim->run->fresh()->configuration['steps'][0]['name'] !== 'Changed definition', 'existing run keeps original configuration');
    // Rejection releases orders but preserves financial numbers and history.
    $order2 = Order::withoutEvents(fn () => Order::create(['customer_id' => $customer->id, 'shipper_id' => $shipper->id, 'code' => 'TEST-'.uniqid(), 'status' => 'delivered', 'shipping_fee' => 300]));
    $dispatch->update(['route_plan' => [['shipper_id' => $shipper->id, 'routes' => [['orders' => [['order_id' => $order2->id]]]]]]]);
    $claim2 = $service->submit($shipper, ['items' => [['order_id' => $order2->id, 'amount' => 500]]]);
    $service->act($claim2, $coordinator, 'reject', 'Không hợp lệ');
    check($claim2->run->fresh()->status === 'rejected' && ! DB::table('shipping_expense_order_locks')->where('order_id', $order2->id)->exists(), 'rejection releases order reservation');
    check((float) $order2->fresh()->shipping_fee === 300.0, 'rejection keeps original official fee');
    foreach ([$shipper, $coordinator, $accountant] as $user) {
        Auth::setUser($user);
        $request = Illuminate\Http\Request::create('/shipping-expenses');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);
        $html = app(App\Http\Controllers\ShippingExpenseController::class)->show($claim->fresh())->render();
        check(str_contains($html, 'Lịch sử xử lý'), 'claim detail renders for '.$user->name);
        $controller = app(App\Http\Controllers\ShippingExpenseController::class);
        $queue = $user->id === $coordinator->id ? 'coordination' : ($user->id === $accountant->id ? 'accounting' : 'mine');
        $request->query->set('queue', $queue);
        check(str_contains($controller->index($request)->render(), 'phí ship'), 'queue renders '.$queue);
        if ($user->id === $shipper->id) {
            check(str_contains($controller->create($request)->render(), 'Tạo yêu cầu xác nhận chi phí ship'), 'shipper entry form renders');
        }

    }
    $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->firstOrFail();
    Auth::setUser($admin);
    check(str_contains(app(App\Http\Controllers\ProcessManagementController::class)->index()->render(), 'công bố phiên bản mới'), 'admin process configuration renders');

    // Arbitrary user steps, configurable actions, protected attachments, entity links and inbox.
    $entityService = app(App\Services\EntityProcessService::class);
    $actions = ['approve' => ['label' => 'Chấp thuận', 'note_required' => true, 'document_required' => true], 'revise' => ['label' => 'Bổ sung hồ sơ', 'note_required' => true], 'reject' => ['label' => 'Không duyệt', 'note_required' => true]];
    $entityDef = ProcessDefinition::create(['code' => 'test-entity-'.uniqid(), 'name' => 'Test xét duyệt đơn', 'activity' => 'order_review', 'version' => 1, 'is_active' => true, 'configuration' => ['initiator_role' => 'Shipper', 'positions' => ['inbox', 'order_list', 'order_detail'], 'steps' => [['name' => 'Anh A kiểm tra', 'assignment_mode' => 'user', 'role' => null, 'user_id' => $accountant->id, 'actions' => $actions, 'revision_resume' => 'current'], ['name' => 'Anh B hoàn tất', 'assignment_mode' => 'user', 'role' => null, 'user_id' => $coordinator->id, 'actions' => ['approve' => ['label' => 'Hoàn tất', 'note_required' => false]], 'revision_resume' => 'first']]]]);
    Order::withoutEvents(fn () => $order2->update(['user_id' => $shipper->id]));
    $entityRun = $entityService->submit($entityDef, $order2->id, $shipper, 'Đề nghị xét duyệt hồ sơ đơn');
    check($entityRun->assignee_user_id === $accountant->id && $engine->canHandle($entityRun, $accountant), 'arbitrary user receives entity process without step role');
    rejected(fn () => $entityService->submit($entityDef, $order2->id, $shipper, 'Duplicate'), 422, 'duplicate active entity process blocked');
    rejected(fn () => $entityService->act($entityRun, $accountant, 'approve', 'Đồng ý'), 422, 'required file enforced before transition');
    rejected(fn () => $entityService->act($entityRun, $coordinator, 'approve', 'Đồng ý'), 403, 'future assignee cannot act early');
    $entityService->act($entityRun, $accountant, 'revise', 'Bổ sung hợp đồng');
    $entityService->revise($entityRun, $shipper, 'Đã bổ sung hợp đồng');
    $file = Illuminate\Http\UploadedFile::fake()->create('Hợp đồng kiểm tra.txt', 2, 'text/plain');
    $entityService->act($entityRun, $accountant, 'approve', 'Đồng ý', [$file]);
    $document = App\Models\ProcessDocument::whereHas('event', fn ($e) => $e->where('run_id', $entityRun->id))->firstOrFail();
    $processFileIds[] = $document->path;
    check($document->original_name === 'Hợp đồng kiểm tra.txt' && str_contains($document->path, 'Hợp đồng kiểm tra_'), 'attachment preserves original name and suffix');
    check($entityRun->fresh()->assignee_user_id === $coordinator->id, 'entity transitions to next arbitrary user');
    $inbox = app(App\Http\Controllers\ProcessInboxController::class);
    foreach ([$accountant, $coordinator, $shipper] as $user) {
        Auth::setUser($user);
        $req = Illuminate\Http\Request::create('/process-inbox');
        $req->setUserResolver(fn () => $user);
        $req->setLaravelSession(app('session.store'));
        app()->instance('request', $req);
        $pending = $inbox->index($req)->getData()['runs']->pluck('id');
        check($pending->contains($entityRun->id) === ($user->id === $coordinator->id), 'inbox shows current assigned user only '.$user->name);
    }
    Auth::setUser($coordinator);
    check(str_contains($inbox->show($entityRun->fresh())->render(), 'Hoàn tất'), 'entity detail shows configured final action');
    check($inbox->document($document) instanceof Symfony\Component\HttpFoundation\StreamedResponse, 'authorized reviewer can download attachment');
    $outsider = User::create(['name' => 'TEMP unrelated user', 'email' => 'outsider-'.uniqid().'@test.local', 'password' => bcrypt('test')]);
    Auth::setUser($outsider);
    rejected(fn () => $inbox->document($document), 403, 'unrelated user cannot download private attachment');
    Auth::setUser($coordinator);
    rejected(fn () => $entityService->act($entityRun, $coordinator, 'reject', 'Từ chối'), 422, 'disabled action cannot be forged');
    $entityService->act($entityRun, $coordinator, 'approve', null);
    check($entityRun->fresh()->status === 'confirmed', 'entity final action completes review');
    $linked = App\Models\ProcessSubject::with('run')->where('subject_type', 'order')->where('subject_id', $order2->id)->where('run_id', $entityRun->id)->get()->groupBy('subject_id');
    $html = view('processes.entity-actions', ['entity' => $order2, 'position' => 'order_detail', 'entityProcessSubjects' => $linked, 'entityProcessDefinitions' => collect()])->render();
    check(str_contains($html, route('process-inbox.show', $entityRun)), 'entity button links to same process');
    $html = view('processes.entity-actions', ['entity' => $order2, 'position' => 'product_list', 'entityProcessSubjects' => $linked, 'entityProcessDefinitions' => collect()])->render();
    check(! str_contains($html, route('process-inbox.show', $entityRun)), 'unconfigured position hides entity button');
    Auth::setUser($admin);
    check(str_contains(app(App\Http\Controllers\ProcessManagementController::class)->create()->render(), 'Cách giao người xử lý'), 'new process designer renders assignment modes');
    $product = App\Models\Product::first();
    if ($product) {
        $productConfig = $entityDef->configuration;
        $productConfig['initiator_role'] = 'admin';
        $productConfig['positions'] = ['inbox', 'product_detail'];
        $productConfig['steps'][0]['actions'] = ['approve' => ['label' => 'Xác nhận']];
        $productDef = ProcessDefinition::create(['code' => 'test-product-'.uniqid(), 'name' => 'Test sản phẩm', 'activity' => 'product_review', 'version' => 1, 'is_active' => true, 'configuration' => $productConfig]);
        $productRun = $entityService->submit($productDef, $product->id, $admin, 'Kiểm tra hồ sơ sản phẩm');
        $entityService->act($productRun, $accountant, 'approve', null);
        $entityService->act($productRun, $coordinator, 'approve', null);
        check($productRun->fresh()->status === 'confirmed', 'product entity review completes with audit');
    }

    // Configuration is persisted and validated by the real admin controller.
    Auth::setUser($admin);
    $manager = app(App\Http\Controllers\ProcessManagementController::class);
    $configData = ['code' => 'test-config-'.uniqid(), 'activity' => 'order_review', 'name' => 'Test cấu hình hoàn chỉnh', 'is_active' => 0, 'initiator_role' => 'Shipper', 'positions' => ['inbox', 'order_detail'], 'steps' => [['name' => 'User A', 'assignment_mode' => 'user', 'user_id' => $accountant->id, 'role' => null, 'revision_resume' => 'current', 'actions' => ['approve' => ['enabled' => 1, 'label' => 'Xác nhận'], 'revise' => ['enabled' => 1, 'label' => 'Bổ sung']]], ['name' => 'User B', 'assignment_mode' => 'user', 'user_id' => $coordinator->id, 'role' => null, 'revision_resume' => 'first', 'actions' => ['approve' => ['enabled' => 1, 'label' => 'Hoàn tất', 'document_required' => 1]]]]];
    $configRequest = Illuminate\Http\Request::create('/process-management', 'POST', $configData);
    $configRequest->setUserResolver(fn () => $admin);
    $configRequest->setLaravelSession(app('session.store'));
    app()->instance('request', $configRequest);
    $manager->store($configRequest);
    $savedDef = ProcessDefinition::where('code', $configData['code'])->firstOrFail();
    check($savedDef->configuration['steps'][0]['assignment_mode'] === 'user' && $savedDef->configuration['steps'][1]['actions']['approve']['document_required'], 'admin can persist arbitrary-user stages and action requirements');
    $invalidData = $configData;
    $invalidData['positions'] = ['product_detail'];
    $invalidData['version'] = $savedDef->version;
    $bad = Illuminate\Http\Request::create('/process-management/'.$savedDef->id, 'PUT', $invalidData);
    $bad->setUserResolver(fn () => $admin);
    rejected(fn () => $manager->save($bad, $savedDef), 422, 'activity rejects unsupported entity position');
    $validData = $configData;
    $validData['version'] = $savedDef->version;
    $validData['name'] = 'Phiên bản mới';
    $valid = Illuminate\Http\Request::create('/process-management/'.$savedDef->id, 'PUT', $validData);
    $valid->setUserResolver(fn () => $admin);
    $manager->save($valid, $savedDef);
    check($savedDef->fresh()->version === 2, 'publishing increments version');
    rejected(fn () => $manager->save($valid, $savedDef), 422, 'stale configuration save is blocked');
    $bad->setUserResolver(fn () => $shipper);
    rejected(fn () => $manager->save($bad, $savedDef), 403, 'non-admin cannot modify process');
    $roleConfig = $entityDef->configuration;
    $roleConfig['steps'] = [['name' => 'Điều phối bất kỳ', 'assignment_mode' => 'role', 'role' => 'manager_shipper', 'user_id' => null, 'actions' => ['approve' => ['label' => 'Hoàn tất']], 'revision_resume' => 'current']];
    $roleDef = ProcessDefinition::create(['code' => 'test-role-'.uniqid(), 'name' => 'Test nhóm vai trò', 'activity' => 'order_review', 'version' => 1, 'is_active' => true, 'configuration' => $roleConfig]);
    $roleRun = $entityService->submit($roleDef, $order2->id, $shipper, 'Duyệt theo nhóm');
    check($roleRun->assignee_mode === 'role' && $engine->canHandle($roleRun, $coordinator), 'role-only stage resolves group members');
    $entityService->act($roleRun, $coordinator, 'approve', null);
    check($roleRun->fresh()->status === 'confirmed', 'group reviewer can finish one-step process');
} finally {
    foreach ($processFileIds as $path) {
        Illuminate\Support\Facades\Storage::disk('local')->delete($path);
    }
    DB::rollBack();
}
