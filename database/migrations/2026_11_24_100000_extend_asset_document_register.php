<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_assets', function (Blueprint $table) {
            $table->string('category', 24)->nullable();
            $table->unsignedBigInteger('value')->nullable();
        });
        Schema::table('asset_contract_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable()->change();
            $table->foreignId('asset_id')->nullable()->constrained('company_assets')->restrictOnDelete();
            $table->string('document_type', 24)->default('other');
            $table->string('document_number', 100)->nullable();
            $table->date('issued_on')->nullable();
            $table->json('details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('asset_contract_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_id');
            $table->dropColumn(['document_type', 'document_number', 'issued_on', 'details']);
        });
        Schema::table('company_assets', fn (Blueprint $table) => $table->dropColumn(['category', 'value']));
    }
};
