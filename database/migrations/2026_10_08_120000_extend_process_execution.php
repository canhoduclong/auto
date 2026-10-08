<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_runs', function (Blueprint $t) {
            $t->unsignedBigInteger('assignee_user_id')->nullable()->index();
            $t->string('assignee_role')->nullable()->index();
            $t->string('assignee_mode')->nullable();
            $t->text('request_note')->nullable();
        });
        Schema::create('process_subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('run_id')->constrained('process_runs');
            $t->string('subject_type', 50);
            $t->unsignedBigInteger('subject_id');
            $t->unique(['run_id', 'subject_type', 'subject_id']);
            $t->index(['subject_type', 'subject_id']);
        });
        Schema::create('process_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained('process_events');
            $t->foreignId('uploaded_by')->constrained('users');
            $t->string('path');
            $t->string('original_name');
            $t->timestamps();
        });
        foreach (DB::table('process_runs')->orderBy('id')->get() as $run) {
            $c = json_decode($run->configuration, true);
            $s = $c['steps'][$run->current_step] ?? [];
            DB::table('process_runs')->where('id', $run->id)->update(['assignee_mode' => $s['assignment_mode'] ?? 'user_role', 'assignee_user_id' => $s['user_id'] ?? null, 'assignee_role' => $s['role'] ?? null]);
        }
        foreach (DB::table('shipping_expense_claims')->get() as $claim) {
            DB::table('process_subjects')->insert(['run_id' => $claim->process_run_id, 'subject_type' => 'shipping_expense', 'subject_id' => $claim->id]);
            foreach (DB::table('shipping_expense_items')->where('claim_id', $claim->id)->get() as $item) {
                DB::table('process_subjects')->insert(['run_id' => $claim->process_run_id, 'subject_type' => 'order', 'subject_id' => $item->order_id]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('process_documents');
        Schema::dropIfExists('process_subjects');
        Schema::table('process_runs', fn (Blueprint $t) => $t->dropColumn(['assignee_user_id', 'assignee_role', 'assignee_mode', 'request_note']));
    }
};
