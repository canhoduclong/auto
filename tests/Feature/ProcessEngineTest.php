<?php

namespace Tests\Feature;

use App\Models\ProcessDefinition;
use App\Models\ProcessRun;
use App\Models\Role;
use App\Models\User;
use App\Services\ProcessEngine;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProcessEngineTest extends TestCase
{
    private $app;

    private ProcessEngine $engine;

    private ProcessDefinition $definition;

    private User $shipper;

    private User $coordinator;

    private User $accountant;

    protected function setUp(): void
    {
        $this->app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('process_definitions', function (Blueprint $t) {
            $t->id();
            $t->string('activity');
            $t->boolean('is_active');
            $t->integer('version');
            $t->json('configuration');
            $t->timestamps();
        });
        Schema::create('process_runs', function (Blueprint $t) {
            $t->id();
            $t->integer('definition_id');
            $t->integer('definition_version');
            $t->string('activity');
            $t->integer('initiator_id');
            $t->json('configuration');
            $t->string('status');
            $t->integer('current_step');
            $t->integer('assignee_user_id')->nullable();
            $t->string('assignee_mode')->nullable();
            $t->string('assignee_role')->nullable();
            $t->integer('resume_step')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });
        $this->engine = new ProcessEngine;
        $this->shipper = $this->user('Shipper');
        $this->coordinator = $this->user('manager_shipper');
        $this->accountant = $this->user('accountant');
        $this->definition = ProcessDefinition::create(['activity' => 'shipping_expense', 'is_active' => true, 'version' => 1, 'configuration' => ['initiator_role' => 'shipper', 'steps' => [['name' => 'Điều phối', 'role' => 'manager_shipper', 'user_id' => $this->coordinator->id], ['name' => 'Kế toán', 'role' => 'accountant', 'user_id' => $this->accountant->id]]]]);
    }

    private function user(string $role): User
    {
        $user = User::create(['name' => $role]);
        $user->setRelation('roles', collect([new Role(['name' => $role])]));
        // start() resolves reviewers from the database; provide role relations without production tables.
        User::retrieved(function ($loaded) use ($user, $role) {
            if ($loaded->id === $user->id) {
                $loaded->setRelation('roles', collect([new Role(['name' => $role])]));
            }
        });

        return $user;
    }

    protected function tearDown(): void
    {
        User::flushEventListeners();
        DB::disconnect('sqlite');
        $this->app->flush();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    public function test_revisions_resume_at_the_requesting_step_and_confirmation_is_final(): void
    {
        $run = $this->engine->start($this->definition, $this->shipper);
        $this->engine->decide($run, $this->coordinator, 'revise');
        $this->engine->resubmit($run, $this->shipper);
        self::assertSame(0, $run->current_step);
        $this->engine->decide($run, $this->coordinator, 'approve');
        $this->engine->decide($run, $this->accountant, 'revise');
        $this->engine->resubmit($run, $this->shipper);
        self::assertSame(1, $run->current_step);
        self::assertFalse($this->engine->canHandle($run, $this->coordinator));
        $this->engine->decide($run, $this->accountant, 'approve');
        self::assertSame('confirmed', $run->fresh()->status);
        self::assertNotNull($run->finished_at);
        self::assertFalse($this->engine->canHandle($run, $this->accountant));
    }

    public function test_role_alone_cannot_approve_and_accountant_cannot_skip_the_coordinator(): void
    {
        $run = $this->engine->start($this->definition, $this->shipper);
        self::assertFalse($this->engine->canHandle($run, $this->user('manager_shipper')));
        self::assertFalse($this->engine->canHandle($run, $this->accountant));
        $this->expectException(HttpException::class);
        $this->engine->decide($run, $this->accountant, 'approve');
    }

    public function test_configuration_changes_do_not_rewrite_existing_runs(): void
    {
        $run = $this->engine->start($this->definition, $this->shipper);
        $config = $this->definition->configuration;
        $config['steps'][0]['user_id'] = 999;
        $this->definition->update(['version' => 2, 'configuration' => $config]);
        self::assertSame(1, $run->fresh()->definition_version);
        self::assertSame($this->coordinator->id, $run->fresh()->step()['user_id']);
    }

    public function test_rejection_is_terminal(): void
    {
        $run = $this->engine->start($this->definition, $this->shipper);
        $this->engine->decide($run, $this->coordinator, 'reject');
        self::assertSame('rejected', $run->status);
        self::assertFalse($this->engine->canHandle($run, $this->coordinator));
        $this->expectException(HttpException::class);
        $this->engine->resubmit($run, $this->shipper);
    }

    public function test_specific_user_does_not_need_the_step_role(): void
    {
        $config = $this->definition->configuration;
        $config['steps'][0]['assignment_mode'] = 'user';
        $config['steps'][0]['user_id'] = $this->shipper->id;
        $config['steps'][0]['role'] = null;
        $this->definition->update(['configuration' => $config]);
        $run = $this->engine->start($this->definition, $this->shipper);
        self::assertTrue($this->engine->canHandle($run, $this->shipper));
        self::assertFalse($this->engine->canHandle($run, $this->coordinator));
    }

    public function test_role_assignment_accepts_any_member_of_the_selected_role(): void
    {
        $config = $this->definition->configuration;
        $config['steps'][0]['assignment_mode'] = 'role';
        $config['steps'][0]['user_id'] = null;
        $this->definition->update(['configuration' => $config]);
        // Construct a run directly to isolate eligibility from database role lookup.
        $run = new ProcessRun(['configuration' => $config, 'status' => 'running', 'current_step' => 0]);
        self::assertTrue($this->engine->canHandle($run, $this->coordinator));
        self::assertTrue($this->engine->canHandle($run, $this->user('manager_shipper')));
        self::assertFalse($this->engine->canHandle($run, $this->shipper));
    }

    public function test_disabled_actions_and_required_documents_are_enforced(): void
    {
        $config = $this->definition->configuration;
        $config['steps'][0]['actions'] = ['approve' => ['label' => 'Duyệt', 'note_required' => true, 'document_required' => true]];
        $this->definition->update(['configuration' => $config]);
        $run = $this->engine->start($this->definition, $this->shipper);
        foreach ([['reject', 'Lý do', 1], ['approve', '', 1], ['approve', 'Đồng ý', 0]] as [$action,$note,$count]) {
            try {
                $this->engine->validateAction($run, $this->coordinator, $action, $note, $count);
                self::fail('Must reject action');
            } catch (HttpException $e) {
                self::assertSame(422, $e->getStatusCode());
            }
        }
        $this->engine->validateAction($run, $this->coordinator, 'approve', 'Đồng ý', 1);
        self::assertTrue($this->engine->canHandle($run, $this->coordinator));
    }

    public function test_revision_can_restart_from_first_step(): void
    {
        $config = $this->definition->configuration;
        $config['steps'][1]['revision_resume'] = 'first';
        $this->definition->update(['configuration' => $config]);
        $run = $this->engine->start($this->definition, $this->shipper);
        $this->engine->decide($run, $this->coordinator, 'approve');
        $this->engine->decide($run, $this->accountant, 'revise');
        $this->engine->resubmit($run, $this->shipper);
        self::assertSame(0, $run->current_step);
        self::assertTrue($this->engine->canHandle($run, $this->coordinator));
        self::assertFalse($this->engine->canHandle($run, $this->accountant));
    }

    public function test_shipping_timeline_distinguishes_revision_confirmation_and_payment(): void
    {
        $run = new ProcessRun(['configuration' => $this->definition->configuration, 'status' => 'revision', 'current_step' => 1, 'resume_step' => 1]);
        $run->setRelation('events', collect([new \App\Models\ProcessEvent(['action' => 'approve', 'created_at' => now()->subHour()]), new \App\Models\ProcessEvent(['action' => 'revise', 'created_at' => now()])]));
        $claim = new \App\Models\ShippingExpenseClaim(['created_at' => now()->subDay()]);
        $claim->setRelation('run', $run)->setRelation('shipper', $this->shipper);
        $users = collect([$this->coordinator, $this->accountant])->keyBy('id');
        $nodes = \App\Support\ShippingExpenseTimeline::nodes($claim, $users);
        self::assertCount(3, $nodes);
        self::assertSame('done', $nodes[1]['state']);
        self::assertSame('requested', $nodes[2]['state']);
        self::assertSame('revision', $nodes[0]['state']);
        self::assertNotNull($nodes[2]['entries'][0]['time']);
        $run->status = 'confirmed';
        $claim->confirmed_at = now();
        $nodes = \App\Support\ShippingExpenseTimeline::nodes($claim, $users);
        self::assertCount(3, $nodes);
        self::assertSame('done', $nodes[2]['state']);
        self::assertSame('current', $nodes[0]['state']);
        self::assertSame('Chi phí đã chốt · Có thể gửi thanh toán', $nodes[0]['description']);
    }
}
