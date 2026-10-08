<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_definitions', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('activity');
            $t->unsignedInteger('version')->default(1);
            $t->boolean('is_active')->default(false);
            $t->json('configuration');
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('process_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('definition_id')->constrained('process_definitions');
            $t->unsignedInteger('definition_version');
            $t->string('activity');
            $t->foreignId('initiator_id')->constrained('users');
            $t->json('configuration');
            $t->string('status')->default('running');
            $t->unsignedInteger('current_step')->default(0);
            $t->unsignedInteger('resume_step')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
            $t->index(['activity', 'status']);
        });
        Schema::create('shipping_expense_claims', function (Blueprint $t) {
            $t->id();
            $t->foreignId('process_run_id')->unique()->constrained('process_runs');
            $t->foreignId('shipper_id')->constrained('users');
            $t->text('note')->nullable();
            $t->decimal('total', 16, 2)->default(0);
            $t->foreignId('payment_transaction_id')->nullable()->constrained('transactions');
            $t->timestamp('confirmed_at')->nullable();
            $t->foreignId('confirmed_by')->nullable()->constrained('users');
            $t->timestamps();
        });
        Schema::create('shipping_expense_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('shipping_expense_claims');
            $t->foreignId('order_id')->constrained('orders');
            $t->foreignId('dispatch_id')->constrained('shipper_dispatch_histories');
            $t->decimal('amount', 16, 2);
            $t->text('note')->nullable();
            $t->timestamps();
            $t->unique(['claim_id', 'order_id']);
        });
        Schema::create('shipping_expense_order_locks', function (Blueprint $t) {
            $t->foreignId('order_id')->primary()->constrained('orders');
            $t->foreignId('claim_id')->constrained('shipping_expense_claims');
        });
        Schema::create('process_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('run_id')->constrained('process_runs');
            $t->foreignId('actor_id')->constrained('users');
            $t->string('action');
            $t->text('note')->nullable();
            $t->json('snapshot');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['process_events', 'shipping_expense_order_locks', 'shipping_expense_items', 'shipping_expense_claims', 'process_runs', 'process_definitions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
