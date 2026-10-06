<?php
namespace Tests\Feature;
use App\Models\{TaskAssignment,TaskAssignee,User};
use Illuminate\Support\Facades\{DB,Schema,Auth};
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
class TaskReceiptTest extends TestCase {
 private $app;
 protected function setUp():void {
  $this->app=require dirname(__DIR__,2).'/bootstrap/app.php';$this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);DB::purge('sqlite');
  Schema::create('task_assignments',function(Blueprint $t){$t->id();$t->integer('created_by');$t->integer('accountable_user_id');$t->string('work_kind')->nullable();$t->string('status');$t->string('title');$t->string('code');$t->string('rejected_reason')->nullable();$t->timestamps();$t->softDeletes();});
  Schema::create('task_assignees',function(Blueprint $t){$t->id();$t->integer('task_id');$t->integer('user_id');$t->string('status');$t->text('note')->nullable();$t->timestamp('accepted_at')->nullable();$t->timestamp('started_at')->nullable();$t->timestamp('completed_at')->nullable();$t->timestamps();});
  Schema::create('task_status_logs',function(Blueprint $t){$t->id();$t->integer('task_id');$t->string('from_status')->nullable();$t->string('to_status');$t->integer('changed_by');$t->text('reason');$t->timestamp('created_at');});
  Schema::create('approval_orders',function(Blueprint $t){$t->id();$t->integer('task_id');$t->string('status');});
  DB::table('task_assignments')->insert(['id'=>1,'created_by'=>1,'accountable_user_id'=>2,'work_kind'=>'execution','status'=>'pending','title'=>'Test','code'=>'TEST']);
  DB::table('task_assignees')->insert(['task_id'=>1,'user_id'=>2,'status'=>'pending']);
 }
 protected function tearDown():void{DB::disconnect('sqlite');$this->app->flush();restore_error_handler();restore_exception_handler();parent::tearDown();}
 private function request(int $id,array $data=[]):\Illuminate\Http\Request{$u=new User;$u->id=$id;$u->setRelation('roles',new \Illuminate\Database\Eloquent\Collection);Auth::setUser($u);$r=\Illuminate\Http\Request::create('/tasks/1','POST',$data);$r->setUserResolver(fn()=>$u);return $r;}
 private function controller(){return (new \ReflectionClass(\App\Http\Controllers\TaskAssignmentController::class))->newInstanceWithoutConstructor();}
 private function reject(callable $action,int $code):void{try{$action();self::fail('Expected rejection');}catch(HttpException $e){self::assertSame($code,$e->getStatusCode());}}
 public function test_receipt_does_not_start_task_and_start_requires_receipt():void {
  $c=$this->controller();$task=TaskAssignment::find(1);
  $this->reject(fn()=>$c->assigneeUpdate($this->request(2,['status'=>'processing']),$task),422);
  $c->acceptTask($this->request(2),$task);
  self::assertSame('pending',$task->fresh()->status);self::assertNotNull(TaskAssignee::first()->accepted_at);self::assertNull(TaskAssignee::first()->started_at);
  $c->assigneeUpdate($this->request(2,['status'=>'processing']),$task);
  self::assertSame('processing',$task->fresh()->status);self::assertNotNull(TaskAssignee::first()->started_at);
 }
 public function test_pending_approval_prevents_start_even_after_receipt():void {
  $c=$this->controller();$task=TaskAssignment::find(1);$c->acceptTask($this->request(2),$task);
  DB::table('approval_orders')->insert(['task_id'=>1,'status'=>'pending']);
  $this->reject(fn()=>$c->assigneeUpdate($this->request(2,['status'=>'processing']),$task),422);
  self::assertNull(TaskAssignee::first()->started_at);
 }
 public function test_duplicate_receipt_is_rejected_without_extra_audit():void {
  $c=$this->controller();$task=TaskAssignment::find(1);$c->acceptTask($this->request(2),$task);
  $this->reject(fn()=>$c->acceptTask($this->request(2),$task),422);
  self::assertSame(1,DB::table('task_status_logs')->count());
 }
 public function test_cancelled_task_cannot_be_received():void {
  $task=TaskAssignment::find(1);$task->update(['status'=>'cancelled']);
  $this->reject(fn()=>$this->controller()->acceptTask($this->request(2),$task),422);
  self::assertNull(TaskAssignee::first()->accepted_at);
 }
}
