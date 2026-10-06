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
        DB::table('task_assignments')->insert([
            ['id'=>1,'parent_id'=>null,'created_by'=>1,'status'=>'processing','code'=>'P','title'=>'Parent'],
            ['id'=>2,'parent_id'=>1,'created_by'=>2,'status'=>'completed','code'=>'C','title'=>'Child'],
            ['id'=>3,'parent_id'=>2,'created_by'=>2,'status'=>'pending','code'=>'G','title'=>'Grandchild'],
            ['id'=>4,'parent_id'=>2,'created_by'=>2,'status'=>'done','code'=>'D','title'=>'Finished'],
        ]);
        DB::table('task_assignees')->insert(['task_id'=>2,'user_id'=>3,'status'=>'completed']);
    }
    protected function tearDown(): void { DB::disconnect('sqlite');$this->app->flush();restore_error_handler();restore_exception_handler();parent::tearDown(); }
    private function actor(int $id): User { $user=$this->createMock(User::class);$user->id=$id;$user->method('hasRole')->willReturn(false);return $user; }
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
    public function test_task_must_be_a_child_of_requested_parent(): void {
        try {(new SubTaskRecallService)->recall(1,3,$this->actor(1),'Thu hồi');self::fail('Wrong parent');}
        catch(HttpException $e){self::assertSame(404,$e->getStatusCode());}
        self::assertSame('pending',TaskAssignment::find(3)->status);
    }
}
