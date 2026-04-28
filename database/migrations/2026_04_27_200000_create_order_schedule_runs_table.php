<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_schedule_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger_type', 30)->default('cron'); // cron | manual
            $table->string('status', 30)->default('success');    // success | failed
            $table->integer('evaluated')->default(0);
            $table->integer('generated')->default(0);
            $table->integer('need_review')->default(0);
            $table->integer('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_schedule_runs');
    }
};
