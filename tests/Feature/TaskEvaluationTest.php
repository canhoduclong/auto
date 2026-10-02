<?php
namespace Tests\Feature;

use App\Http\Controllers\TaskAssignmentController;
use App\Models\{TaskAssignment, TaskAssignee, User};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class TaskEvaluationTest extends TestCase
{
    private $app;
    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:', 'cache.default'=>'array', 'session.driver'=>'array']);
        DB::purge('sqlite');
        foreach (['users'=>'name', 'settings'=>'key,value', 'task_assignments'=>'created_by,status,task_type,debt_items', 'task_assignees'=>'task_id,user_id,status,evaluation_score,evaluated_by,evaluated_at', 'task_status_logs'=>'task_id,from_status,to_status,changed_by,reason'] as $name=>$columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach (explode(',', $columns) as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        DB::table('users')->insert([['id'=>1, 'name'=>'Creator'], ['id'=>2, 'name'=>'Recipient']]);
        DB::table('task_assignments')->insert(['id'=>1, 'created_by'=>1, 'status'=>'processing']);
        DB::table('task_assignees')->insert(['id'=>1, 'task_id'=>1, 'user_id'=>2, 'status'=>'processing']);
    }
    protected function tearDown(): void
    {
        DB::disconnect('sqlite'); $this->app->flush(); restore_error_handler(); restore_exception_handler(); parent::tearDown();
    }
    private function score($score, int $user = 1)
    {
        $request = Request::create('/tasks/1/assignees/1/evaluation', 'POST', ['evaluation_score'=>$score]);
        $request->setUserResolver(fn () => User::findOrFail($user));
        return app(TaskAssignmentController::class)->evaluateAssignee($request, TaskAssignment::findOrFail(1), TaskAssignee::findOrFail(1));
    }
    public function test_creator_can_score_zero_and_one_hundred_and_history_is_kept(): void
    {
        foreach ([0, 100] as $score) {
            $this->score($score);
            self::assertSame($score, TaskAssignee::find(1)->evaluation_score);
            self::assertEquals(1, TaskAssignee::find(1)->evaluated_by);
        }
        self::assertSame(2, DB::table('task_status_logs')->count());
        self::assertSame('processing', TaskAssignment::find(1)->status);
    }
    public function test_recipient_cannot_score_themselves(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->score(90, 2);
    }
    public function test_assignment_from_another_task_is_rejected(): void
    {
        DB::table('task_assignees')->where('id',1)->update(['task_id'=>2]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->score(90);
    }
    public function test_debt_progress_is_restricted_and_audited(): void
    {
        $task = TaskAssignment::findOrFail(1);
        $task->update(['task_type'=>'debt_collection', 'debt_items'=>[['customer_name'=>'Customer', 'sale_id'=>2, 'target'=>1000, 'collected'=>0]]]);
        $request = Request::create('/tasks/1/debt-progress/0', 'POST', ['collected'=>500, 'note'=>'Partial collection']);
        $request->setUserResolver(fn () => User::findOrFail(2));
        app(TaskAssignmentController::class)->updateDebtProgress($request, $task, 0);
        self::assertEquals(500, $task->fresh()->debt_items[0]['collected']);
        self::assertSame(1, DB::table('task_status_logs')->count());
        $request->merge(['collected'=>1001]);
        try { app(TaskAssignmentController::class)->updateDebtProgress($request, $task, 0); self::fail('Exceeded target'); }
        catch (\Illuminate\Validation\ValidationException $e) { self::assertEquals(500, $task->fresh()->debt_items[0]['collected']); }
        DB::table('task_assignees')->delete();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $request->merge(['collected'=>600]);
        app(TaskAssignmentController::class)->updateDebtProgress($request, $task, 0);
    }

    public function test_invalid_scores_are_rejected(): void
    {
        foreach ([-1, 101, 4.5, 'invalid', ''] as $score) {
            try { $this->score($score); self::fail('Invalid score accepted'); }
            catch (\Illuminate\Validation\ValidationException $exception) { self::assertArrayHasKey('evaluation_score', $exception->errors()); }
        }
        self::assertNull(TaskAssignee::find(1)->evaluation_score);
    }
}
