<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('task_assignments', function(Blueprint $t) { $t->string('task_type')->default('default'); $t->json('debt_items')->nullable(); }); }
    public function down(): void { Schema::table('task_assignments', fn(Blueprint $t) => $t->dropColumn(['task_type','debt_items'])); }
};
