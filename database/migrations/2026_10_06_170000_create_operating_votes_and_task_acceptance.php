<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('operating_proposals', function (Blueprint $t) {
            $t->id(); $t->foreignId('created_by')->constrained('users');
            $t->string('title'); $t->text('description'); $t->string('status')->default('open');
            $t->unsignedInteger('quorum'); $t->unsignedInteger('approval_percent')->default(51);
            $t->timestamp('closes_at'); $t->timestamp('closed_at')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users');
            $t->text('conclusion')->nullable(); $t->timestamps();
        });
        Schema::create('operating_votes', function (Blueprint $t) {
            $t->id(); $t->foreignId('proposal_id')->constrained('operating_proposals');
            $t->foreignId('user_id')->constrained('users'); $t->string('choice')->nullable();
            $t->text('comment')->nullable(); $t->timestamp('voted_at')->nullable(); $t->timestamps();
            $t->unique(['proposal_id','user_id']);
        });
        Schema::table('task_assignments', function (Blueprint $t) {
            $t->string('work_kind')->nullable()->index();
            $t->foreignId('proposal_id')->nullable()->constrained('operating_proposals');
            $t->foreignId('accountable_user_id')->nullable()->constrained('users');
            $t->timestamp('acceptance_due_at')->nullable();
        });
        Schema::table('task_assignees', function (Blueprint $t) {
            $t->timestamp('accepted_at')->nullable(); $t->timestamp('started_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('task_assignees', fn (Blueprint $t) => $t->dropColumn(['accepted_at','started_at']));
        Schema::table('task_assignments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('proposal_id'); $t->dropConstrainedForeignId('accountable_user_id');
            $t->dropColumn(['work_kind','acceptance_due_at']);
        });
        Schema::dropIfExists('operating_votes'); Schema::dropIfExists('operating_proposals');
    }
};
