<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('layout_name', 'layout_web_name');
            $table->renameColumn('layout_slug', 'layout_web_slug');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('layout_mobile_name')->nullable()->after('layout_web_slug');
            $table->string('layout_mobile_slug', 120)->nullable()->after('layout_mobile_name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['layout_mobile_name', 'layout_mobile_slug']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('layout_web_name', 'layout_name');
            $table->renameColumn('layout_web_slug', 'layout_slug');
        });
    }
};
