<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_debt_balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->string('spreadsheet_id', 100);
            $table->string('identity', 100);
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('source_key', 64);
            $table->string('customer_name');
            $table->string('customer_code')->nullable();
            $table->string('sale_name')->nullable();
            $table->bigInteger('sheet_balance');
            $table->bigInteger('previous_balance');
            $table->bigInteger('new_balance');
            $table->bigInteger('delta');
            $table->date('effective_date');
            $table->text('reason');
            $table->unsignedBigInteger('created_by')->index();
            $table->string('actor_name');
            $table->timestamps();
            $table->index(['spreadsheet_id', 'identity', 'id'], 'customer_debt_balance_identity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_debt_balance_adjustments');
    }
};
