<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->string('schedule_snapshot_hash', 64)->nullable()->after('note');
            $table->longText('schedule_snapshot')->nullable()->after('schedule_snapshot_hash');
        });
    }

    public function down(): void
    {
        Schema::table('order_histories', function (Blueprint $table) {
            $table->dropColumn(['schedule_snapshot_hash', 'schedule_snapshot']);
        });
    }
};