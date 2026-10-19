<?php

namespace Tests\Feature;

use App\Http\Controllers\DepartmentFinanceRequestController;
use App\Models\{Transaction, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmployeeFinanceRequestTest extends TestCase
{
    private $app;
    private $user;

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['cache.default' => 'array', 'session.driver' => 'array']);
        foreach (['transactions', 'approval_orders'] as $table) {
            $ddl = (array) DB::selectOne("SHOW CREATE TABLE `$table`");
            $sql = array_values($ddl)[1];
            $sql = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $sql);
            $sql = preg_replace('/^  CONSTRAINT .*\n/m', '', $sql);
            $sql = preg_replace('/,\n\)/', "\n)", $sql);
            DB::statement($sql);
        }
        $this->user = new class extends User {
            public function hasRole($roles, ?string $guard = null): bool { return true; }
            public function managedAccounts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { return $this->belongsToMany(\App\Models\Account::class, 'account_user', 'user_id', 'account_id')->withPivot('is_default')->withTimestamps(); }
        };
        $this->user->id = 900001;
        $this->user->name = 'Nhân viên kiểm thử';
        $this->user->setRelation('roles', collect());
        Auth::setUser($this->user);
        $this->app['view']->share('errors', new \Illuminate\Support\ViewErrorBag);
    }

    protected function tearDown(): void
    {
        foreach (['transactions', 'approval_orders'] as $table) DB::statement("DROP TEMPORARY TABLE IF EXISTS `$table`");
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    private function fixture(array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'submitted_by' => $this->user->id, 'request_source' => 'warehouse',
            'status' => Transaction::STATUS_PENDING_APPROVAL, 'type' => 'extra_expense',
            'method' => 'cash', 'amount' => 1000, 'note' => 'Ban đầu', 'request_title' => 'Phiếu ban đầu',
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'request_attachments' => [['name' => 'Giữ nguyên.pdf', 'path' => 'test/keep.pdf']],
        ], $overrides));
    }

    private function request(string $path, string $method = 'GET', array $data = []): Request
    {
        $request = Request::create($path, $method, $data);
        $route = $this->app['router']->getRoutes()->match($request);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $this->user);
        $this->app->instance('request', $request);
        return $request;
    }

    private function update(Transaction $transaction)
    {
        $request = $this->request('/warehouse/requests/'.$transaction->id, 'PUT', [
            'request_document_title' => '__custom__', 'request_document_title_custom' => 'Phiếu thử',
            'request_form_type' => Transaction::REQUEST_FORM_CASH, 'flow_direction' => 'out',
            'request_title' => 'Phiếu đã sửa', 'method' => 'cash', 'note' => 'Sửa trước duyệt',
            'items' => [['content' => 'Chi phí', 'quantity' => 2, 'unit_price' => 1500]], 'request_vat' => 100,
            'submitted_by' => 123, 'status' => Transaction::STATUS_APPROVED,
        ]);
        return (new DepartmentFinanceRequestController)->employeeUpdate($request, $transaction);
    }

    public function test_owner_updates_amount_and_preserves_workflow_and_attachments(): void
    {
        $transaction = $this->fixture();
        DB::table('approval_orders')->insert(['transaction_id' => $transaction->id, 'approval_step_id' => 1, 'status' => 'pending']);
        $step = DB::table('approval_orders')->value('id');
        $this->update($transaction);
        $transaction->refresh();
        self::assertEquals(3100, $transaction->amount);
        self::assertEquals(900001, $transaction->submitted_by);
        self::assertSame(Transaction::STATUS_PENDING_APPROVAL, $transaction->status);
        self::assertSame('test/keep.pdf', $transaction->request_attachments[0]['path']);
        self::assertEquals($step, DB::table('approval_orders')->value('id'));
        self::assertSame('Phiếu đã sửa', $transaction->request_title);
    }

    public function test_cannot_edit_another_employees_request(): void
    {
        $transaction = $this->fixture(['submitted_by' => 900002]);
        $this->expectException(HttpException::class);
        $this->update($transaction);
    }

    public function test_stale_form_cannot_overwrite_processed_approval(): void
    {
        $transaction = $this->fixture();
        $transaction->load('approvalSteps');
        DB::table('approval_orders')->insert(['transaction_id' => $transaction->id, 'approval_step_id' => 1, 'status' => 'approved', 'approved_by' => 900003]);
        try { $this->update($transaction); self::fail('Processed approval was overwritten'); }
        catch (HttpException $exception) { self::assertSame(403, $exception->getStatusCode()); }
        self::assertEquals(1000, $transaction->fresh()->amount);
        self::assertSame('approved', DB::table('approval_orders')->value('status'));
    }

    public function test_approved_request_is_locked(): void
    {
        $transaction = $this->fixture(['status' => Transaction::STATUS_APPROVED]);
        $this->expectException(HttpException::class);
        $this->update($transaction);
    }

    public function test_source_cannot_be_changed_through_another_departments_url(): void
    {
        $transaction = $this->fixture(['request_source' => 'shipper']);
        $this->expectException(HttpException::class);
        $this->update($transaction);
    }

    public function test_manager_list_and_forms_use_site_container(): void
    {
        $transaction = $this->fixture(['request_source' => 'manager']);
        $request = $this->request('/manager/requests');
        $html = (new DepartmentFinanceRequestController)->managerIndex($request)->render();
        self::assertStringContainsString('class="container fr-site-container"', $html);
        self::assertStringContainsString('fr-list-actions', $html);
        $request = $this->request('/manager/requests/create');
        $html = (new DepartmentFinanceRequestController)->managerCreate($request)->render();
        self::assertStringContainsString('class="container fr-site-container"', $html);
        self::assertStringContainsString('id="requestItemsTable"', $html);
        $request = $this->request('/manager/requests/'.$transaction->id.'/edit');
        $html = (new DepartmentFinanceRequestController)->managerEdit($request, $transaction)->render();
        self::assertStringContainsString('class="container fr-site-container"', $html);
        self::assertStringContainsString('id="requestItemsTable"', $html);
    }

    public function test_sorting_precedes_pagination_and_keeps_filters(): void
    {
        for ($i = 1; $i <= 12; $i++) $this->fixture(['amount' => $i * 1000]);
        $request = $this->request('/warehouse/requests?sort=amount&direction=desc&per_page=10&page=2&status=pending_approval');
        $result = (new DepartmentFinanceRequestController)->warehouseIndex($request)->getData()['requests'];
        self::assertSame(12, $result->total());
        self::assertCount(2, $result->items());
        self::assertEquals(2000, $result->items()[0]->amount);
        self::assertStringContainsString('status=pending_approval', $result->url(1));
    }

    public function test_date_upper_bound_works_without_lower_bound(): void
    {
        $this->fixture();
        $request = $this->request('/warehouse/requests?to_date=2000-01-01');
        self::assertSame(0, (new DepartmentFinanceRequestController)->warehouseIndex($request)->getData()['requests']->total());
    }

    public function test_filters_sorting_and_separate_editor_render(): void
    {
        $transaction = $this->fixture();
        $older = $this->fixture(['request_title' => 'Phiếu cũ']);
        DB::table('transactions')->where('id', $older->id)->update(['created_at' => '2020-01-01 00:00:00']);
        $request = $this->request('/warehouse/requests?from_date='.today()->toDateString().'&sort=amount&direction=asc&per_page=10');
        $view = (new DepartmentFinanceRequestController)->warehouseIndex($request);
        self::assertSame(1, $view->getData()['requests']->total());
        self::assertSame(10, $view->getData()['requests']->perPage());
        $html = $view->render();
        self::assertStringContainsString('Lọc phiếu', $html);
        self::assertStringContainsString('requests-per-page', $html);
        self::assertStringContainsString('/warehouse/requests/'.$transaction->id.'/edit', $html);
        self::assertStringNotContainsString('id="requestItemsTable"', $html);
        $request = $this->request('/warehouse/requests/'.$transaction->id.'/edit');
        $html = (new DepartmentFinanceRequestController)->employeeEdit($request, $transaction)->render();
        self::assertStringContainsString('Lọc phiếu', $html);
        self::assertStringContainsString('id="requestItemsTable"', $html);
        $request = $this->request('/warehouse/requests/create');
        $html = (new DepartmentFinanceRequestController)->employeeCreate($request)->render();
        self::assertStringContainsString('Lọc phiếu', $html);
        self::assertStringContainsString('id="requestItemsTable"', $html);
    }
}
