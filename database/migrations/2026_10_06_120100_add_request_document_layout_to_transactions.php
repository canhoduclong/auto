<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('transactions', fn(Blueprint $table) => $table->string('request_document_layout')->nullable()); }
 public function down(): void { Schema::table('transactions', fn(Blueprint $table) => $table->dropColumn('request_document_layout')); }
};
