<?php
namespace Tests\Feature;

use App\Models\{TaskAssignment, TaskAssignee, User, Role};
use App\Services\TaskPublicationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Auth, DB, Notification, Schema};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TaskPublicationTest extends TestCase
{
    private $app;
    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:', 'cache.default'=>'array']);
        DB::purge('sqlite');
        Notification::fake();
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('task_assignments', function (Blueprint $t) {
            $t->id(); $t->integer('created_by'); $t->integer('accountable_user_id'); $t->string('work_kind');
            $t->string('title'); $t->string('status'); $t->integer('approval_flow_id')->nullable();
            $t->integer('publication_approver_id')->nullable(); $t->string('publication_status')->nullable();
            $t->json('publication_assignee_ids')->nullable(); $t->timestamp('published_at')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('task_assignees', function (Blueprint $t) {
            $t->id(); $t->integer('task_id'); $t->integer('user_id'); $t->string('status'); $t->timestamp('accepted_at')->nullable(); $t->timestamp('started_at')->nullable(); $t->timestamps();
        });
        Schema::create('task_status_logs', function (Blueprint $t) {
            $t->id(); $t->integer('task_id'); $t->string('from_status')->nullable(); $t->string('to_status'); $t->integer('changed_by'); $t->text('reason'); $t->timestamp('created_at');
        });
        DB::table('users')->insert(array_map(fn ($id)=>['id'=>$id,'name'=>'User '.$id], [1,2,3,4]));
        DB::table('task_assignments')->insert(['id'=>1,'created_by'=>1,'accountable_user_id'=>3,'work_kind'=>'execution','title'=>'Draft','status'=>'draft','publication_approver_id'=>2,'publication_status'=>'pending','publication_assignee_ids'=>'[3,4]']);
    }
    protected function tearDown(): void
    {
        DB::disconnect('sqlite'); $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    private function actor(int $id): User
    {
        $user=User::findOrFail($id); $user->setRelation('roles',new Collection); Auth::setUser($user); return $user;
    }
    private function denied(callable $action, int $status): void
    {
        try { $action(); self::fail('Expected rejection'); } catch (HttpException $e) { self::assertSame($status,$e->getStatusCode()); }
    }
    public function test_only_selected_approver_can_publish_and_recipients_are_not_activated_before_approval(): void
    {
        $service=new TaskPublicationService; $task=TaskAssignment::findOrFail(1);
        self::assertSame(0,TaskAssignee::count());
        $this->denied(fn ()=>$service->decide($task,$this->actor(1),'approve'),403);
        $this->denied(fn ()=>$service->decide($task,$this->actor(3),'approve'),403);
        Notification::assertNothingSent();
        $published=$service->decide($task,$this->actor(2),'approve');
        self::assertSame('pending',$published->status); self::assertSame('approved',$published->publication_status);
        self::assertNotNull($published->published_at); self::assertSame([3,4],TaskAssignee::orderBy('user_id')->pluck('user_id')->all());
        self::assertSame(0,TaskAssignee::whereNotNull('accepted_at')->count());
        Notification::assertSentTo(User::find(3),\App\Notifications\OperatingNotification::class);
        Notification::assertSentTo(User::find(4),\App\Notifications\OperatingNotification::class);
        $this->denied(fn ()=>$service->decide($task,$this->actor(2),'approve'),422);
        self::assertSame(2,TaskAssignee::count());
    }
    public function test_rejection_requires_reason_and_creator_can_resubmit_without_notifying_recipients(): void
    {
        $service=new TaskPublicationService; $task=TaskAssignment::findOrFail(1);
        $this->denied(fn ()=>$service->decide($task,$this->actor(2),'reject',''),422);
        $service->decide($task,$this->actor(2),'reject','Bổ sung mục tiêu');
        self::assertSame('rejected',$task->fresh()->publication_status); self::assertSame(0,TaskAssignee::count());
        Notification::assertNotSentTo(User::find(3),\App\Notifications\OperatingNotification::class);
        $this->denied(fn ()=>$service->resubmit($task,$this->actor(2)),403);
        $service->resubmit($task,$this->actor(1));
        self::assertSame('pending',$task->fresh()->publication_status); self::assertSame('draft',$task->fresh()->status);
        Notification::assertSentTo(User::find(2),\App\Notifications\OperatingNotification::class);
    }
    public function test_unpublished_task_is_hidden_from_recipients_and_direct_detail_access_is_denied(): void
    {
        $task=TaskAssignment::findOrFail(1);
        $controller=(new \ReflectionClass(\App\Http\Controllers\TaskAssignmentController::class))->newInstanceWithoutConstructor();
        $method=new \ReflectionMethod($controller,'canViewTask');
        foreach ([1=>true,2=>true,3=>false,4=>false] as $id=>$visible) {
            $actor=$this->actor($id);
            self::assertSame($visible,$method->invoke($controller,$task,$actor));
            self::assertSame($visible,TaskAssignment::publicationVisibleTo($actor)->exists());
        }
    }
    public function test_edit_can_change_approver_and_only_new_approver_can_publish(): void
    {
        $service = new TaskPublicationService;
        $task = TaskAssignment::findOrFail(1);
        $service->configureForEdit($task, $this->actor(1), 4, [3]);
        self::assertSame(4, (int) $task->fresh()->publication_approver_id);
        self::assertSame('draft', $task->fresh()->status);
        self::assertSame(0, TaskAssignee::count());
        $this->denied(fn () => $service->decide($task, $this->actor(2), 'approve'), 403);
        $service->decide($task, $this->actor(4), 'approve');
        self::assertSame([3], TaskAssignee::pluck('user_id')->all());
    }

    public function test_edit_without_approval_publishes_and_notifies_recipients_once(): void
    {
        $service = new TaskPublicationService;
        $task = TaskAssignment::findOrFail(1);
        $service->configureForEdit($task, $this->actor(1), null, [3, 4]);
        self::assertFalse($task->fresh()->awaitsPublication());
        self::assertNull($task->fresh()->publication_approver_id);
        self::assertSame('pending', $task->fresh()->status);
        self::assertSame([3, 4], TaskAssignee::orderBy('user_id')->pluck('user_id')->all());
        Notification::assertSentToTimes(User::find(3), \App\Notifications\OperatingNotification::class, 1);
        $service->configureForEdit($task->fresh(), $this->actor(1), null, [3, 4]);
        Notification::assertSentToTimes(User::find(3), \App\Notifications\OperatingNotification::class, 1);
    }

    public function test_edit_can_require_approval_for_a_task_not_yet_received(): void
    {
        $service = new TaskPublicationService;
        $task = TaskAssignment::findOrFail(1);
        $service->configureForEdit($task, $this->actor(1), null, [3]);
        $service->configureForEdit($task->fresh(), $this->actor(1), 2, [3]);
        self::assertTrue($task->fresh()->awaitsPublication());
        self::assertSame('draft', $task->fresh()->status);
        self::assertSame(0, TaskAssignee::count());
        $this->denied(fn () => $service->configureForEdit($task->fresh(), $this->actor(3), null, [3]), 403);
        $this->denied(fn () => $service->configureForEdit($task->fresh(), $this->actor(1), 1, [3]), 422);
    }
    public function test_edit_saves_new_accountable_user_instead_of_requiring_previous_one(): void
    {
        Schema::table('task_assignments', function (Blueprint $t) {
            $t->string('task_type')->nullable(); $t->text('description')->nullable();
            $t->string('priority')->nullable(); $t->integer('parent_id')->nullable();
            $t->timestamp('due_date')->nullable(); $t->json('attachments')->nullable();
        });
        $actor = $this->actor(1);
        $role = new Role; $role->name = 'admin';
        $actor->setRelation('roles', new Collection([$role]));
        $request = \Illuminate\Http\Request::create('/task-assignments/1', 'PUT', [
            'accountable_user_id' => 4, 'title' => 'Changed owner', 'priority' => 'medium',
            'assignee_ids' => [4], 'publication_approver_id' => null,
        ]);
        $request->setUserResolver(fn () => $actor);
        $controller = (new \ReflectionClass(\App\Http\Controllers\TaskAssignmentController::class))->newInstanceWithoutConstructor();
        $controller->update($request, TaskAssignment::findOrFail(1));
        self::assertSame(4, (int) TaskAssignment::findOrFail(1)->accountable_user_id);
        self::assertSame('Changed owner', TaskAssignment::findOrFail(1)->title);
        self::assertSame([4], TaskAssignee::pluck('user_id')->all());
    }
}
