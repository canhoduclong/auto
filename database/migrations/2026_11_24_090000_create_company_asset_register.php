<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_code', 40)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('address', 500)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('asset_partners')->restrictOnDelete();
            $table->string('number', 100);
            $table->string('title');
            $table->date('signed_on')->index();
            $table->date('expires_on')->nullable();
            $table->unsignedBigInteger('value');
            $table->string('status', 24);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['partner_id', 'number']);
        });

        Schema::create('company_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained('asset_contracts')->restrictOnDelete();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('status', 24);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_contract_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('asset_contracts')->restrictOnDelete();
            $table->date('paid_on');
            $table->unsignedBigInteger('amount');
            $table->string('method', 24);
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['contract_id', 'paid_on']);
        });

        Schema::create('asset_contract_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('asset_contracts')->restrictOnDelete();
            $table->string('title', 150)->nullable();
            $table->string('name');
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_contract_documents');
        Schema::dropIfExists('asset_contract_payments');
        Schema::dropIfExists('company_assets');
        Schema::dropIfExists('asset_contracts');
        Schema::dropIfExists('asset_partners');
    }
};
