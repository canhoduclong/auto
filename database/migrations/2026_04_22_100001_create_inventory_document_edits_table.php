<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_document_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_document_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedTinyInteger('edit_number');
            $table->text('notes')->nullable();
            // Snapshot of changes: JSON [{variant_id, old_qty, new_qty, old_cost, new_cost}]
            $table->json('changes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_document_edits');
    }
};
