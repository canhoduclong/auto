<?php

namespace Tests\Feature;

use App\Http\Controllers\TaskAssignmentController;
use App\Models\{Role, TaskDelegateConfig, User};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use PHPUnit\Framework\TestCase;

class AdminTaskAssigneesTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('task_delegate_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('assigner_id');
            $table->integer('assignee_id');
            $table->boolean('is_active');
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('value');
        });
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Receiver'],
            ['id' => 3, 'name' => 'Hidden receiver'],
            ['id' => 4, 'name' => 'Other receiver'],
        ]);
        DB::table('settings')->insert(['key' => 'task_assignee_hidden_3', 'value' => '1']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    private function user(string $roleName): User
    {
        $user = new User;
        $user->id = 1;
        $role = new Role;
        $role->name = $roleName;
        $user->setRelation('roles', new Collection([$role]));
        return $user;
    }

    private function controllerAssignees(User $user): array
    {
        $controller = (new \ReflectionClass(TaskAssignmentController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'allowedAssigneesFor');
        return $method->invoke($controller, $user)->pluck('id')->sort()->values()->all();
    }

    public function test_admin_can_select_all_users_without_delegation_configuration(): void
    {
        $admin = $this->user('admin');
        self::assertSame([1, 2, 3, 4], TaskDelegateConfig::allowedAssignees($admin)->pluck('id')->sort()->values()->all());
        self::assertSame([1, 2, 3, 4], $this->controllerAssignees($admin));
    }

    public function test_other_roles_remain_limited_to_active_visible_delegated_users(): void
    {
        DB::table('task_delegate_configs')->insert([
            ['assigner_id' => 1, 'assignee_id' => 2, 'is_active' => true],
            ['assigner_id' => 1, 'assignee_id' => 3, 'is_active' => true],
            ['assigner_id' => 1, 'assignee_id' => 4, 'is_active' => false],
        ]);
        self::assertSame([2], $this->controllerAssignees($this->user('manager')));
    }
}
