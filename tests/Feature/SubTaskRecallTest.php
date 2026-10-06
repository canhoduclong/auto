<?php
namespace Tests\Feature;
use App\Models\{TaskAssignment, User};
use App\Services\SubTaskRecallService;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubTaskRecallTest extends TestCase
{
    private $app;
    protected function setUp(): void {
        $this->app=require dirname(__DIR__,2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']); DB::purge('sqlite');
        foreach(['task_assignments'=>'parent_id,created_by,status,code,title,completion_content','task_assignees'=>'task_id,user_id,status','task_status_logs'=>'task_id,from_status,to_status,changed_by,reason'] as $name=>$columns) Schema::create($name,function(Blueprint $t)use($columns){$t->id();foreach(explode(',',$columns) as $c)$t->string($c)->nullable();$t->timestamps();});
        Schema::table('task_assignments', fn (Blueprint $table) => $table->softDeletes());
        DB::table('task_assignments')->insert([
            ['id'=>1,'parent_id'=>null,'created_by'=>1,'status'=>'processing','code'=>'P','title'=>'Parent'],
            ['id'=>2,'parent_id'=>1,'created_by'=>2,'status'=>'completed','code'=>'C','title'=>'Child'],
            ['id'=>3,'parent_id'=>2,'created_by'=>2,'status'=>'pending','code'=>'G','title'=>'Grandchild'],
            ['id'=>4,'parent_id'=>2,'created_by'=>2,'status'=>'done','code'=>'D','title'=>'Finished'],
        ]);
        DB::table('task_assignees')->insert(['task_id'=>2,'user_id'=>3,'status'=>'completed']);
    }
    protected function tearDown(): void { DB::disconnect('sqlite');$this->app->flush();restore_error_handler();restore_exception_handler();parent::tearDown(); }
    private function actor(int $id): User { $user=new User;$user->id=$id;$user->setRelation('roles',new \Illuminate\Database\Eloquent\Collection);return $user; }
    public function test_parent_creator_can_recall_branch_and_preserve_finished_work(): void {
        self::assertSame(2,(new SubTaskRecallService)->recall(1,2,$this->actor(1),'Thay đổi kế hoạch'));
        self::assertSame('cancelled',TaskAssignment::find(2)->status);
        self::assertSame('cancelled',TaskAssignment::find(3)->status);
        self::assertSame('done',TaskAssignment::find(4)->status);
        self::assertSame('processing',TaskAssignment::find(1)->status);
        self::assertSame('cancelled',DB::table('task_assignees')->value('status'));
        self::assertSame('completed',DB::table('task_status_logs')->where('task_id',2)->value('from_status'));
        self::assertSame(3,DB::table('task_status_logs')->count());
    }
    public function test_child_creator_can_recall_and_repeat_is_rejected(): void {
        $service=new SubTaskRecallService;$service->recall(1,2,$this->actor(2),'Thu hồi');
        $this->expectException(HttpException::class);$service->recall(1,2,$this->actor(2),'Lần hai');
    }
    public function test_unrelated_assignee_cannot_recall(): void {
        try {(new SubTaskRecallService)->recall(1,2,$this->actor(3),'Thu hồi'); self::fail('Unauthorized');}
        catch(HttpException $e){self::assertSame(403,$e->getStatusCode());}
        self::assertSame('completed',TaskAssignment::find(2)->status);
        self::assertSame(0,DB::table('task_status_logs')->count());
    }
    public function test_child_creator_can_open_confirmation_and_view_parent_without_parent_assignment(): void {
        $actor = $this->actor(2);
        $parent = TaskAssignment::findOrFail(1);
        $child = TaskAssignment::findOrFail(2);
        $controller = (new \ReflectionClass(\App\Http\Controllers\TaskAssignmentController::class))->newInstanceWithoutConstructor();
        $request = \Illuminate\Http\Request::create('/tasks/1/subtasks/2/recall');
        $request->setUserResolver(fn () => $actor);
        $view = $controller->recallSubTaskForm($request, $parent, $child, new SubTaskRecallService);
        self::assertSame('task_assignments.recall', $view->name());
        self::assertSame('completed', $child->fresh()->status);
        self::assertSame(0, DB::table('task_status_logs')->count());
        $canView = new \ReflectionMethod($controller, 'canViewTask');
        self::assertTrue($canView->invoke($controller, $parent, $actor));
    }
    public function test_confirmation_page_denies_unrelated_user(): void {
        $controller = (new \ReflectionClass(\App\Http\Controllers\TaskAssignmentController::class))->newInstanceWithoutConstructor();
        $request = \Illuminate\Http\Request::create('/tasks/1/subtasks/2/recall');
        $request->setUserResolver(fn () => $this->actor(3));
        try {
            $controller->recallSubTaskForm($request, TaskAssignment::find(1), TaskAssignment::find(2), new SubTaskRecallService);
            self::fail('Unauthorized');
        } catch (HttpException $e) { self::assertSame(403, $e->getStatusCode()); }
    }
    public function test_recalled_branch_can_be_deleted_with_history_preserved(): void {
        TaskAssignment::find(4)->update(['status'=>'cancelled']);
        $service = new SubTaskRecallService;
        $service->recall(1, 2, $this->actor(2), 'Thu hồi');
        self::assertSame(3, $service->deleteRecalled(1, 2, $this->actor(2)));
        self::assertNull(TaskAssignment::find(2));
        self::assertNull(TaskAssignment::find(3));
        self::assertNotNull(TaskAssignment::withTrashed()->find(2));
        self::assertSame(1, TaskAssignment::count());
        self::assertGreaterThan(0, DB::table('task_status_logs')->where('task_id', 2)->count());
    }
    public function test_delete_requires_recall_and_protects_finished_descendants(): void {
        $service = new SubTaskRecallService;
        try { $service->deleteRecalled(1, 2, $this->actor(2)); self::fail('Active task deleted'); }
        catch (HttpException $e) { self::assertSame(422, $e->getStatusCode()); }
        $service->recall(1, 2, $this->actor(2), 'Thu hồi');
        try { $service->deleteRecalled(1, 2, $this->actor(2)); self::fail('Finished descendant lost'); }
        catch (HttpException $e) { self::assertSame(422, $e->getStatusCode()); }
        self::assertNotNull(TaskAssignment::find(2));
        self::assertSame('done', TaskAssignment::find(4)->status);
    }
    public function test_unrelated_user_cannot_delete_recalled_task(): void {
        TaskAssignment::find(2)->update(['status'=>'cancelled']);
        try { (new SubTaskRecallService)->deleteRecalled(1, 2, $this->actor(3)); self::fail('Unauthorized'); }
        catch (HttpException $e) { self::assertSame(403, $e->getStatusCode()); }
        self::assertNotNull(TaskAssignment::find(2));
    }
    public function test_task_must_be_a_child_of_requested_parent(): void {
        try {(new SubTaskRecallService)->recall(1,3,$this->actor(1),'Thu hồi');self::fail('Wrong parent');}
        catch(HttpException $e){self::assertSame(404,$e->getStatusCode());}
        self::assertSame('pending',TaskAssignment::find(3)->status);
    }
}
