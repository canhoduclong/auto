<?php
namespace Tests\Feature;

use App\Models\{User, Role};
use App\Services\AccountingWorkQueueService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\TestCase;

class AccountingWorkQueueTest extends TestCase
{
    private $app;
    private $user;
    private array $tables = ['orders','accounting_reconciliations','accounting_reconciliation_exclusions','task_assignments','task_assignees','operating_proposals','operating_votes','transactions','approval_orders','approval_steps','order_adjustments'];
    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__,2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['cache.default'=>'array','session.driver'=>'array']);
        foreach($this->tables as $table) {
            $ddl = array_values((array)DB::selectOne("SHOW CREATE TABLE `$table`"))[1];
            $ddl = str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl);
            $ddl = preg_replace('/^  CONSTRAINT .*\n/m','',$ddl);
            $ddl = preg_replace('/,\n\)/',"\n)",$ddl);
            DB::statement($ddl);
        }
        $this->user = new User; $this->user->id=900001;
        $this->user->setRelation('roles',collect([new Role(['name'=>'accountant'])]));
    }
    protected function tearDown(): void
    {
        foreach(array_reverse($this->tables) as $table)DB::statement("DROP TEMPORARY TABLE IF EXISTS `$table`");
        $this->app->flush();restore_error_handler();restore_exception_handler();parent::tearDown();
    }
    public function test_revenue_counts_only_unconfirmed_deliveries_across_dates(): void
    {
        foreach(['delivered','completed','packed','cancelled','completed','delivered'] as $i=>$status)DB::table('orders')->insert(['id'=>$i+1,'code'=>'TEST-'.($i+1),'customer_id'=>900002,'status'=>$status,'created_at'=>'2020-01-01','updated_at'=>now()]);
        DB::table('accounting_reconciliations')->insert(['order_id'=>5,'status'=>'confirmed']);
        DB::table('accounting_reconciliation_exclusions')->insert(['order_id'=>6,'excluded_by'=>900001,'reason'=>'Test','excluded_at'=>now()]);
        self::assertSame([1,2],(new AccountingWorkQueueService)->revenueOrders()->orderBy('id')->pluck('id')->all());
    }
    public function test_personal_tasks_and_votes_exclude_other_users_and_closed_work(): void
    {
        foreach(['pending','processing','completed','done','pending'] as $i=>$status) {
            DB::table('task_assignments')->insert(['id'=>$i+1,'code'=>'TASK-'.($i+1),'title'=>'Test task','status'=>$status,'created_by'=>900001]);
            DB::table('task_assignees')->insert(['task_id'=>$i+1,'user_id'=>$i===4?900002:900001,'status'=>$status==='done'?'completed':$status]);
        }
        foreach([null,'agree',null] as $i=>$choice) {
            DB::table('operating_proposals')->insert(['id'=>$i+1,'title'=>'Test vote','description'=>'Test','quorum'=>1,'created_by'=>900002,'status'=>'open','closes_at'=>$i===2?now()->subDay():now()->addDay()]);
            DB::table('operating_votes')->insert(['proposal_id'=>$i+1,'user_id'=>900001,'choice'=>$choice]);
        }
        $service=new AccountingWorkQueueService;
        self::assertSame([1,2],$service->todoTasks($this->user)->orderBy('id')->pluck('id')->all());
        self::assertSame([3],$service->relatedTasks($this->user)->pluck('id')->all());
        self::assertSame([1],$service->relatedProposals($this->user)->pluck('id')->all());
    }
    public function test_finance_only_counts_current_accounting_step_and_completion(): void
    {
        foreach([1=>'accountant',2=>'director'] as $id=>$role)DB::table('approval_steps')->insert(['id'=>$id,'approval_flow_id'=>1,'role_slug'=>$role,'step_order'=>$id]);
        foreach([1=>'pending_approval',2=>'pending_approval',3=>'approved_pending_completion',4=>'approved',5=>'rejected'] as $id=>$status)DB::table('transactions')->insert(['id'=>$id,'type'=>'extra_expense','amount'=>1000,'request_source'=>'warehouse','status'=>$status]);
        DB::table('approval_orders')->insert(['transaction_id'=>1,'approval_step_id'=>1,'status'=>'pending']);
        DB::table('approval_orders')->insert(['transaction_id'=>2,'approval_step_id'=>2,'status'=>'pending']);
        $ids=(new AccountingWorkQueueService)->financeRequests($this->user)->pluck('id')->sort()->values()->all();
        self::assertSame([1,3],$ids);
    }
    public function test_dashboard_renders_nonzero_badges_and_links_to_full_queues(): void
    {
        DB::table('orders')->insert(['code'=>'TEST-OLD','customer_id'=>900002,'status'=>'delivered','created_at'=>'2020-01-01']);
        DB::table('transactions')->insert(['type'=>'extra_expense','amount'=>1000,'request_source'=>'warehouse','status'=>'approved_pending_completion','request_title'=>'Chi phí kiểm thử']);
        DB::table('task_assignments')->insert(['id'=>1,'code'=>'T1','title'=>'Việc chờ nhận','created_by'=>900002,'status'=>'pending']);
        DB::table('task_assignees')->insert(['task_id'=>1,'user_id'=>900001,'status'=>'pending']);
        $queues=(new AccountingWorkQueueService)->dashboard($this->user);
        self::assertSame(1,$queues['revenue']['count']);
        self::assertSame(1,$queues['finance']['count']);
        self::assertSame(1,$queues['todo']['count']);
        $html=view('accounting.work-queues',['workQueues'=>$queues])->render();
        self::assertSame(3,substr_count($html,'aria-label="1 mục cần xử lý"'));
        self::assertStringContainsString('work_queue=1',$html);
        self::assertStringContainsString('attention=todo',$html);
        self::assertStringContainsString('Chi phí kiểm thử',$html);
    }

    public function test_complete_accounting_dashboard_renders_work_first(): void
    {
        \Illuminate\Support\Facades\Auth::setUser($this->user);
        $request=\Illuminate\Http\Request::create('/accounting');
        $route=$this->app['router']->getRoutes()->match($request); $route->bind($request);
        $request->setRouteResolver(fn()=>$route); $request->setUserResolver(fn()=>$this->user);
        $this->app->instance('request',$request);
        $this->app['view']->share('errors',new \Illuminate\Support\ViewErrorBag);
        $html=(new \App\Http\Controllers\AccountingDashboardController)->index($request)->render();
        self::assertStringContainsString('Công việc cần xử lý',$html);
        self::assertStringContainsString('Tổng quan tài chính',$html);
        self::assertLessThan(strpos($html,'<summary class="acc-overview-toggle">'),strpos($html,'id="acc-work-title"'));
    }

    public function test_dashboard_badges_are_hidden_when_empty(): void
    {
        $queues=(new AccountingWorkQueueService)->dashboard($this->user);
        self::assertSame(0,$queues['revenue']['count']);
        $html=view('accounting.work-queues',['workQueues'=>$queues])->render();
        self::assertStringNotContainsString('aria-label="0 mục cần xử lý"',$html);
        self::assertSame(5,substr_count($html,'<article class="acc-work-card">'));
    }
}
