<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_histories', function (Blueprint $table): void {
            $table->string('source', 20)->nullable()->after('note');
            $table->string('ip_address', 45)->nullable()->after('source');
            $table->string('request_method', 10)->nullable()->after('ip_address');
            $table->string('route_name')->nullable()->after('request_method');
            $table->string('request_path', 1000)->nullable()->after('route_name');
            $table->text('user_agent')->nullable()->after('request_path');
            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('order_histories', function (Blueprint $table): void {
            $table->dropIndex(['source', 'created_at']);
            $table->dropColumn(['source', 'ip_address', 'request_method', 'route_name', 'request_path', 'user_agent']);
        });
    }
};
