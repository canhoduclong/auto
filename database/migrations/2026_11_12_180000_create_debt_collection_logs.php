<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_collection_reminders', function (Blueprint $t) {
            $t->id();
            $t->uuid('request_id')->unique();
            $t->string('spreadsheet_id', 100);
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->string('source_key', 64)->nullable();
            $t->json('source_keys');
            $t->string('customer_name');
            $t->timestamp('reminded_at');
            $t->bigInteger('balance_at_reminder');
            $t->bigInteger('target_amount');
            $t->date('promised_date')->nullable();
            $t->date('follow_up_date')->nullable();
            $t->string('channel', 30);
            $t->text('note');
            $t->json('baseline_receipts');
            $t->string('snapshot_loaded_at')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->string('actor_name');
            $t->timestamps();
            $t->index(['spreadsheet_id', 'reminded_at']);
        });
        Schema::create('debt_collection_events', function (Blueprint $t) {
            $t->id();
            $t->uuid('request_id')->unique();
            $t->unsignedBigInteger('reminder_id')->index();
            $t->string('type', 30);
            $t->text('note');
            $t->bigInteger('promised_amount')->nullable();
            $t->date('promised_date')->nullable();
            $t->string('receipt_token', 64)->nullable()->unique();
            $t->bigInteger('receipt_amount')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->string('actor_name');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_collection_events');
        Schema::dropIfExists('debt_collection_reminders');
    }
};
